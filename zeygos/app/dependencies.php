<?php
// DIC configuration

$container = $app->getContainer();

// Flash messages
$container['flash'] = function ($c) {
    return new Slim\Flash\Messages;
};

// Flash messages
$container['user'] = function ($c) {
    return isset($_SESSION['user']) ? $_SESSION['user'] : [];
};

// View renderer
$container['view'] = function ($c) {
    $settings = $c->get('settings');

    $attributes = [
        'flash' => $c['flash'],
        'user' => $c['user'],
        'www' => $settings['app']['www'],
    ];

    return new Slim\Views\PhpRenderer($settings['view']['template_path'], $attributes);
};

// Database
$container['dbh'] = function ($c) {
    $settings = $c->get('settings');

    try {
        $dbh = new \PDO($settings['database']['dsn'], $settings['database']['user'], $settings['database']['pass'], array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_PERSISTENT => false,
            \PDO::ATTR_EMULATE_PREPARES => true,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ));
    } catch (\PDOException $e) {
        throw new \Exception('An error occurred while trying to process your request. Please try again later.');
    }

    return $dbh;
};

// Monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings');
    $logger = new Monolog\Logger($settings['logger']['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['logger']['path'], $settings['logger']['level']));
    return $logger;
};

// Validator
$container['validator'] = function ($c) {
    return new App\Validation\Validator;
};

// CSRF protection
$container['csrf'] = function($c) {
    return new \Slim\Csrf\Guard;
};

/**
 * @param $data
 * @param bool $dump
 */
function debug($data, $dump=false)
{
    echo '<pre>';
    if (!$dump) {
        print_r($data);
    } else {
        var_dump($data);
    }
    echo '</pre>';
}
