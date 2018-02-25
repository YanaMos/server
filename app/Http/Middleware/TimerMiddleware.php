<?php


namespace App\Http\Middleware;

use App\Injectable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TimerMiddleware implements MiddlewareInterface
{
    use Injectable;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->timer->startPoint('middleware');
        return $handler->process($request);
    }
}