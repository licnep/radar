<?php
namespace App\Middleware;

/**
 * Class InputMiddleware
 * @package App\Middleware
 */
class InputMiddleware extends Middleware
{
    /**
     * @param $request
     * @param $response
     * @param $next
     * @return mixed
     */
    public function __invoke($request, $response, $next)
    {
        $this->container->view->addAttribute('input', isset($_SESSION['input']) ? $_SESSION['input'] : '');

        $_SESSION['input'] = $request->getParams();

        $response = $next($request, $response);

        return $response;
    }
}
