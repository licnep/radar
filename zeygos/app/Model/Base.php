<?php
namespace App\Model;

/**
 * Class Base
 * @package App\Model
 */
class Base
{
    public $app;

    /**
     * Base constructor.
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }
}