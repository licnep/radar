<?php
namespace App\Controller;

/**
 * Class BaseController
 * @package App\Controller
 */
class BaseController
{
    protected $container;

    /**
     * BaseController constructor.
     * @param $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @param $property
     * @return mixed
     */
    public function __get($property)
    {
        if ($this->container->{$property}) {
            return $this->container->{$property};
        }
    }
}