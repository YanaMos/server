<?php use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

return [
    new \App\Http\Middleware\TimerMiddleware(),

    // CORS
    function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
        /* @var \Psr\Http\Message\ResponseInterface $response */
        if ($request->getMethod() === 'OPTIONS') {
            $response = new \GuzzleHttp\Psr7\Response();
        } else {
            $response = $handler->handle($request);
        }

        $headers = [
            'Origin',
            'X-Requested-With',
            'Content-Range',
            'Content-Disposition',
            'Content-Type',
            'Authorization',
            'Accept',
            'Client-Security-Token',
            'X-CSRFToken',
        ];

        $method = [
            'POST',
            'GET',
            'OPTIONS',
            'DELETE',
            'PUT'
        ];

        $response = $response
            ->withHeader("Access-Control-Allow-Origin", '*')
            ->withHeader("Access-Control-Allow-Methods", implode(',', $method))
            ->withHeader("Access-Control-Allow-Headers", implode(',', $headers))
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withHeader("Access-Control-Allow-Credentials", 'true');

        return $response;
    },


    // Error handler
    function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler());
        $whoops->register();

        return $handler->handle($request);
    },

    new \App\Http\Middleware\BasicAuthMiddleware([
        'user' => getenv('BASIC_AUTH_LOGIN'),
        'password' => getenv('BASIC_AUTH_PASS'),
    ]),
//    new \Middlewares\FastRoute(require __DIR__ . '/routes.php', '\App\Controllers\NotFoundController::showMessage'),
    new \Middlewares\FastRoute(\FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
        $routes = require __DIR__ . '/routes.php';
        foreach ($routes as $route) {
            $r->addRoute($route[0], $route[1], $route[2]);
        }
    })),
    new \App\Http\Middleware\RequestHandlerMiddleware(),
];