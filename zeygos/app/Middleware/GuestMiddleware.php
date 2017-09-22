<?php
namespace App\Middleware;

/**
 * Class GuestMiddleware
 * @package App\Middleware
 */
class GuestMiddleware extends Middleware
{
    /**
     * @param $request
     * @param $response
     * @param $next
     * @return mixed
     */
    public function __invoke($request, $response, $next)
    {
        if ($this->container->user) {
            return $response->withRedirect($this->container->router->pathFor('home'));
        }

        $response = $next($request, $response);

        return $response;
    }
}
