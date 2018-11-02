<?php

namespace Silvershop\Webshipper;

class DeleteFromWebshipper extends \BuildTask
{
    protected $title = "Delete cancelled orders in Webshipper";
    protected $description = "Deletes all order in Webshipper which has been cancelled in the webshop";

    public function run($request)
    {
        $SyncList = WebshipperDeleteQue::get();

        if(count($SyncList)>0){
            foreach ($SyncList as $syncOrder){
                $order = \Order::get()->byID($syncOrder->OrderID);
                $success = Connector::create()->deleteOrder($order);

                if($success){
                    $syncOrder->delete();
                }
            }
        }
    }
}