<?php

class Touch_TouchPayment_Model_Sales_Quote_Address_Total_Fee extends Mage_Sales_Model_Quote_Address_Total_Abstract{

    
    protected $_code = Touch_TouchPayment_Model_Payment::METHOD_TOUCH;

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);
 
        
        $this->_setAmount(0);
        $this->_setBaseAmount(0);
        $items = $this->_getAddressItems($address);
    
        if (!count($items)) {
            return $this; //this makes only address type shipping to come through
        }
        
        
        $quote = $address->getQuote();
      
        $payment = $quote->getPayment();
        $method  = $payment->getMethod();
        /**
         * do we have the right method ?
         */
        if ($method == Touch_TouchPayment_Model_Payment::METHOD_TOUCH) {
            
             
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
                $myData = Mage::getSingleton('core/session')->getBlahBlahBlah();
              */
            
            $quote->getAddressesCollection();
            
            $buffer = Mage::getSingleton('core/session')->getTouchBuffer();
            if(!($buffer instanceof Touch_TouchPayment_Model_Data_Buffer)) {
                $buffer = new Touch_TouchPayment_Model_Data_Buffer();
            } 
            $grandTotal = $address->getGrandTotal();
            if ($buffer->grandTotal != $grandTotal OR $buffer->numberOfItems != $quote->getItemsQty()) {
                
                $touchApi = new Touch_TouchPayment_Model_Api_Touch();
                $result = $touchApi->getFee($grandTotal);
                $fee = $result->result;
                
                $buffer->grandTotal = $grandTotal;
                $buffer->fee = $fee;
                $buffer->numberOfItems = $quote->getItemsQty();
                Mage::getSingleton('core/session')->setTouchBuffer($buffer);
            } else {
                $fee = $buffer->fee;
            }
            
            $address->setTouchFeeAmount($fee);
            $address->setTouchBaseFeeAmount($fee);
            $address->setGrandTotal($address->getGrandTotal() + $address->getTouchFeeAmount());
            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $address->getTouchBaseFeeAmount());
            
        } else {
            $address->setTouchFeeAmount(0);
            $address->setTouchBaseFeeAmount(0);
            Mage::getSingleton('core/session')->setTouchBuffer(new Touch_TouchPayment_Model_Data_Buffer());
        }        

    }
 
    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        $amt = $address->getTouchFeeAmount();
        if (!empty($amt) && $amt > 0) {
            $address->addTotal(array(
                    'code'=>$this->getCode(),
                    'title'=>'Touch Fee',//Mage::helper($this->_code)->__('Fee'),
                    'value'=> $amt
            ));
        }
        return $this;
    }

    
    
    
}
