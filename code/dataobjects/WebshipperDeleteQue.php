<?php

namespace Silvershop\Webshipper;

class WebshipperDeleteQue extends \DataObject{

    private static $has_one = [
        'Order' => 'Order'
    ];

}