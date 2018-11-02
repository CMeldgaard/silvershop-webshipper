<?php

namespace Silvershop\Webshipper;

class WebshipperProductOrderItemExtension extends \DataExtension
{
    public function createWebshipperOrderItem()
    {
        if (method_exists($this->owner,'hasVariations') && $this->owner->hasVariations()) {
            $title = $this->owner->Product()->Title;
            $title .= ': ' . $this->owner->ProductVariation()->Title;
            $SKU = $this->owner->ProductVariation()->InternalItemID;
        }else {
            $title = $this->owner->Product()->Title;
            $SKU = $this->owner->Product()->InternalItemID;
        }

        $itemArray = [
            'description' => $title,
            'sku'         => (int)$SKU,
            'quantity'    => (int)$this->owner->Quantity,
            'unit_price'  => (int)$this->owner->UnitPrice
        ];

        return $itemArray;
    }
}
