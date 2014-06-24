<?php
/**
 * Hook into the invoice grand total collector
 * 
 * 
 * @author Mario Herrmann 
 */
class Touch_TouchPayment_Model_Sales_Order_Total_Invoice_Extensionfee extends Mage_Sales_Model_Order_Invoice_Total_Abstract {

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

        $extensionFeeAmountLeft = $order->getTouchExtensionFeeAmount() - $order->getExtensionFeeAmountInvoiced();
        $baseExtensionFeeAmountLeft = $order->getTouchBaseExtensionFeeAmount() - $order->getBaseExtensionFeeAmountInvoiced();
        if (abs($baseExtensionFeeAmountLeft) < $invoice->getBaseGrandTotal()) {
            $invoice->setGrandTotal($invoice->getGrandTotal() + $extensionFeeAmountLeft);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseExtensionFeeAmountLeft);
        } else {
            $extensionFeeAmountLeft = $invoice->getGrandTotal() * -1;
            $baseExtensionFeeAmountLeft = $invoice->getBaseGrandTotal() * -1;

            $invoice->setGrandTotal(0);
            $invoice->setBaseGrandTotal(0);
        }

        $invoice->setTouchExtensionFeeAmount($extensionFeeAmountLeft);
        $invoice->setTouchBaseExtensionFeeAmount($baseExtensionFeeAmountLeft);
        return $this;
    }

}
