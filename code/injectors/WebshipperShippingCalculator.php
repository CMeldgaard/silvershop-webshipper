<?php

namespace Silvershop\Webshipper;

/**
 * Helper class for encapsulating shipping calculation logic.
 */
class WebshipperShippingCalculator
{
    protected $method;
    protected $order;

    public function __construct(\ShippingMethod $method, \Order $order)
    {
        $this->method = $method;
        $this->order = $order;
    }

    public function calculate($address = null)
    {
        if ($this->method
            && (float)$this->method->FreeQuota
            && (float)$this->method->FreeQuota <= $this->order->SubTotal()) {
            return 0;
        }

        return $this->method->calculateRate(
            $this->order->createShippingPackage(),
            $address ? $address : $this->order->getShippingAddress()
        );
    }
}