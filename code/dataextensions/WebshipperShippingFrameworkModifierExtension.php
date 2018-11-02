<?php

namespace Silvershop\Webshipper;

class WebshipperShippingFrameworkModifierExtension extends \DataExtension
{
    public function updateTableTitle(&$title)
    {
        $order = $this->owner->Order();

        if ($order->Droppoint) {
            $title = rtrim($title, ')');
            $title .= ': ' . $order->Droppoint . ')';
        }
    }
}
