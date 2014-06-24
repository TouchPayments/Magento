<?php

/**
 * SMS Input field for Touch Payment SMS Code Validation
 *
 * @copyright  2013 Touch Payments / Checkn Pay Ltd Pltd
 */
class Touch_TouchPayment_Block_Sms extends Mage_Core_Block_Template {

    /**
     * @var string 
     */
    public $errorMessage;

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

}