<?php
/**
 * Hook into the invoice grand total collector
 * 
 * 
 * @author Mario Herrmann 
 */
class Touch_TouchPayment_Model_Sales_Order_Total_Invoice_Fee extends Mage_Sales_Model_Order_Invoice_Total_Abstract {

    /**
     * collect the grand totals
     * 
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return \Touch_TouchPayment_Model_Sales_Order_Total_Invoice_Fee
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        parent::collect($invoice);
        $order = $invoice->getOrder();
        $feeAmountLeft = $order->getTouchFeeAmount() - $order->getFeeAmountInvoiced();
        $baseFeeAmountLeft = $order->getTouchBaseFeeAmount() - $order->getBaseFeeAmountInvoiced();
        if (abs($baseFeeAmountLeft) < $invoice->getBaseGrandTotal()) {
            $invoice->setGrandTotal($invoice->getGrandTotal() + $feeAmountLeft);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseFeeAmountLeft);
        } else {
            $feeAmountLeft = $invoice->getGrandTotal() * -1;
            $baseFeeAmountLeft = $invoice->getBaseGrandTotal() * -1;

            $invoice->setGrandTotal(0);
            $invoice->setBaseGrandTotal(0);
        }

        $invoice->setTouchFeeAmount($feeAmountLeft);
        $invoice->setTouchBaseFeeAmount($baseFeeAmountLeft);
        return $this;
    }

}
