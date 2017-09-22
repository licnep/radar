<?php
namespace App\Controller;

use App\Model\PayPal as PayPalModel;
use App\Model\PayPalIpn as PayPalIpnModel;
use App\Model\Subscription as SubscriptionModel;

/**
 * Class Paypal
 * @package App\Controller
 */
class PayPalController extends BaseController
{
    /**
     * PayPal IPN.
     */
    public function postIpn($request, $response, $args)
    {
        $paypal = new PayPalIpnModel($this);
        $paypal->process();
    }

    /**
     * PayPal return page.
     *
     * @return string
     */
    public function getReturn($request, $response, $args)
    {
        $subscriptionModel = new SubscriptionModel($this);
        $plan = $subscriptionModel->getDefaultPlan();

        $token = $request->getParam('token');
        $payerId = $request->getParam('PayerID');

        if ($token && $payerId) {
            $paypalModel = new PayPalModel($this);
            $success = $paypalModel->finaliseRecurringPayment($plan, $token, $payerId);

            if ($success === false) {
                $this->flash->addMessage('error', $paypalModel->getErrorMessage());
            }
        } else {
            $this->flash->addMessage('error', 'Invalid response from PayPal.');
        }

        return $response->withRedirect($this->router->pathFor('dashboard'));
    }
}
