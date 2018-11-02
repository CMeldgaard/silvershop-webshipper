<?php

class WebshipperShipment extends DataObject{
    private static $db = [
    	'Link' => 'Varchar'
    ];

    private static $has_one = [
    	'Order' => 'Order'
    ];
}