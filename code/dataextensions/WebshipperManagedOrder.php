<?php

namespace Silvershop\Webshipper;

/**
 * Sends order to Webshipper after order have been purchased
 *
 * @package silvershop-stock
 */
class WebshipperManagedOrder extends \DataExtension
{
	private static $db = array(
		'WebshipperID'          => 'Int',
		'WebshipperRateID'      => 'Int',
		'TrackTraceID'        => 'Varchar(255)',
		'TrackTraceURL'       => 'Text',
		'DroppointID'         => 'Varchar(10)'
	);

	public function onBeforeDelete()
	{
		parent::onBeforeDelete();
		if ($this->owner->WebshipperID) {
			$this->deleteOrder();
		}
	}

	public function onStatusChange($fromStatus, $toStatus)
	{
		//Customer completes checkout with either paymentcart og invoice
		if ($fromStatus == 'Cart' && ($toStatus == 'Unpaid' || $toStatus == 'Paid')) {
			$sendToWebshipper = true;
			$this->owner->extend('onBeforeWebshipper', $sendToWebshipper);
			if($sendToWebshipper){
                $this->sendToWebshipper();
            }
		}

		if ($toStatus == 'AdminCancelled' || $toStatus == 'MemberCancelled') {
			$this->deleteOrder();
		}
	}

	public function sendToWebshipper()
	{
		$order = $this->owner;

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
                //Add to que
                $SyncQue = WebshipperSyncQue::create();
                $SyncQue->OrderID = $order->ID;
                $SyncQue->write();
                break;
            }
		}
	}

	public function deleteOrder()
	{
        $order = $this->owner;
        $SyncQue = WebshipperDeleteQue::create();
        $SyncQue->OrderID = $order->ID;
        $SyncQue->write();
	}
}
