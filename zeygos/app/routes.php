<?php
// Application routes

use App\Middleware\GuestMiddleware;
use App\Middleware\AuthMiddleware;

// General
$app->get('/', 'App\Controller\HomeController:getLanding')->setName('home');
$app->get('/terms', 'App\Controller\HomeController:getTerms')->setName('terms');
$app->get('/privacy', 'App\Controller\HomeController:getPrivacy')->setName('privacy');
$app->get('/payment/paypal/ipn', 'App\Controller\PayPalController:postIpn');
$app->post('/payment/paypal/ipn', 'App\Controller\PayPalController:postIpn');

// Guest-only
$app->group('', function () use ($app) {
    $app->get('/login', 'App\Controller\AuthController:getLogin')->setName('login');
    $app->post('/login', 'App\Controller\AuthController:postLogin');
    $app->get('/auth/facebook', 'App\Controller\AuthController:getFacebook');
    $app->get('/password-reset-request', 'App\Controller\AuthController:getPasswordResetRequest')->setName('password-reset-request');
    $app->post('/password-reset-request', 'App\Controller\AuthController:postPasswordResetRequest');
    $app->get('/password-reset', 'App\Controller\AuthController:getPasswordReset')->setName('password-reset');
    $app->post('/password-reset', 'App\Controller\AuthController:postPasswordReset');
    $app->get('/register', 'App\Controller\AuthController:getRegister')->setName('register');
    $app->post('/register', 'App\Controller\AuthController:postRegister');

})->add(new GuestMiddleware($container));


// User-only
$app->group('', function () use ($app) {
    $app->get('/logout', 'App\Controller\AuthController:getLogout')->setName('logout');
    $app->get('/dashboard', 'App\Controller\HomeController:getDashboard')->setName('dashboard');
    $app->get('/subscription', 'App\Controller\HomeController:getSubscription')->setName('subscription');
    $app->get('/change-password', 'App\Controller\HomeController:getChangePassword')->setName('change-password');
    $app->post('/change-password', 'App\Controller\HomeController:postChangePassword');
    $app->get('/subscription/checkout', 'App\Controller\SubscriptionController:getCheckout')->setName('checkout');
    $app->get('/subscription/cancel', 'App\Controller\SubscriptionController:getCancel')->setName('cancel');
    $app->get('/payment/paypal/return', '\App\Controller\PayPalController:getReturn');
})->add(new AuthMiddleware($container));
