<?php

namespace Silvershop\Webshipper;

class SyncToWebshipper extends \BuildTask
{
    protected $title = "Sync webshop orders to Webshipper";
    protected $description = "Syncs all eligable orders to the Webshipper portal";

    public function run($request)
    {
        $SyncList = WebshipperSyncQue::get();

        if(count($SyncList)>0){
            foreach ($SyncList as $syncOrder){
                $order = \Order::get()->byID($syncOrder->OrderID);
                $success = Connector::create()->createOrder($order);

                if($success){
                    $syncOrder->delete();
                }
            }
        }
    }
}