<?php
namespace App\Model;

use App\Model\Subscription as SubscriptionModel;

/**
 * Class User
 * @package App\Model
 */
class User extends Base
{
    /**
     * Inserts a new user into the database.
     *
     * @param array $data The user data to insert.
     * @return int The user Id.
     * @throws \Exception
     */
    public function insert(array $data)
    {
        // Validate the data coming in.
        $email = isset($data['email']) ? filter_var($data['email'], FILTER_VALIDATE_EMAIL) : '';
        if (! $email) {
            throw new \Exception('The email address is not valid.');
        }

        // Check to see if email already exists.
        $exists = $this->emailExists($email);
        if ($exists) {
            throw new \Exception('The email address already exists, please choose a different one.');
        }

        // Sanitize everything
        foreach ($data as &$attribute) {
            $attribute = filter_var($attribute, FILTER_SANITIZE_STRING);
        }

        $sql = "INSERT INTO users
                SET email = :email,                    
                    password = :password,                    
                    first_name = :first_name,
                    last_name = :last_name,
                    added_at = :added_at,
                    updated_at = :updated_at,
                    facebook_id = :facebook_id";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':email', $data["email"]);
        $stmt->bindValue(':password', md5($data["password"]));
        $stmt->bindValue(':first_name', isset($data["first_name"]) ? $data["first_name"] : "");
        $stmt->bindValue(':last_name', isset($data["last_name"]) ? $data["last_name"] : "");
        $stmt->bindValue(':added_at', time());
        $stmt->bindValue(':updated_at', time());
        $stmt->bindValue(':facebook_id', isset($data["facebook_id"]) ? $data["facebook_id"] : "");
        $stmt->execute();

        $userId = (int) $this->app->dbh->lastInsertId();
        if (! $userId) {
            throw new \Exception('Sign up failed, please try again.');
        }

        return $userId;
    }

    /**
     * @param $email
     * @return bool
     */
    public function emailExists($email)
    {
        $sql = "SELECT count(*)
                FROM users
                WHERE email = :email";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $count = $stmt->fetchColumn();

        return $count > 0;
    }

    /**
     * @param $email
     * @return mixed
     */
    public function getUserByEmail($email)
    {
        $sql = "SELECT *
                FROM users
                WHERE email = :email";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $user;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getUserByFacebookId($id)
    {
        $sql = "SELECT *
                FROM users
                WHERE facebook_id = :facebook_id";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':facebook_id', $id);
        $stmt->execute();

        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $user;
    }

    /**
     * @param $token
     * @return mixed
     */
    public function getUserByAuthToken($token)
    {
        $sql = "SELECT *
                FROM users
                WHERE auth_token = :auth_token";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':auth_token', $token);
        $stmt->execute();

        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $user;
    }

    /**
     * @param $facebookId
     * @param $userId
     */
    public function linkFacebookUser($facebookId, $userId)
    {
        $this->app->dbh->query(
            "UPDATE users
             SET facebook_id = " . $this->app->dbh->quote($facebookId) . "
             WHERE id = " . $this->app->dbh->quote($userId)
        );
    }

    /**
     * @param $userId
     * @param $token
     */
    public function setUserAuthToken($userId, $token)
    {
        $this->app->dbh->query(
            "UPDATE users
             SET auth_token = " . $this->app->dbh->quote($token) . "
             WHERE id = " . $this->app->dbh->quote($userId)
        );
    }

    /**
     * @param $token
     * @param $password
     */
    public function updatePasswordByToken($token, $password)
    {
        $this->app->dbh->query(
            "UPDATE users
             SET password = " . $this->app->dbh->quote(md5($password)) . "
             WHERE auth_token = " . $this->app->dbh->quote($token)
        );
    }

    /**
     * @param $userId
     * @param $password
     */
    public function updatePasswordByUserId($userId, $password)
    {
        $this->app->dbh->query(
            "UPDATE users
             SET password = " . $this->app->dbh->quote(md5($password)) . "
             WHERE id = " . $this->app->dbh->quote($userId)
        );
    }

    /**
     * @param $email
     * @param null $password
     * @return bool
     */
    public function login($email, $password=null)
    {
        $user = $this->getUserByEmail($email);

        if (!$user) {
            return false;
        }

        if (is_null($password) || md5($password) === $user['password']) {
            unset($user['password']);
            $_SESSION['user'] = $user;

            return true;
        }

        return false;
    }

    /**
     *
     */
    public function logout()
    {
        unset($_SESSION['user']);
    }

    /**
     * @param $userId
     * @return bool|mixed
     */
    public function getCurrentSubscription($userId)
    {
        $sql = "SELECT subscription.subscription_id,
                       subscription.subscription_status,
                       subscription.subscription_amount,
                       subscription.subscription_initial_amount,
                       subscription.subscription_start_date AS start_date,
                       subscription.subscription_current_billing_start AS current_billing_start,
                       subscription.subscription_next_payment AS next_payment,
                       subscription.subscription_length,
                       subscription.subscription_plan,
                       subscription.merchant_subscription_id
                FROM subscription
                WHERE subscription.user_id = :user_id
                  AND subscription.subscription_status != 'terminated'
                  AND (subscription.subscription_next_payment + 1*60) >= UNIX_TIMESTAMP()
                ORDER BY subscription.subscription_id DESC
                LIMIT 1";
        $stmt = $this->app->dbh->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();

        $subscription = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (empty($subscription)) {
            return false;
        }

        if (!$subscription['next_payment']) {
            $subscription['next_payment'] = strtotime('+1 month', $subscription['current_billing_start']);
        }
        $subscription['next_payment_formatted'] = date('j F, Y', $subscription['next_payment']);

        // Subscription plan
        $subscriptionModel = new SubscriptionModel($this->app);
        $plans = $subscriptionModel->getPlans();

        try {
            $plan = $plans->find($subscription['subscription_plan']);
        } catch (\Exception $e) {
            $plan = $subscriptionModel->getDefaultPlan();
        }
        $subscription['plan'] = $plan;

        return $subscription;
    }
}
