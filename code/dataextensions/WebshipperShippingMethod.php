<?php

namespace Silvershop\Webshipper;

class WebshipperShippingMethod extends \ShippingMethod
{
    private static $db = array(
        'Price'            => 'Currency',
        'WebshipperRateID' => 'Varchar(50)',
        'IsDropPoint'      => 'Boolean',
        'FreeQuota'        => 'Currency'
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $rates = Connector::create()->getShippingRates();

        $WebshipperRates = [];

        foreach ($rates as $rate) {
            $WebshipperRates[$rate['id']] = $rate['attributes']['name'];
        }

        $fields->addFieldsToTab('Root.Main', array(
            \CurrencyField::create('Price', 'Pris'),
            \DropdownField::create('WebshipperRateID', 'Webshipper rate', $WebshipperRates),
            \CheckboxField::create('IsDropPoint', 'Vis pakkeshop vælger'),
            \CurrencyField::create('FreeQuota', 'Gratis hvis subtotal er større end:')->setDescription('DKK 0.00 = ')
        ));

        return $fields;
    }

    /**
     * @return
     * Returns price of shipping method. (Used for checkoutpage formfield)
     */
    public function ShippingPrice()
    {
        if (!(float)$this->FreeQuota) {
            return $this->Price;
        }

        $cart = \Controller::curr()->Cart();

        //If subtotal is bigger than FreeQuota, its free
        if ((float)$this->FreeQuota <= $cart->SubTotal()) {
            return 0;
        }

        return $this->Price;
    }

    public function calculateRate(\ShippingPackage $package, \Address $address)
    {
        return $this->Price;
    }

    public function getCalculator(\Order $order)
    {
        return new WebshipperShippingCalculator($this, $order);
    }
}
