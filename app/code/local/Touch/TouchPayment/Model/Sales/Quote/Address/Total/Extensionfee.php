<?php

class Touch_TouchPayment_Model_Sales_Quote_Address_Total_Extensionfee extends Mage_Sales_Model_Quote_Address_Total_Abstract {

    protected $_code = "touch_touchextensionfee";

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);


        $items = $address->getAllItems();
        if (!count($items)) {
            return $this; //this makes only address type shipping to come through
        }

        $quote = $address->getQuote();
        $payment = $quote->getPayment();
        $method = $payment->getMethod();

        /**
         * do we have the right method ?
         * Also, is there something about the extension days ?
         */
        if ($method == Touch_TouchPayment_Model_Payment::METHOD_TOUCH && isset($_POST['payment']['extension_days']) && $_POST['payment']['extension_days'] != 0) {
            /**
             * If so call Touch for a token and a fee
             * @todo make sure the quote values and final order values are 
             * the we need to show the fee to the customer as an additional fee
             * this will impact the database and the invoices and the order 
             * resulting from the quote
             */
             
             
             /**
              * We wanna make sure touch is only called ONCE              
              * we only call again, if the grand total has changed
              * 
              */

            $session = Mage::getSingleton('core/session');
            $buffer = $session->getTouchBuffer();

            if (!($buffer instanceof Touch_TouchPayment_Model_Data_Buffer)) {
                $buffer = new Touch_TouchPayment_Model_Data_Buffer();
            }

            /*
             *  We have to take the elements inside POST
             * Why? Because the Payment Model is called after the Address Model.
             * So the setData function of TouchPayment/Model/Sales/Payement.php is called "too late"
             * 
             */
            $extensionDays = filter_var($_POST['payment']['extension_days'], FILTER_SANITIZE_NUMBER_INT);
            // Something is wrong with the fees.
            // Calling the API and stuff
            if ($buffer->extensionDays != $extensionDays) {
                $touchApi = new Touch_TouchPayment_Model_Api_Touch();
                $extensionsrules = $touchApi->getExtensions()->result;
                foreach ($extensionsrules as $extension) {
                    if ($extension->days == $extensionDays) {
                        $extensionFee = $extension->amount;
                        break;
                    }
                }


                $buffer->extensionFee = $extensionFee;
                $buffer->extensionDays = $extensionDays;
                Mage::getSingleton('core/session')->setTouchBuffer($buffer);
            } else {
                $extensionFee = $buffer->extensionFee;
                // No need to set this one, it is already good if we are there
                //$extensionDays = $buffer->extensionDays;
            }

            $address->setTouchExtensionFeeAmount($extensionFee);
            $address->setTouchBaseExtensionFeeAmount($extensionFee);
            $address->setTouchExtensionFeeDays($extensionDays);
            $address->setTouchBaseExtensionFeeDays($extensionDays);

            $address->setGrandTotal($address->getGrandTotal() + $address->getTouchExtensionFeeAmount());
            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getTouchBaseExtensionFeeAmount());
        } else {
            $address->setTouchExtensionFeeAmount(0);
            $address->setTouchBaseExtensionFeeAmount(0);
            $address->setTouchExtensionFeeDays(0);
            $address->setTouchBaseExtensionFeeDays(0);
            Mage::getSingleton('core/session')->setTouchBuffer(new Touch_TouchPayment_Model_Data_Buffer());
        }
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amt = $address->getTouchExtensionFeeAmount();
        if (!empty($amt) && $amt > 0) {
            $address->addTotal(array(
                'code'=>$this->getCode(),
                'title' => 'Touch Payments Extension Fee', //Mage::helper($this->_code)->__('Fee'),
                'value' => $amt
            ));
        }
        return $this;
    }    
}
