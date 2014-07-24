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
        $payment = $order->getPayment();
        $method = $payment->getMethod();

        if (in_array($method, array(Touch_TouchPayment_Model_Payment::METHOD_TOUCH, Touch_TouchPayment_Model_Express::METHOD_TOUCH))) {

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

    public function setTouchOrderPending(Varien_Event_Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        $order   = $payment->getOrder();
        $method  = $payment->getMethod();

        if (in_array($method, array(Touch_TouchPayment_Model_Payment::METHOD_TOUCH))) {
            $order->setState(Mage_Sales_Model_Order::STATE_NEW, true)->save();
        }
    }

    public function autoCancelPendingOrders()
    {
        $orderCollection = Mage::getResourceModel('sales/order_collection');

        $orderCollection
            ->addFieldToFilter('status', 'pending')
            ->addFieldToFilter('state', 'new')
            ->addFieldToFilter('created_at', array(
                    'lt' =>  new Zend_Db_Expr("DATE_ADD('".now()."', INTERVAL -'60:00' HOUR_MINUTE)")))
            ->addFieldToFilter('sales_flat_order_payment.method', Touch_TouchPayment_Model_Payment::METHOD_TOUCH)
            ->getSelect()
            ->join('sales_flat_order_payment', 'main_table.entity_id=sales_flat_order_payment.entity_id', false, null, 'inner')
            ->limit(40);

        foreach($orderCollection->getItems() as $order) {

            $orderModel = Mage::getModel('sales/order');
            $orderModel->load($order['entity_id']);

            if ($orderModel->canCancel()) {
                $orderModel->registerCancellation('Touch Payments - Order has timed out');
                $orderModel->save();
            }
        }

    }

}
