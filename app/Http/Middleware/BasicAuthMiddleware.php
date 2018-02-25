<?php


namespace App\Http\Middleware;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;


class BasicAuthMiddleware implements MiddlewareInterface
{
    private $options = [
        'user' => null,
        'password' => null,
    ];

    public function __construct(array $options = [])
    {
        $this->hydrate($options);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->authenticate($request)){
            return new Response(401, [], 'Access denied');
        }
        return $handler->handle($request);
    }

    public function setUser(string $user)
    {
        $this->options['user'] = $user;
        return $this;
    }

    public function setPassword(string $password)
    {
        $this->options['password'] = $password;
        return $this;
    }

    private function authenticate(ServerRequestInterface $request):bool
    {
        $serverParams = $request->getServerParams();
        $user = $serverParams['PHP_AUTH_USER'] ?? null;
        $password = $serverParams['PHP_AUTH_PW'] ?? null;

        if ($user !== $this->options['user'] || $password !== $this->options['password']){
            return false;
        }

        return true;
    }

    private function hydrate(array $data)
    {
        foreach ($data as $key => $value) {
            $method = "set" . ucfirst($key);
            if (method_exists($this, $method)) {
                call_user_func([$this, $method], $value);
            }
        }
    }
}