<?php
namespace App\Model;

use App\Model\User as UserModel;

/**
 * Class PayPal
 * @package App\Model
 */
class PayPal extends Base
{
    /**
     * The PayPal API URL.
     */
    protected $paypalUrl;

    /**
     * The Paypal API endpoint URL.
     */
    protected $apiEndpoint;

    /**
     * The Paypal API version.
     *
     * @var string
     */
    protected $apiVersion = '64';

    /**
     * @var bool
     */
    protected $error = false;

    /**
     * @var string
     */
    protected $errorMessage = '';

    /**
     * @var string
     */
    protected $logFile = '';

    /**
     * @var array
     */
    protected $settings = array();

    /**
     * @var \PDO
     */
    protected $dbh;

    /**
     * PayPal constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $settings = $app->settings['paypal'];
        $paypalEnv = $settings['environment'];
        $paypalSettings = $settings[$paypalEnv];

        $this->setEnvironment($paypalEnv);
        $this->setSettings($paypalSettings);
    }

    /**
     * Set PayPal settings.
     *
     * @param $settings
     */
    protected function setSettings($settings)
    {
        $this->settings = $settings;
    }

    /**
     * Sets the Paypal environment: live or sandbox.
     *
     * @param $env
     */
    public function setEnvironment($env)
    {
        if ($env == 'live') {
            $this->apiEndpoint = "https://api-3t.paypal.com/nvp";
            $this->paypalUrl = "https://www.paypal.com/cgi-bin/webscr";
        } else {
            $this->apiEndpoint = "https://api-3t.sandbox.paypal.com/nvp";
            $this->paypalUrl = "https://www.sandbox.paypal.com/webscr";
        }
    }

