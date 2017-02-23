<?php
define('APP_START_TIME', microtime(true));

require __DIR__ . '../../vendor/autoload.php';

$config = require __DIR__ . '/../config/env.php';
$dependencies = require __DIR__ . '/../app/dependencies.php';
$middleware = require __DIR__ . '/../app/middleware.php';
$routes = require __DIR__ . '/../app/routes.php';

$app = new \App\App();
$app->init($config, $dependencies);
$app->runHttp($middleware, $routes);