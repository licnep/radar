<?php
// Application settings

//$subfolder = 'zeygos/';
$subfolder = '';

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // URLs
        'app' => [
            'www' => 'http://' . $_SERVER['HTTP_HOST'] . '/' . $subfolder,
        ],

        // View renderer settings
        'view' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Database settings
        'database' => [
            'dsn' => 'mysql:dbname=aurora;host=localhost',
            'dbhost' => 'localhost',
            'dbname' => 'aurora',
            'user' => 'root',
            'pass' => 'zxcvbnm',
        ],

        // Facebook settings
        'facebook' => [
            'app_id' => '1706368099656498',
        ],

        // Email settings
        'email' => [
            'from_email' => 'support@example.com',
            'from_name' => 'Hello Support',
            'replyto_email' => 'support@example.com',
            'replyto_name' => 'Hello Support',
        ],

        // PayPal settings
        'paypal' => [
            /*
            https://www.sandbox.paypal.com/
            ad2joe-facilitator-1@gmail.com
            12345678
            http://www.auroraft.com/zeygos/payment/paypal/ipn

            ad2joe-buyer-1@gmail.com
            12345678
            */
            'environment' => 'live',
            'sandbox' => [
//                'api_password' => 'JF36P5LH9BXUYFF8',
//                'api_username' => 'ad2joe-facilitator-1_api1.gmail.com',
//                'api_signature' => 'AFcWxV21C7fd0v3bYYYRCpSSRl31AuL9KGHe4Cfu48k7T560GKLPlF-M',
                'api_password' => 'L7JTH5B6DHJUQSVA',
                'api_username' => 'belvederesizeygos-facilitator_api1.gmail.com',
                'api_signature' => 'AFcWxV21C7fd0v3bYYYRCpSSRl31AAd9hRT.kPGJeS.ALcO.HdhCwqD6',
                'log_file' => __DIR__ . '../../logs/paypal/' . date('Y-m-d') . '_sandbox.log',
                'log_file_ipn' => __DIR__ . '../../logs/paypal/' . date('Y-m-d') . '_ipn_sandbox.log',
                'return_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/' .  $subfolder . 'payment/paypal/return',
            ],
            'live' => [
                'api_password' => '2NBE4B4KKTGAQGCF',
                'api_username' => 'belvederesizeygos_api1.gmail.com',
                'api_signature' => 'AFcWxV21C7fd0v3bYYYRCpSSRl31ASooDm3MZ4Zj5xituVV61IPwwTIt',
                'log_file' => __DIR__ . '../../logs/paypal/' . date('Y-m-d') . '.log',
                'log_file_ipn' => __DIR__ . '../../logs/paypal/' . date('Y-m-d') . '_ipn.log',
                'return_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/' .  $subfolder . 'payment/paypal/return',
            ],
        ],

        // Monolog settings
        'logger' => [
            'name' => 'aurora',
            'path' => __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
    ],
];
