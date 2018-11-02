<?php

namespace Silvershop\Webshipper;

class WebshipperSyncQue extends \DataObject{

    private static $has_one = [
    	'Order' => 'Order'
    ];

}