<?php
namespace App\Model;

/**
 * Class Subscription
 * @package App\Model
 */
class Subscription extends Base
{
    /**
     * @return Plan
     */
    public function getDefaultPlan()
    {
        $price = 20.00;
        $interval = 'monthly';
        $trialDays = 30;

        $plan = new Plan('Subscription', 'default', $interval);
        $plan->price($price)
            ->trialDays($trialDays)
            ->features(['Access to everything', ]);

        return $plan;
    }

    /**
     * @return Plan
     */
    public function getFreePlan()
    {
        $plan = new Plan('Free', 'free');
        $plan->free()
            ->features(['Free access', ]);

        return $plan;
    }

    /**
     * @return Plans
     */
    public function getPlans()
    {
        $default = $this->getDefaultPlan();

        $plans = new Plans();
        $plans->add($default);

        return $plans;
    }
}
