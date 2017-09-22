<?php
namespace App\Model;

use Countable;
use Exception;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;

/**
 * Class Plans
 * @package App\Model
 */
class Plans implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * All of the defined plans.
     *
     * @var array
     */
    protected $plans = array();

    /**
     * Create a new plan collection instance.
     *
     * @param array $plans
     */
    public function __construct(array $plans = array())
    {
        $this->plans = $plans;
    }

    /**
     * Create a new plan instance.
     *
     * @param $name
     * @param $id
     * @param $interval
     * @return Plan
     */
    public function create($name, $id, $interval)
    {
        return $this->add(new Plan($name, $id, $interval));
    }

    /**
     * Get plan matching a given ID.
     *
     * @param $id
     * @return Plan
     * @throws Exception
     */
    public function find($id)
    {
        foreach ($this->plans as $plan) {
            if ($plan->id === $id) {
                return $plan;
            }
        }

        throw new Exception("Unable to find plan with ID [{$id}].");
    }

    /**
     * Add a plan to the plan collection.
     *
     * @param  Plan $plan
     * @return Plan
     */
    /**
     * @param Plan $plan
     * @return Plan
     */
    public function add(Plan $plan)
    {
        $this->plans[] = $plan;

        return $plan;
    }

    /**
     * Determine if the plan collection has paid plans.
     *
     * @return bool
     */
    public function hasPaidPlans()
    {
        return count($this->paid()) > 0;
    }

    /**
     * Get all of the plans that require payment (price > 0).
     *
     * @return Plans
     */
    public function paid()
    {
        return new Plans(array_values(array_filter($this->plans, function ($plan) {
            return $plan->price > 0;
        })));
    }

    /**
     * Get all of the monthly plans for a given tier.
     *
     * @param $tier
     * @return Plans
     */
    public function tier($tier)
    {
        return new Plans(array_values(array_filter($this->plans, function ($plan) use ($tier) {
            return $plan->tier === $tier;
        })));
    }

    /**
     * Get all of the monthly plans for the application.
     *
     * @return Plans
     */
    public function monthly()
    {
        return new Plans(array_values(array_filter($this->plans, function (Plan $plan) {
            return $plan->isMonthly();
        })));
    }

    /**
     * Get all of the yearly plans for the application.
     *
     * @return Plans
     */
    public function yearly()
    {
        return new Plans(array_values(array_filter($this->plans, function (Plan $plan) {
            return $plan->isYearly();
        })));
    }

    /**
     * Get all of the plans that are active.
     *
     * @return Plans
     */
    public function active()
    {
        return new Plans(array_values(array_filter($this->plans, function (Plan $plan) {
            return $plan->isActive();
        })));
    }

    /**
     * Determine the number of plans in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->plans);
    }

    /**
     * Get an iterator for the collection.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->plans);
    }

    /**
     * Get the JSON serializable fields for the object.
     *
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->plans;
    }
}
