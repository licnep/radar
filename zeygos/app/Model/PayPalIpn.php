<?php
namespace App\Model;

/**
 * Class PayPalIpn
 * @package App\Model
 */
class PayPalIpn extends PayPal
{
    /**
     * PayPalIpn constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->settings['log_file'] = $this->settings['log_file_ipn'];
    }

    /**
     * @return bool
     */
    public function process()
    {
        $data = !empty($_POST) ? $_POST : $_GET;

        if (!$data || !is_array($data)) {
            exit('It works!');
        }

        $this->log("\n#####=====--------- " . date('r') . " ---------=====#####");
        $this->log(print_r($data, true) . "\n");

        $encodedData = 'cmd=_notify-validate';
        foreach ($data as $key => $value) {
            $encodedData .= "&$key=".urlencode($value);
        }
        $response = $this->checkIpn($encodedData);
        if ($response === false) {
            $this->log("* IPN Check FAILED: " . $this->getErrorMessage());
            return false;
        } else {
            $this->log("* IPN Check - VERIFIED");
        }

        switch ($data['txn_type']) {
            case 'recurring_payment_profile_created':
                return $this->recurringPaymentProfileCreated($data);
                break;
            case 'recurring_payment_profile_cancel':
                return $this->recurringPaymentProfileCancel($data);
                break;
            case 'recurring_payment':
                return $this->recurringPayment($data);
                break;
            case 'recurring_payment_skipped':
                return $this->recurringPaymentSkipped($data);
                break;
            case 'recurring_payment_suspended_due_to_max_failed_payment':
                return $this->recurringPaymentSuspendedDueToMaxFailedPayment($data);
                break;
            default:
                $this->log("* FAILURE: received unknown txn_type - " . $data['txn_type']);
                return false;
                break;
        }
    }

    /**
     * Recurring payment profile created.
     *
     * @param $details
     * @return bool
     */
    protected function recurringPaymentProfileCreated($details)
    {
        // Check the profile status for the recurring payment.
        if ($details['profile_status'] != 'Active') {
            $this->log(sprintf("FAILURE: expected profile status 'Active', got '%s' instead.", $details['profile_status']));
            return false;
        }

        // Identify user subscription using the recurring payment ID.
        $paymentId = $details['recurring_payment_id'];
        $subscription = $this->getUserSubscriptionByPaymentId($paymentId);
        if (empty($subscription)) {
            $this->log(sprintf("FAILURE: could not find recurring payment ID %s in database.", $paymentId));
            return false;
        }

        // Read details about the initial payment.
        $amount = isset($details['initial_payment_amount']) ? $details['initial_payment_amount'] : 0;
        $status = isset($details['initial_payment_status']) ? $details['initial_payment_status'] : '';
        if ($amount > 0 && $status != 'Completed') {
            $this->log(sprintf("FAILURE: expected an initial payment status of 'Completed', got '%s' instead.", $status));
            return false;
        }

        if ($amount > 0) {
            // Track the subscription payment.
            $this->addSubscriptionPayment(
                $subscription['user_id'],
                $subscription['subscription_id'],
                $details['initial_payment_txn_id'],
                $subscription['subscription_initial_amount']
            );
        } else {
            // Subscription downgrade -- no payment.
        }

        // Activate the subscription.
        $nextPaymentDate = strtotime($details['next_payment_date']);

        $sql = "UPDATE subscription
                SET subscription_status = 'active',
                    subscription_failed_payments = 0,
                    subscription_next_payment = :subscription_next_payment
                WHERE subscription_id = :subscription_id";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':subscription_next_payment', $nextPaymentDate);
        $stmt->bindValue(':subscription_id', $subscription['subscription_id']);
        $stmt->execute();

        $this->log("SUCCESS: marked the recurring profile payment as Active.");

        return true;
    }

    /**
     * Rebill.
     *
     * @param $details
     * @return bool
     */
    protected function recurringPayment($details)
    {
        // Check the profile status for the recurring payment.
        if ($details['profile_status'] != 'Active') {
            $this->log(sprintf("FAILURE: expected profile status 'Active', got '%s' instead.", $details['profile_status']));
            return false;
        }

        // Identify user subscription using the recurring payment ID.
        $paymentId = $details['recurring_payment_id'];
        $subscription = $this->getUserSubscriptionByPaymentId($paymentId);
        if (empty($subscription)) {
            $this->log(sprintf("FAILURE: could not find recurring payment ID %s in database.", $paymentId));
            return false;
        }

        // Read details about the payment.
        $status = isset($details['payment_status']) ? $details['payment_status'] : '';
        if ($status != 'Completed') {
            $this->log(sprintf("FAILURE: expected a payment status of 'Completed', got '%s' instead.", $status));
            return false;
        }

        // Track the subscription payment.
        $this->addSubscriptionPayment(
            $subscription['user_id'],
            $subscription['subscription_id'],
            $details['txn_id'],
            $details['amount']
        );

        // Update the subscription.
        $nextPaymentDate = strtotime($details['next_payment_date']);

        $sql = "UPDATE subscription
                SET subscription_status = 'active',
                    subscription_failed_payments = 0,
                    subscription_next_payment = :subscription_next_payment
                WHERE subscription_id = :subscription_id";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':subscription_next_payment', $nextPaymentDate);
        $stmt->bindValue(':subscription_id', $subscription['subscription_id']);
        $stmt->execute();

        $this->log("SUCCESS: updated the subscription.");
        return true;
    }

