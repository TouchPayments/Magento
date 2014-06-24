<?php
/**
 * Observer for certain order events like Shipment
 * and Invoice creation
 *
 * @copyright  2013 Touch Payments / Checkn Pay Ltd Pltd
 */
class Touch_TouchPayment_Model_Observer {

    /**
     * observing the Shipped event
     * sales_order_shipment_save_after
     *
     * Call Touch to confirm shipment
     *
     * @param Varien_Event_Observer $observer
     */
    public function setOrderShipped(Varien_Event_Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        //$items = $order->getItems();
        $payment = $order->getPayment();
        $method = $payment->getMethod();

        if ($method == Touch_TouchPayment_Model_Payment::METHOD_TOUCH) {

            $touchApi = new Touch_TouchPayment_Model_Api_Touch();
            $response = $touchApi->setOrderItemsShipped($order->getIncrementId());
            if (isset($response->error)) {
                $addMessage = 'Touch Payments couldn\'t set the order to shipped. ';
                if (isset($response->error->message)) {
                    $addMessage .= $response->error->message;
                }

                Mage::getSingleton('adminhtml/session')->addError($addMessage);
                throw new Exception($addMessage);
            }

        }
        return $this;
    }

    public function invoiceSaveAfter(Varien_Event_Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();
        if ($invoice->getTouchFeeAmount()) {
            $order->setFeeAmountInvoiced($order->getFeeAmountInvoiced() + $invoice->getTouchFeeAmount());
            $order->setBaseFeeAmountInvoiced($order->getBaseFeeAmountInvoiced() + $invoice->getTouchBaseFeeAmount());
        }

        if ($invoice->getTouchExtensionFeeAmount()) {
            $order->setExtensionFeeAmountInvoiced($order->getExtensionFeeAmountInvoiced() + $invoice->getTouchExtensionFeeAmount());
            $order->setBaseExtensionFeeAmountInvoiced($order->getBaseExtensionFeeAmountInvoiced() + $invoice->getTouchBaseExtensionFeeAmount());
        }

        return $this;
    }

}
