<?php
namespace App\Controller;

use App\Model\User as UserModel;
use Respect\Validation\Validator as Validator;

/**
 * Class HomeController
 * @package App\Controller
 */
class HomeController extends BaseController
{
    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getDashboard($request, $response, $args)
    {
        $args['title'] = "Zeygos Radar";

        $userModel = new UserModel($this);
        $args['subscription'] = $userModel->getCurrentSubscription($this->user['id']);

        return $this->view->render($response, 'dashboard.phtml', $args);
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getSubscription($request, $response, $args)
    {
        $args['title'] = "Subscription";

        $userModel = new UserModel($this);
        $args['subscription'] = $userModel->getCurrentSubscription($this->user['id']);

        return $this->view->render($response, 'subscription.phtml', $args);
    }


    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getLanding($request, $response, $args)
    {


        $args['title'] = "Welcome";
        return $this->view->render($response, 'landing.phtml', $args);
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getPrivacy($request, $response, $args)
    {
        $args['title'] = "Privacy";
        return $this->view->render($response, 'privacy.phtml', $args);
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getTerms($request, $response, $args)
    {
        $args['title'] = "Terms and Conditions";
        return $this->view->render($response, 'terms.phtml', $args);
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getChangePassword($request, $response, $args)
    {
        $args['title'] = "Change Password";

        return $this->view->render($response, 'password_change.phtml', $args);
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function postChangePassword($request, $response, $args)
    {
        $validation = $this->validator->validate($request, [
            'password' => Validator::noWhitespace()->notEmpty(),
        ]);

        if ($validation->failed()) {
            return $response->withRedirect($this->router->pathFor('change-password'));
        }

        $userId = $this->user['id'];
        $password = $request->getParam("password");

        $userModel = new UserModel($this);
        try {
            $userModel->updatePasswordByUserId($userId, $password);
        } catch (\Exception $e) {
            $this->flash->addMessage('error', 'We were unable to update the password. Please try again.');
            return $response->withRedirect($this->router->pathFor('change-password'));
        }

        $this->flash->addMessage('info', 'The password was updated.');
        return $response->withRedirect($this->router->pathFor('dashboard'));
    }
}
