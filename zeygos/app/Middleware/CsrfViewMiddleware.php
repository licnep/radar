<?php
namespace App\Middleware;

/**
 * Class CsrfViewMiddleware
 * @package App\Middleware
 */
class CsrfViewMiddleware extends Middleware
{
    /**
     * @param $request
     * @param $response
     * @param $next
     * @return mixed
     */
    public function __invoke($request, $response, $next)
    {
        $this->container->view->getEnvironment()->addGlobal('csrf', [
            'field' => '
				<input type="hidden" name="' . $this->container->csrf->getTokenNameKey() . '"
				 value="' . $this->container->csrf->getTokenName() . '">
				<input type="hidden" name="' . $this->container->csrf->getTokenValueKey() . '"
				 value="' . $this->container->csrf->getTokenValue() . '">
			',
        ]);

        $response = $next($request, $response);
        return $response;

    }
}