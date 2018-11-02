<?php

class WebshipperPaymentExtension extends DataExtension{

    public function canCapture($member){
        return true;
    }

}