    /**
     * Process a Recurring Payment request via Paypal.
     *
     * @param \App\Model\Plan $plan
     * @return bool
     */
    public function processRecurringPayment(Plan $plan)
    {
        $this->log(sprintf("Entered processRecurringPayment() -- %s", json_encode($plan)));

        // Format the price for PayPal with period (.) as decimal separator.
        $price = number_format($plan->price(), 2, '.', '');

        // SetExpressCheckout
        $data = array();

        // SetExpressCheckout Request Fields.
        $data['MAXAMT'] = $price;
        $data['RETURNURL'] = $this->settings['return_url'];
        $data['CANCELURL'] = $this->settings['return_url'];
        $data['NOSHIPPING'] = 1;
        $data['ALLOWNOTE'] = 0;

        // Billing Agreement Details Type Fields.
        $data['L_BILLINGTYPE0'] = 'RecurringPayments';
        $data['L_BILLINGAGREEMENTDESCRIPTION0'] = 'Market Radar subscription';

        // Payment Details Type Fields.
        $data['PAYMENTREQUEST_0_AMT'] = $price;
        $data['PAYMENTREQUEST_0_CURRENCYCODE'] = 'EUR';
        $data['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Sale';
        $data['L_PAYMENTREQUEST_0_NAME0'] = $plan->name();
        $data['L_PAYMENTREQUEST_0_DESC0'] = "Market Radar subscription";
        $data['L_PAYMENTREQUEST_0_AMT0'] = $price;
        $data['L_PAYMENTREQUEST_0_NUMBER0'] = $plan->id();
        $data['L_PAYMENTREQUEST_0_ITEMCATEGORY0'] = 'Digital';

        // The response contains a token for use in subsequent steps.
        $result = $this->executeRequest('SetExpressCheckout', $data);
        if (!is_array($result) || empty($result)) {
            $this->error = true;
            $this->errorMessage = "There was an error communicating with PayPal!";
            return false;
        }

        // Interpret the SetExpressCheckout acknowledgement code received.
        $ack = strtolower($result['ACK']);

        if ($ack == 'success' || $ack == 'successwithwarning') {
            // A timestamped token by which you identify to PayPal
            // that you are processing this payment with Express Checkout.
            $token = $result['TOKEN'];

            $this->redirectToPayPal($token);
        } elseif ($ack == 'failure') {
            $this->error = true;
            $this->errorMessage = $result['L_SHORTMESSAGE0'] . '<br />' . $result['L_LONGMESSAGE0'];
            return false;
        } else {
            $this->error = true;
            $this->errorMessage = sprintf('Received unknown acknowledgement code %s', $result['ACK']);
            return false;
        }
    }

    /**
     * Finalise a Recurring Payment request when returned from Paypal.
     *
     * @param \App\Model\Plan $plan
     * @param $token
     * @param $payerId
     * @return mixed
     */
    public function finaliseRecurringPayment(Plan $plan, $token, $payerId)
    {
        $this->log(sprintf("Entered finaliseRecurringPayment() -- %s %s %s", json_encode($plan), $token, $payerId));

        $userModel = new UserModel($this->app);
        $currentSubscription = $userModel->getCurrentSubscription($this->app->user['id']);

        if ($currentSubscription) {
            $profileStartDate = $currentSubscription['next_payment'];
            $nextPayment = $currentSubscription['next_payment'];
        } else {
            list($day, $month, $year) = explode('-', date('j-n-Y'));

            $nextMonth = $month + 1;
            if ($nextMonth == 13) {
                $nextMonth = 1;
                $year++;
            }

            $last = date('t', mktime(13, 0, 0, $nextMonth, 1, $year));
            if ($day > $last) {
                $day = $last;
            }
            $profileStartDate = mktime(0, 0, 0, $nextMonth, $day, $year);
            $nextPayment = mktime(0, 0, 0, $nextMonth, $day, $year);

        }
        $profileStartDate = str_replace(date('P'), 'Z', date('c', $profileStartDate));

        // Format the price for PayPal with period (.) as decimal separator.
        $price = number_format($plan->price(), 2, '.', '');
        $data = array(
            'TOKEN' => $token,
            'PAYERID' => $payerId,
            'PROFILESTARTDATE' => $profileStartDate,
            'DESC' => 'Market Radar subscription',
            'BILLINGPERIOD' => 'Month',
            'BILLINGFREQUENCY' => 1,
            'AMT' => $price,
            'INITAMT' => 0,
            'TRIALBILLINGPERIOD' => 'Month',
            'TRIALBILLINGFREQUENCY' => 1,
            'TRIALTOTALBILLINGCYCLES' => 1,
            'TRIALAMT' => 0.00,
            'CURRENCYCODE' => 'EUR',
            'FAILEDINITAMTACTION' => 'CancelOnFailure',
            'MAXFAILEDPAYMENTS' => 1
        );

        // The response contains an ActiveProfile status, indicating that the customer will be billed, and a PROFILEID value.
        $result = $this->executeRequest('CreateRecurringPaymentsProfile', $data);
        if (!is_array($result) || empty($result)) {
            return false;
        }

        // Interpret the CreateRecurringPaymentsProfile acknowledgement code received.
        $ack = strtolower($result['ACK']);
        if ($ack == 'success' || $ack == 'successwithwarning') {
            //
        } elseif ($ack == 'failure') {
            $this->error = true;
            $this->errorMessage = $result['L_SHORTMESSAGE0'] . '<br />' . $result['L_LONGMESSAGE0'];
            return false;
        } else {
            $this->error = true;
            $this->errorMessage = sprintf('Received unknown acknowledgement code %s', $result['ACK']);
            return false;
        }

        $profileId = $result['PROFILEID'];
        $status = $result['PROFILESTATUS'];
        if ($status == 'ActiveProfile') {
            $status = 'active';
        } else {
            $status = 'pending';
        }

        // Save the transaction in the database and get the new ID.
        $sql = "INSERT INTO subscription
                SET merchant_subscription_id = :merchant_subscription_id,
                    user_id = :user_id,
                    subscription_start_date = :subscription_start_date,
                    subscription_current_billing_start = :subscription_current_billing_start,
                    subscription_next_payment = :subscription_next_payment,
                    subscription_initial_amount = :subscription_initial_amount,
                    subscription_amount = :subscription_amount,
                    subscription_length = :subscription_length,
                    subscription_plan = :subscription_plan,
                    subscription_status = :subscription_status";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':merchant_subscription_id', $profileId);
        $stmt->bindValue(':user_id', $this->app->user['id']);
        $stmt->bindValue(':subscription_start_date', time());
        $stmt->bindValue(':subscription_current_billing_start', time());
        $stmt->bindValue(':subscription_next_payment', $nextPayment);
        $stmt->bindValue(':subscription_initial_amount', 0);
        $stmt->bindValue(':subscription_amount', $price);
        $stmt->bindValue(':subscription_length', 1);
        $stmt->bindValue(':subscription_plan', $plan->id());
        $stmt->bindValue(':subscription_status', $status);
        $stmt->execute();

        $subscriptionId = $this->app->dbh->lastInsertId();

        // Cancel any existing subscription
        if (!empty($currentSubscription)) {
            $this->cancelSubscription($currentSubscription['subscription_id']);
        }

        return $subscriptionId;
    }

    /**
     * Cancel a PayPal subscription.
     *
     * @param $id
     * @return bool
     */
    public function cancelSubscription($id)
    {
        $this->log(sprintf("Entered cancelSubscription(#%s)", $id));

        $sql = "SELECT merchant_subscription_id
                FROM subscription
                WHERE subscription_id = :subscription_id";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':subscription_id', $id);
        $stmt->execute();
        $profileId = $stmt->fetchColumn();

        if (!$profileId) {
            $this->error = true;
            $this->errorMessage = sprintf('Subscription #%s does not exist.', $id);

            $this->log($this->errorMessage);
            return false;
        }

        $data = array(
            'PROFILEID' => $profileId,
            'ACTION'    => 'Cancel'
        );
        $response = $this->executeRequest('ManageRecurringPaymentsProfileStatus', $data);
        if (!is_array($response) || empty($response)) {
            return false;
        }

        // Interpret the acknowledgement code received.
        $ack = strtolower($response['ACK']);
        if($ack == 'success' || $ack == 'successwithwarning') {


            // Update subscription in database.
            $sql = "UPDATE subscription
                    SET subscription_status = 'cancelled'
                    WHERE subscription_id = :id";

            $stmt = $this->app->dbh->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->execute();

            return true;
        } elseif ($ack == 'failure') {
            $this->error = true;
            $this->errorMessage = $response['L_LONGMESSAGE0'] . ".";
            return false;
        } else {
            $this->error = true;
            $this->errorMessage = sprintf('Received unknown acknowledgement code \'%s\'', $response['ACK']);
            return false;
        }
    }

    /**
     * Redirect to Paypal after a token has been obtained via a setExpressCheckout API call.
     *
     * @param string $token
     */
    public function redirectToPayPal($token)
    {
        header('Location: ' . $this->paypalUrl . '?cmd=_express-checkout&token=' . $token);
        exit;
    }

    /**
     * Responsible for actually formatting and then sending the NVP requests to Paypal.
     *
     * @param  string $method
     * @param  array $data
     * @return bool
     */
    public function executeRequest($method, $data = array())
    {
        $data['METHOD'] = $method;
        $data['VERSION'] = urlencode($this->apiVersion);
        $data['USER'] = urlencode($this->settings['api_username']);
        $data['PWD'] = urlencode($this->settings['api_password']);
        $data['SIGNATURE'] = urlencode($this->settings['api_signature']);

        // Convert the data to a correctly formatted NVP string
        $data = http_build_query($data, '', '&');

        // Execute the transaction via CURL.
        $ch = curl_init();

        $verbose = fopen('php://temp', 'rw+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        curl_setopt($ch, CURLOPT_URL, $this->apiEndpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // For older versions of openssl we manually force TLS
        // @see http://php.net/manual/en/openssl.constversion.php
        if (defined("OPENSSL_VERSION_NUMBER") && OPENSSL_VERSION_NUMBER <= 9469999) {
            // CURL_SSLVERSION_TLSv1 was introduced in PHP 5.5
            // @see https://bugs.php.net/bug.php?id=62318
            if (!defined("CURL_SSLVERSION_TLSv1")) {
                define ("CURL_SSLVERSION_TLSv1", 1);
            }

            // Force using TLS for OpenSSL version 0.9.8e-fips-rhel5 01 Jul 2008 or older
            // @see http://php.net/manual/en/function.curl-setopt.php
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);

            // Also specify TLSv1 or RC4-SHA cipher to avoid
            // SSL23_GET_SERVER_HELLO:sslv3 alert handshake failure
            curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, "TLSv1");
        }

        $result = curl_exec($ch);
        if ($result === false) {
            $this->error = true;
            $this->errorMessage = curl_errno($ch) ? curl_error($ch) : 'An unknown error has occurred while trying to contact the remote gateway';
            $this->log(print_r($this->errorMessage, true));

            // Log the output of CURL
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            $this->log(print_r($verboseLog, true));

            curl_close($ch);
            return false;
        }

        curl_close($ch);
        parse_str($result, $response);

        $this->log(print_r($result, true));
        $this->log(print_r($response, true));

        return $response;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function log($message)
    {
        if (!is_string($message)) {
            $message = print_r($message, true);
        }

        error_log(date('r') . "\n\r" . $message . "\n", 3, $this->settings['log_file']);
    }
}
