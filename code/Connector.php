<?php

namespace Silvershop\Webshipper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class Connector extends \Object
{

    protected $headers;
    protected $client;

    public function __construct()
    {
        parent::__construct();

        $token = self::config()->secret_token;

        $this->client = new Client(array('base_uri' => self::config()->baseURI));

        $this->headers = [
            'Authorization' => 'Bearer ' . $token,
            'content-type'  => 'application/vnd.api+json',
            'Accept'        => 'application/vnd.api+json'
        ];
    }

    public function getShippingRates()
    {
        try{
            $request = $this->client->request('GET', 'shipping_rates?include=carrier', [
                'headers' => $this->headers
            ]);
        }catch (BadResponseException $e){
            return false;
        }

        $rates = $request->getBody()->getContents();

        $decoded = json_decode($rates, true);

        return $decoded['data'];
    }

    public function getDropPoints($zipCode, $country, $shippingRateID = null, $carrierID = null, $serviceCode = null)
    {

        try{
            $request = $this->client->request('POST', 'drop_point_locators', [
                'headers' => $this->headers,
                'json'    => [
                    'data' => [
                        'type'       => 'drop_point_locators',
                        'attributes' => [
                            'carrier_id'       => $carrierID,
                            'service_code'     => $serviceCode,
                            'shipping_rate_id' => $shippingRateID,
                            'delivery_address' => [
                                'zip'          => $zipCode,
                                'country_code' => $country
                            ]
                        ]
                    ]
                ]
            ]);
        }catch (BadResponseException $e){
            return false;
        }

        $rates = $request->getBody()->getContents();
        $decoded = json_decode($rates, true);

        return $decoded['data']['attributes']['drop_points'];
    }


    public function createOrder($order, $customOrderId = null)
    {
        $shopConfig = new \ShopConfig();

        //Create billing info
        $billingAddress = [
            'address_1'    => $order->BillingAddress()->Address,
            'city'         => $order->BillingAddress()->City,
            'att_contact'  => $order->FirstName . ' ' . $order->Surname,
            'country_code' => $order->BillingAddress()->Country,
            'phone'        => $order->BillingAddress()->Phone,
            'zip'          => $order->BillingAddress()->PostalCode,
            'email'        => $order->Email,
            'company_name' => $order->CompanyName
        ];

        if ($order->SeparateBillingAddress) {
            //Create delivery info
            $deliveryAddress = [
                'address_1'    => $order->ShippingAddress()->Address,
                'city'         => $order->ShippingAddress()->City,
                'att_contact'  => $order->ShippingAddress()->FirstName . ' ' . $order->ShippingAddress()->Surname,
                'country_code' => $order->ShippingAddress()->Country,
                'phone'        => $order->ShippingAddress()->Phone,
                'zip'          => $order->ShippingAddress()->PostalCode,
                'email'        => $order->Email,
                'company_name' => $order->ShippingAddress()->CompanyName
            ];
        }else {
            $deliveryAddress = $billingAddress;
        }


        //Create order items
        $orderItems = [];
        foreach ($order->Items() as $orderItem) {

            $buyableID = $orderItem->Buyable()->ID;
            $buyableClass = $orderItem->Buyable()->ClassName;

            try{
                $shippable = $buyableClass::get()->byID($buyableID)->IsShippable();
            }catch (\Exception $e){
                //Not a shippable product
                continue;
            }

            if($shippable){
                $orderItems[] = $orderItem->createWebshipperOrderItem();
            }
        }

        $shippingData = [
            'data' => [
                'type'          => 'orders',
                'attributes'    => [
                    'ext_ref'          => $customOrderId ? $customOrderId : $order->ID,
                    'billing_address'  => $billingAddress,
                    'delivery_address' => $deliveryAddress,
                    'order_lines'      => $orderItems,
                    'currency'         => $shopConfig::config()->base_currency,
                    'external_comment' => $order->Notes
                ],
                'relationships' => [
                    'order_channel' => [
                        'data' => [
                            'id'   => self::config()->channelID,
                            'type' => 'order_channels'
                        ]
                    ],
                    'shipping_rate' => [
                        'data' => [
                            'id'   => $order->WebshipperRateID,
                            'type' => 'shipping_rates'
                        ]
                    ]
                ]
            ]
        ];

        if ($order->DroppointID) {

            $dropPoint = $this->getDropPointData($deliveryAddress['zip'], $order->DroppointID, $deliveryAddress['country_code'], $order->WebshipperRateID);

            $dynamicAddress = array(
                'drop_point_id' => $order->DroppointID,
                'name'          => $dropPoint['name'],
                'address_1'     => $dropPoint['address_1'],
                'zip'           => $dropPoint['zip'],
                'city'          => $dropPoint['city'],
                'country_code'  => $dropPoint['country_code']
            );

            //Insert into shipping data
            $shippingData['data']['attributes']['drop_point'] = $dynamicAddress;
        }

        try{
            $response = $this->client->request('POST', 'orders', ['headers' => $this->headers, 'json' => $shippingData]);
        }catch (BadResponseException $e){
            return false;
        }

        $this->extend('onAfterWebshipperSync', $order);

        $decoded = json_decode($response->getBody()->getContents(), true);

        $this->updateWebshipperOrderID($order, $decoded['data']['id']);

        return true;
    }


    public function getDropPointData($zipCode, $dropPointID, $country, $shippingRateID = null, $carrierID = null, $serviceCode = null)
    {
        $dropPoints = $this->getDropPoints($zipCode, $country, $shippingRateID, $carrierID, $serviceCode);

        $key = array_search($dropPointID, array_column($dropPoints, 'drop_point_id'));

        return $dropPoints[$key];
    }


    //OLD API FUNCTIONS
    public function deleteOrder($order)
    {
        $webshipperID = $order->WebshipperID;

        try{
            $request = $this->client->request('DELETE', 'orders/' . $webshipperID, [
                'headers' => $this->headers
            ]);
        }catch (BadResponseException $e){
            return false;
        }

        return true;
    }

    public function updateWebshipperOrderID($order, $WebshipperID)
    {
        $order->WebshipperID = (int)$WebshipperID;
    }
}
