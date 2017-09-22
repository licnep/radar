<?php
namespace App\Controller;

use App\Model\PayPal as PayPalModel;
use App\Model\User as UserModel;
use App\Model\Subscription as SubscriptionModel;

/**
 * Class SubscriptionController
 * @package App\Controller
 */
class SubscriptionController extends BaseController
{
    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getCheckout($request, $response, $args)
    {
        $userModel = new UserModel($this);

        $subscriptionModel = new SubscriptionModel($this);
        $plan = $subscriptionModel->getDefaultPlan();

        // Current subscription.
        $currentSubscriptionDetails = $userModel->getCurrentSubscription($this->user['id']);

        // Check if there's any pending subscription
        /*
        if ($currentSubscriptionDetails) {
            $this->flash->addMessage('info', 'You already have a subscription');
            return $response->withRedirect($this->router->pathFor('home'));
        }*/

        $paypalModel = new PayPalModel($this);

        $success = $paypalModel->processRecurringPayment($plan);

        if ($success === false) {
            $this->flash->addMessage('error', $paypalModel->getErrorMessage());
            return $response->withRedirect($this->router->pathFor('dashboard'));
        } else {
            // nothing to do -- user was already redirected to PayPal
        }
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getCancel($request, $response, $args)
    {
        $userModel = new UserModel($this);

        // Current subscription.
        $currentSubscriptionDetails = $userModel->getCurrentSubscription($this->user['id']);

        // Check if there's any pending subscription
        if (!$currentSubscriptionDetails || $currentSubscriptionDetails['subscription_status'] != 'active') {
            $this->flash->addMessage('info', 'You cannot cancel a subscription at this time');
            return $response->withRedirect($this->router->pathFor('home'));
        }

        $paypalModel = new PayPalModel($this);

        $success = $paypalModel->cancelSubscription($currentSubscriptionDetails['subscription_id']);

        if ($success === false) {
            $this->flash->addMessage('error', $paypalModel->getErrorMessage());
            return $response->withRedirect($this->router->pathFor('dashboard'));
        } else {
            $this->flash->addMessage('info', 'You canceled the subscription.');
            return $response->withRedirect($this->router->pathFor('dashboard'));
        }
    }
}
