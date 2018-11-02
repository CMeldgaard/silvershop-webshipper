<?php

namespace Silvershop\Webshipper;

use Silvershop\Webshipper\OptionsetField;

/**
 * @package silvershop-shipping
 */
class WebshipperShippingCheckoutComponent extends \CheckoutComponent
{
    public function getFormFields(\Order $order, $zip = null)
    {
        //Current cart
        $cart = \ShoppingCart::curr();
        //Current shippingmethod
        $shippingMethod = $cart->ShippingMethod();

        $fields = new \FieldList();
        $estimates = $order->getShippingEstimates();

        if ($estimates->exists()) {
            $options = array();
            foreach ($estimates->filter('ClassName', 'Silvershop\Webshipper\WebshipperShippingMethod')->sort('Price DESC') as $estimate) {

                if ($shippingMethod->ID == $estimate->ID) {
                    $selected = true;
                }else {
                    $selected = false;
                }

                $options[$estimate->ID] = array(
                    'title'     => $estimate->Name,
                    'price'     => $estimate->ShippingPrice(),
                    'droppoint' => $estimate->IsDropPoint,
                    'selected'  => $selected,
                    'test' => 'test'
                );
            }

            //Get shipping methods thats not from Webshipper
            foreach ($estimates->exclude('ClassName', 'Silvershop\Webshipper\WebshipperShippingMethod') as $estimate) {

               $options[$estimate->ID] = array(
                    'title'    => $estimate->Name,
                    'price'    => $estimate->Price
                );
            }

            $fields->push(
                OptionsetField::create(
                    "ShippingMethodID",
                    _t('ShippingCheckoutComponent.ShippingOptions', 'Shipping Options'),
                    $options
                )->setFieldHolderTemplate('WebshipperOptionsetField_Holder')
                 ->setTemplate('WebshipperOptionsetField')
            );

            $fields->push(
                $dropppoint = \DropdownField::create('WebshipperDroppoint', 'WebshipperDroppoint',
                    $this->getWebshipperDroppoints())->setEmptyString('Vælg Pakkeshop')
            );

            if (\Session::get('droppoints')) {

                $zipcode = \Session::get('droppoints');
                $rateID = \Session::get('droppointsRateID');
                $country = \Session::get('droppointsCountry');

                $dropppoint->setSource($this->getWebshipperDroppoints($zipcode,$rateID,$country));
            }

        }

        return $fields;
    }

    public function getWebshipperDroppoints($zipCode=null,$rateID=null,$country=null)
    {
        if ($zipCode) {
            $dropList = [];
            $droppoints = Connector::create()->getDropPoints($zipCode,$country,$rateID);

            if($droppoints){
                foreach ($droppoints as $droppoint){
                    $dropList[$droppoint['drop_point_id']] = $droppoint['name'].', ' . $droppoint['address_1'] . ' ' . $droppoint['zip'] . ' ' . $droppoint['city'];
                }

                return $dropList;
            }
        }
        return array();
    }

    public function getRequiredFields(\Order $order)
    {
        return array();
    }

    public function validateData(\Order $order, array $data)
    {

        $result = new \ValidationResult();
        if (!isset($data['ShippingMethodID'])) {
            $result->error(
                _t('ShippingCheckoutComponent.ShippingMethodNotProvidedMessage', "Shipping method not provided"),
                _t('ShippingCheckoutComponent.ShippingMethodErrorCode', "ShippingMethod")
            );
            throw new \ValidationException($result);
        }

        if (!\ShippingMethod::get()->byID($data['ShippingMethodID'])) {
            $result->error(
                _t('ShippingCheckoutComponent.ShippingMethodDoesNotExistMessage', "Shipping Method does not exist"),
                _t('ShippingCheckoutComponent.ShippingMethodErrorCode', "ShippingMethod")
            );
            throw new \ValidationException($result);
        }
    }

    public function getData(\Order $order)
    {
        $estimates = $order->getShippingEstimates();
        $method = count($estimates) === 1 ? $estimates->First() : \Session::get("Checkout.ShippingMethod");

        return array(
            'ShippingMethod' => $method
        );
    }

    public function setData(\Order $order, array $data)
    {
        $option = null;
        if (isset($data['ShippingMethodID'])) {
            $option = \ShippingMethod::get()
                ->byID((int)$data['ShippingMethodID']);
        }
        //assign option to order / modifier
        if ($option) {
            $order->setShippingMethod($option);
            if ($data['WebshipperDroppoint']) {
                $order->DroppointID = $data['WebshipperDroppoint'];
            }
            //Store WebshipperRateID
            $order->WebshipperRateID = $order->ShippingMethod()->WebshipperRateID;
            \Session::set("Checkout.ShippingMethod", $option);
        }
    }


    /**
     * @return string
     */
    public function name()
    {
        $name = new \ReflectionClass($this); //Get class without namespace. (replacement for parent function which usesget_class())
        return $name->getShortName(); //Seems complicated but according to this it is faster: https://coderwall.com/p/cpxxxw/php-get-class-name-without-namespace
    }
}
