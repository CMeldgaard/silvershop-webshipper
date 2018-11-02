<?php

namespace Silvershop\Webshipper;

class WebshipperCheckoutPage_ControllerExtension extends \DataExtension
{
    private static $allowed_actions = array(
        'updateOrderTotal',
        'getDroppoints'
    );

    public function getDroppoints(\SS_HTTPRequest $request)
    {
        if ($request->isAjax()) {

            $zipCode = $request->postVar('zip');
            $country = $request->postVar('country');

            if($country === NULL){
                $country = \Config::inst()->get('Silvershop\Webshipper\Connector', 'baseCountry');
            }

            $selectedShipping = $request->postVar('selectedShipping');

            $shippingMethod = \ShippingMethod::get()->Filter('ID', $selectedShipping)->Sort('Created DESC')->First();

            $droppoints = Connector::create()->getDropPoints($zipCode, $country, $shippingMethod->WebshipperRateID);

            \Session::set('droppoints',$zipCode);
            \Session::set('droppointsRateID',$shippingMethod->WebshipperRateID);
            \Session::set('droppointsCountry',$country);

            header('Content-Type: application/json');
            return json_encode($droppoints);
        }
    }

    public function updateOrderTotal(\SS_HTTPRequest $request)
    {
        if ($request->isAjax()) {
            $cart = \ShoppingCart::curr();
            $id = $request->postVar('id');

            $cart->setShippingMethod(\ShippingMethod::get()->byID($id));
            $cart->write();
            $cart->calculate();

            return $cart->renderWith('Cart');
        }
    }
}
