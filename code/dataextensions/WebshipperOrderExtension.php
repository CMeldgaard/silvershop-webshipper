<?php

namespace Silvershop\Webshipper;

class WebshipperOrderExtension extends \DataExtension
{
	private static $db = array(
		'Droppoint'    => 'Varchar(255)'
	);

	private static $has_many = [
		'Shipments' => 'WebshipperShipment'
	];

	public function hasShippable(){

	    foreach ($this->owner->Items() as  $orderItems){
            $buyableID = $orderItems->Buyable()->ID;
            $buyableClass = $orderItems->Buyable()->ClassName;

            try{
                $shippable = $buyableClass::get()->byID($buyableID)->IsShippable();
            }catch (\Exception $e){
                continue;
            }

            if($shippable){
               return true;
            }

        }

        return false;
    }
}
