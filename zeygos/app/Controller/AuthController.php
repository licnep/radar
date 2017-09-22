<?php
namespace App\Controller;

use App\Model\User as UserModel;
use Hashids\Hashids;
use Respect\Validation\Validator as Validator;

/**
 * Class AuthController
 * @package App\Controller
 */
class AuthController extends BaseController
{
    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getPasswordReset($request, $response, $args)
    {
        $args['title'] = "Password Reset";

        $token = $request->getParam('token');
        if (!$token) {
            $this->flash->addMessage('error', 'The reset password link is invalid.');
            return $response->withRedirect($this->router->pathFor('password-reset-request'));
        }

        $userModel = new UserModel($this);
        $user = $userModel->getUserByAuthToken($token);
        if (!$user) {
            $this->flash->addMessage('error', 'The reset password link is invalid.');
            return $response->withRedirect($this->router->pathFor('password-reset-request'));
        }

        $args['token'] = $token;

        return $this->view->render($response, 'password_reset.phtml', $args);
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function postPasswordReset($request, $response, $args)
    {
        $validation = $this->validator->validate($request,[
            'password' => Validator::noWhitespace()->notEmpty(),
        ]);

        if ($validation->failed()) {
            return $response->withRedirect($this->router->pathFor('password-reset'));
        }

        $token = $request->getParam('token');

        if (!$token) {
            $this->flash->addMessage('error', 'The reset password details are invalid.');
            return $response->withRedirect($this->router->pathFor('password-reset'));
        }

        $password = $request->getParam("password");

        $userModel = new UserModel($this);
        try {
            $userModel->updatePasswordByToken($token, $password);
        } catch (\Exception $e) {
            $this->flash->addMessage('error', 'We were unable to update the password. Please try again.');
            return $response->withRedirect($this->router->pathFor('password-reset'));
        }

        $this->flash->addMessage('info', 'The password was updated.');
        return $response->withRedirect($this->router->pathFor('login'));
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getPasswordResetRequest($request, $response, $args)
    {
        $args['title'] = "Password Reset Request";

        return $this->view->render($response, 'password_reset_request.phtml', $args);
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function postPasswordResetRequest($request, $response, $args)
    {
        $validation = $this->validator->validate($request,[
            'email' => Validator::noWhitespace()->notEmpty()->email()
        ]);

        if ($validation->failed()) {
            return $response->withRedirect($this->router->pathFor('password-reset-request'));
        }

        $email = $request->getParam("email");

        $userModel = new UserModel($this);
        $user = $userModel->getUserByEmail($email);
        if (!$user) {
            $_SESSION['errors']['email'][] = 'There is no user with this email address.';
            return $response->withRedirect($this->router->pathFor('password-reset-request'));
        }

        // Send email
        $mail = new \PHPMailer;

        $mail->setFrom($this->settings['email']['from_email'], $this->settings['email']['from_name']);
        $mail->addReplyTo($this->settings['email']['replyto_email'], $this->settings['email']['replyto_name']);
        $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
        $mail->Subject = 'Password reset for My Awesome App';

        $hashids = new Hashids(uniqid('', true) . ' -> ' . md5(dirname(__FILE__)), 16);
        $token = md5($hashids->encode($user['id']));

        $userModel->setUserAuthToken($user['id'], $token);

        $link = $this->settings['url']['app'] . 'password-reset?token=' . $token;
        $mail->msgHTML('Hello ' . $user['email'] . ', <br><br>Click <a href="' . $link . '">this link</a> to reset your password. <br><br>Thank you.');

        if (!$mail->send()) {
            $this->flash->addMessage('error', 'We were unable to send the email. Please try again (' . $mail->ErrorInfo . ')');
            return $response->withRedirect($this->router->pathFor('password-reset-request'));
        } else {
            $this->flash->addMessage('info', 'Check your email for further instructions.');
            return $response->withRedirect($this->router->pathFor('home'));
        }
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getLogin($request, $response, $args)
    {
        $args['menu'] = "login";
        $args['title'] = "Sign in";
        $args['facebookAppId'] = $this->settings['facebook']['app_id'];

        return $this->view->render($response, 'login.phtml', $args);
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function postLogin($request, $response, $args)
    {
        $validation = $this->validator->validate($request,[
            'email' => Validator::noWhitespace()->notEmpty()->email(),
            'password' => Validator::noWhitespace()->notEmpty(),
        ]);

        if ($validation->failed()) {
            return $response->withRedirect($this->router->pathFor('login'));
        }

        $data["email"] = $request->getParam("email");
        $data["password"] = $request->getParam("password");

        $userModel = new UserModel($this);
        $success = $userModel->login($data["email"], $data["password"]);

        if (!$success) {
            $this->flash->addMessage('error', 'The credentials are invalid.');
            return $response->withRedirect($this->router->pathFor('login'));
        }

        return $response->withRedirect($this->router->pathFor('dashboard'));
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getFacebook($request, $response, $args)
    {
        // Required parameters
        $facebookId = filter_var($request->getParam("id"), FILTER_SANITIZE_STRING);
        $facebookEmail = filter_var($request->getParam("email"), FILTER_VALIDATE_EMAIL);

        $data = [
            'status' => 'success',
            'redirect' => $this->router->pathFor('dashboard')
        ];

        if (!$facebookId) {
            $data['status'] = 'error';
            $data['message'] = 'The Facebook information is either invalid or missing.';
        }

        if (!$facebookEmail) {
            $data['status'] = 'error';
            $data['message'] = 'The Facebook information is either invalid or missing.';
        }

        if ($data['status'] == 'error') {
            return $response->withJson($data);
        }

        // Optional parameters
        $firstName = $request->getParam('first_name');
        $lastName = $request->getParam('last_name');

        // Find an exact match for the Facebook id
        $userEmail = '';
        $userModel = new UserModel($this);
        $userData = $userModel->getUserByFacebookId($facebookId);

        if (! $userData) {
            // Find an exact match for the Facebook email
            $existingUser = $userModel->getUserByEmail($facebookEmail);
            if ($existingUser) {
                // Link accounts
                $userModel->linkFacebookUser($facebookId, $existingUser['id']);
                $userEmail = $existingUser['email'];

            } else {
                // Sign up
                $data["email"] = $facebookEmail;
                $data["password"] = md5(time());
                $data["first_name"] = $firstName;
                $data["last_name"] = $lastName;
                $data["facebook_id"] = $facebookId;

                try {
                    $userModel->insert($data);
                    $userEmail = $data['email'];
                    $data['redirect'] = $this->router->pathFor('checkout');

                    // Send email
                    $mail = new \PHPMailer;

                    $mail->setFrom($this->settings['email']['from_email'], $this->settings['email']['from_name']);
                    $mail->addReplyTo($this->settings['email']['replyto_email'], $this->settings['email']['replyto_name']);
                    $mail->addAddress($facebookEmail, $firstName . ' ' . $lastName);
                    $mail->Subject = 'Welcome at My Awesome App';

                    $link = $this->settings['url']['app'];
                    $mail->msgHTML('Hello ' . $data["email"] . ', <br><br>Welcome at My Awesome App. Click <a href="' . $link . '">this link</a> to return to our website. <br><br>Thank you.');


                } catch (\Exception $e) {
                    $data['status'] = 'error';
                    $data['message'] = $e->getMessage();
                }
            }
        } else {
            $userEmail = $userData['email'];
        }

        if ($data['status'] == 'success') {
            $result = $userModel->login($userEmail);

            if (!$result) {
                $data['status'] = 'error';
                $data['message'] = 'Unable to log you in. Please try again.';
            }
        }

        $jsonResponse = $response->withJson($data);

        return $jsonResponse;
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getLogout($request, $response, $args)
    {
        $userModel = new UserModel($this);
        $userModel->logout();

        return $response->withRedirect($this->router->pathFor('login'));
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getRegister($request, $response, $args)
    {
        $args['menu'] = "register";
        $args['title'] = "Sign up";
        $args['facebookAppId'] = $this->settings['facebook']['app_id'];

        return $this->view->render($response, 'register.phtml', $args);
    }

    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function postRegister($request, $response, $args)
    {
        $validation = $this->validator->validate($request,[
            'first_name' => Validator::noWhitespace()->notEmpty()->alpha(),
            'last_name' => Validator::noWhitespace()->notEmpty()->alpha(),
            'email' => Validator::noWhitespace()->notEmpty()->email(),
            'password' => Validator::noWhitespace()->notEmpty(),
        ]);

        if ($validation->failed()) {
            return $response->withRedirect($this->router->pathFor('register'));
        }

        $data["email"] = $request->getParam("email");
        $data["password"] = $request->getParam("password");
        $data["first_name"] = $request->getParam("first_name");
        $data["last_name"] = $request->getParam("last_name");

        $userModel = new UserModel($this);

        try {
            $userModel->insert($data);
        } catch (\Exception $e) {
            $_SESSION['errors']['email'][] = $e->getMessage();
            return $response->withRedirect($this->router->pathFor('register'));
        }

        // Send email
        $mail = new \PHPMailer;

        $mail->setFrom($this->settings['email']['from_email'], $this->settings['email']['from_name']);
        $mail->addReplyTo($this->settings['email']['replyto_email'], $this->settings['email']['replyto_name']);
        $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
        $mail->Subject = 'Welcome at My Awesome App';

        $link = $this->settings['url']['app'];
        $mail->msgHTML('Hello ' . $data["email"] . ', <br><br>Welcome at My Awesome App. Click <a href="' . $link . '">this link</a> to return to our website. <br><br>Thank you.');

        $userModel->login($data["email"], $data["password"]);

        //return $response->withRedirect($this->router->pathFor('checkout'));
        return $response->withRedirect($this->router->pathFor('dashboard'));
    }
}
