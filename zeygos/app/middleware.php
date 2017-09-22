<?php
// Application middleware

$app->add(new \App\Middleware\InputMiddleware($container));
$app->add(new \App\Middleware\ValidationErrorsMiddleware($container));
