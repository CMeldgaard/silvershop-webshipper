<?php

namespace Silvershop\Webshipper;

use SilverStripe\Omnipay\Exception\Exception;
use SilverStripe\Omnipay\Service\CaptureService;

class WebshipperHook_Controller extends \Controller
{
    protected $hookkey;
    protected $captureOnFulfillment = false;

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * URL Handlers to route request to correct funtion based on endpoint in URL
     * */
    private static $url_handlers = array(
        'fulfillment/$ID' => 'fulfillmentHandler'
    );

    //Hook endpoints
    private static $allowed_actions = array(
        'fulfillmentHandler'
    );

    protected function initialise()
    {
        ////Get hookkey from request
        $webshipperKey = $this->getRequest()->getHeader('X-Webshipper-Hmac-Sha256');
        $webshipperContent = $this->getRequest()->getBody();
        $secretKey = $this->getHookKey();

        $hmacHash = hash_hmac('SHA256', $webshipperContent, $secretKey, true);
        $base64Encoded = base64_encode($hmacHash);

        //////Compare to key stored in config.yml
        if ($webshipperKey != $base64Encoded) {
            $this->httpError(403);
            user_error('Wrong hoookkey in request - At. ' . date('h:i:s a', time()), E_USER_WARNING);
            exit;
        }
    }

    /**
     * @param $hookkey
     * @return $this
     */
    public function setHookKey($hookkey)
    {
        $this->hookkey = $hookkey;
        return $this;
    }

    /**
     * @return string
     * Returns apptoken or sets it to config if unset.
     * */
    public function getHookKey()
    {
        if (empty($this->hookkey)) {
            $this->setHookKey(self::config()->secret_token);
        }
        return $this->hookkey;
    }

    /**
     * @param $hookkey
     * @return $this
     */
    public function setCaptureOnFullfilment($captureOnFulfillment)
    {
        $this->captureOnFulfillment = $captureOnFulfillment;
        return $this;
    }

    /**
     * @return string
     * Returns apptoken or sets it to config if unset.
     * */
    public function getCaptureOnFullfilment()
    {
        if (empty($this->captureOnFulfillment)) {
            $this->setCaptureOnFullfilment(self::config()->captureOnFulfillment);
        }
        return $this->captureOnFulfillment;
    }

    public function fulfillmentHandler(\SS_HTTPRequest $request)
    {
        $this->initialise();

        $fullfillmentData = json_decode($this->getRequest()->getBody(), true);
        $orderID = $fullfillmentData['data']['attributes']['reference'];
        $shipments = $fullfillmentData['data']['attributes']['tracking_links'];

        $this->extend('onWebshipperWebhook', $orderID);

        $order = \Order::get()->Filter('ID', $orderID)->Sort('Created DESC')->First();

        if ($order) {

            ////Get T&T data
            //$trackTraceID = $fullfillmentData['order']['fulfillments']['tracking_no'];
            //$tracking_url = $fullfillmentData['order']['fulfillments']['tracking_url'];

            ////Set T&T info on order
            //$order->TrackTraceID = $trackTraceID;
            //$order->TrackTraceURL = $tracking_url;

            //Updat order to sent
            $order->Status = 'Sent';

            if($this->getCaptureOnFullfilment()){
                //IF statement to prevent capture from config settings
                $payment = \Payment::get()->filter('OrderID', $order->ID)->first();

                $data = [
                    'transactionReference' => $payment->TransactionReference
                ];

                $member = \Member::get()
                    ->leftJoin('Group_Members','Group_Members.MemberID = Member.ID')
                    ->leftJoin('Group','Group.ID = Group_Members.GroupID')
                    ->filter('Code','administrators')->first();

                /* Log in with any admin user we can find */
                \Session::set('loggedInAs', $member->ID);

                //Capture omnipay payment
                try{
                    $capture = CaptureService::create($payment)->initiate($data);
                }catch (Exception $e){
                    //Handle payment error, fx send email to shop owner
                    $order->write();
                    /* Log the user out */
                    \Session::set('loggedInAs', null);
                    return;
                }

                /* Log the user out */
                \Session::set('loggedInAs', null);

                $order->Status = 'Complete';
            }

            $order->write();
        }

        $this->extend('onAfterFulfillment', $order, $fullfillmentData);
    }
}