    /**
     * Cancel.
     *
     * @param $details
     * @return bool
     */
    protected function recurringPaymentProfileCancel($details)
    {
        $this->log(sprintf("Entered recurringPaymentProfileCancel() -- %s", json_encode($details)));

        // Identify user subscription using the recurring payment ID.
        $paymentId = $details['recurring_payment_id'];
        $subscription = $this->getUserSubscriptionByPaymentId($paymentId);
        if (empty($subscription)) {
            $this->log(sprintf("FAILURE: could not find recurring payment ID %s in database.", $paymentId));
            return false;
        }

        $this->log(sprintf("Found subscription %s", json_encode($subscription)));

        // Figure out what to do with the subscription -- either cancel or terminate it.
        if ($subscription['subscription_status'] == 'pending') {
            $status = 'terminated';
        } elseif ($subscription['subscription_status'] == 'active') {
            $status = 'cancelled';
        } else {
            $this->log(sprintf("FAILURE: the subscription status in database is neither 'pending', nor 'active', but '%s'.", $subscription['subscription_status']));
            return false;
        }

        $this->log(sprintf("Status is %s", $status));

        // Update subscription status.
        $sql = "UPDATE subscription
                SET subscription_status = :subscription_status
                WHERE subscription_id = :subscription_id";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':subscription_status', $status);
        $stmt->bindValue(':subscription_id', $subscription['subscription_id']);
        $stmt->execute();

        $this->log("SUCCESS: marked all recurring profile payments as $status.");
        return true;
    }

    /**
     * Recurring payment skipped; it will be retried up to 3 times, 5 days apart.
     *
     * @param $details
     * @return bool
     */
    protected function recurringPaymentSkipped($details)
    {
        // Identify user subscription using the recurring payment ID.
        $paymentId = $details['recurring_payment_id'];
        $subscription = $this->getUserSubscriptionByPaymentId($paymentId);
        if (empty($subscription)) {
            $this->log(sprintf("FAILURE: could not find recurring payment ID %s in database.", $paymentId));
            return false;
        }

        // Increment the number of failed payments in database.
        $sql = "UPDATE subscription
                SET subscription_failed_payments = subscription_failed_payments + 1
                WHERE subscription_id = :subscription_id";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':subscription_id', $subscription['subscription_id']);
        $stmt->execute();

        return true;
    }

    /**
     * Recurring payment failed and the related recurring payment profile has been suspended.
     * This transaction type is sent if:
     * - PayPal's attempt to collect a recurring payment failed.
     * - The "max failed payments" setting in the customer's recurring payment profile is 1 or greater.
     * - The number of attempts to collect payment has exceeded the value specified for "max failed payments".
     *   In this case, PayPal suspends the customer's recurring payment profile.
     *
     * @param $details
     * @return bool
     */
    protected function recurringPaymentSuspendedDueToMaxFailedPayment($details)
    {
        // Identify user subscription using the recurring payment ID.
        $paymentId = $details['recurring_payment_id'];
        $subscription = $this->getUserSubscriptionByPaymentId($paymentId);
        if (empty($subscription)) {
            $this->log(sprintf("FAILURE: could not find recurring payment ID %s in database.", $paymentId));
            return false;
        }

        // Update subscription status.
        $sql = "UPDATE subscription
                SET subscription_status = 'terminated',
                    subscription_failed_payments = subscription_failed_payments + 1
                WHERE subscription_id = :subscription_id";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':subscription_id', $subscription['subscription_id']);
        $stmt->execute();

        return true;
    }

    /**
     * @param $paymentId
     * @return mixed
     */
    protected function getUserSubscriptionByPaymentId($paymentId)
    {
        $sql = "SELECT users.email,
                       users.first_name,
                       users.last_name,
                       subscription.user_id,
                       subscription.subscription_id,
                       subscription.subscription_status,
                       subscription.subscription_length,
                       subscription.subscription_start_date,
                       subscription.subscription_initial_amount
                FROM subscription
                  LEFT JOIN users ON subscription.user_id = users.id
                WHERE subscription.merchant_subscription_id = :merchant_subscription_id";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':merchant_subscription_id', $paymentId);
        $stmt->execute();

        $subscription = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $subscription;
    }

    /**
     * @param $userId
     * @param $subscriptionId
     * @param $transactionId
     * @param $amount
     * @return string
     */
    protected function addSubscriptionPayment($userId, $subscriptionId, $transactionId, $amount)
    {
        $sql = "INSERT INTO subscription_payment
                SET user_id = :user_id,
                    payment_time = :payment_time,
                    payment_amount = :payment_amount,
                    subscription_id = :subscription_id,
                    merchant_transaction_id = :merchant_transaction_id";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->bindValue(':payment_time', time());
        $stmt->bindValue(':payment_amount', $amount);
        $stmt->bindValue(':subscription_id', $subscriptionId);
        $stmt->bindValue(':merchant_transaction_id', $transactionId);
        $stmt->execute();

        $paymentId = $this->app->dbh->lastInsertId();

        return $paymentId;
    }

    /**
     * Handles the IPN post back to PayPal
     * Returns true if the response comes back as "VERIFIED", false otherwise.
     *
     * @param $data
     * @return bool
     */
    protected function checkIpn($data)
    {
        $ch = curl_init();

        $verbose = fopen('php://temp', 'rw+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        curl_setopt($ch, CURLOPT_URL, $this->paypalUrl);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
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
        $this->log(print_r($result, true));

        if ($result == 'INVALID') {
            $this->error = true;
            $this->errorMessage = 'Received INVALID response from Paypal.';
            return false;
        } else if ($result == 'VERIFIED') {
            return true;
        } else {
            $this->error = true;
            $this->errorMessage = 'Received unknown code from Paypal: ' . $result;
            return false;
        }
    }
}
