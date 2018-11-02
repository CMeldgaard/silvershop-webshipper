<?php

namespace Silvershop\Webshipper;

class WebshipperProductExtension extends \DataExtension
{
	private static $db = array(
		'IsShippable' => 'Boolean(1)'
	);

    private static $defaults = array(
        'IsShippable' => true
    );

	public function updateCMSFields(\FieldList $fields)
	{
		$fields->addFieldsToTab('Root.Webshipper', \FieldList::create(array(
            \CheckboxField::create('IsShippable', _t("Webshipper.IsShippable", "Is Shipable"))
		)));
	}

	public function IsShippable()
	{
		return $this->owner->IsShippable;
	}

	public function createWebshipperOrderItem(){
        if($this->owner->hasVariations()){
            $title = $this->owner->Product()->Title;
            $title .= ': ' . $this->owner->ProductVariation()->Title;
            $SKU = $this->owner->ProductVariation()->InternalItemID;
        }else{
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
