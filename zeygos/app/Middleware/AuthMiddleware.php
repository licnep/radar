<?php
namespace App\Middleware;

/**
 * Class AuthMiddleware
 * @package App\Middleware
 */
class AuthMiddleware extends Middleware
{
    /**
     * @param $request
     * @param $response
     * @param $next
     * @return mixed
     */
    public function __invoke($request, $response, $next)
    {
        if (!$this->container->user) {
            $this->container->flash->addMessage('error', 'Please sign in first!');
            return $response->withRedirect($this->container->router->pathFor('login'));
        }

        $response = $next($request, $response);

        return $response;
    }
}
