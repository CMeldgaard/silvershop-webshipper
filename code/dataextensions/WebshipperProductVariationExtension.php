<?php

namespace Silvershop\Webshipper;

class WebshipperProductVariationExtension extends \DataExtension
{
	public function IsShippable()
	{
		return $this->owner->Product()->IsShippable;
	}
}
