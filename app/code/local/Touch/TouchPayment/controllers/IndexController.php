<?php

/**
 * Controller which will be redirected to from Touch's website
 *
 * @copyright  2013 Touch Payments / Checkn Pay Ltd Pltd
 */
class Touch_TouchPayment_IndexController extends Mage_Core_Controller_Front_Action
{


    public function smsAction()
    {
        $this->loadLayout();

        $session = Mage::getSingleton('checkout/session');
        $token = $session['touchToken'];
        $order = Mage::getModel('sales/order')->loadByAttribute('touch_token', $token);
        $this->getLayout()->getBlock('sms')->assign('order', $order);


        if ($this->getRequest()->isPost()) {
            $code = $this->getRequest()->getPost('smsCode');
            if (!empty($code) && strlen($code) == 6 && is_numeric($code)) {
                /**
                 * Approve the order via SMS
                 */

                $result = $this->_approveTouchOrderViaSMSCode($order, $code);
                if (isset($result->error->code) && Touch_ErrorCodes::ERR_WRONG_SMS_CODE == $result->error->code) {
                    $this->getResponse()->setBody(
                        Mage::helper('core')->jsonEncode(
                            array(
                                'success'      => false,
                                'error'        => true,
                                'responseText' => $result->error->message
                            )
                        )
                    );
                    return;
                } else {
                    $this->_handleTouchApprovalResponse($order, $result);
                    return;
                }


            } else {

                $this->getResponse()->setBody(
                    Mage::helper('core')->jsonEncode(
                        array(
                            'success'      => false,
                            'error'        => true,
                            'responseText' => 'The code is incorrect, please try again.'
                        )
                    )
                );

                return;
            }
        }

        /**
         * load the layout design/frontend/default/default/layout/touch.xml
         */

        $this->getResponse()->setBody(
            Mage::helper('core')->jsonEncode(
                array(
                    'success'      => true,
                    'error'        => false,
                    'responseText' => $this->getLayout()->getBlock('sms')->toHtml()
                )
            )
        );
    }

    public function indexAction()
    {

        $token = $this->getRequest()->getParam('token');
        $order = Mage::getModel('sales/order')->loadByAttribute('touch_token', $token);
        $result = $this->_getTouchStatus($order);
        // Check if we have what's needed in the session (we might be confirming from phone after having
        // checked out from desktop
        $session = Mage::getSingleton('checkout/type_onepage')->getCheckout();

        if (!$session->getLastSuccessQuoteId()) {
            $quoteId = $order->getQuoteId();

            $session->setLastQuoteId($quoteId);
            $session->setLastSuccessQuoteId($quoteId);
            $session->setLastOrderId($order->getId());
        }

        if (isset($result->error)) {
            // Cancel order
            $order->registerCancellation($result->error->message)->save();

            Mage::getSingleton('core/session')->addError($result->error->message);
            $this->_redirect('checkout/onepage/failure/');
            return;
        }
        if ($result->result->status != 'pending') {
            $message = null;
            if (isset($result->reasonCancelled)) {
                $message = 'Touch Payment returned and said:' . $result->reasonCancelled;
            } else {
                $message = 'Got an error:' . var_export($result, true);
            }

            $order->registerCancellation($message)->save();
            Mage::getSingleton('core/session')->addError($message);
            $this->_redirect('checkout/onepage/failure/');
            //throw new Mage_Exception('Wrong Status');
        } else {

            /**
             * adjust the touch fee that comes back from
             * the API in case the fee has changed
             */
            if ((float)$result->result->fee > 0 && $order->getTouchFeeAmount() != $result->result->fee) {
                $order->setGrandTotal($order->getGrandTotal() - $order->getTouchFeeAmount() + $result->result->fee);
                $order->setTouchFeeAmount((float)$result->result->fee);
                $order->setTouchBaseFeeAmount((float)$result->result->fee);
                $order->save();
            }
            // Same here with extension fee
            if ((float)$result->result->extensionFee > 0
                && $order->getTouchExtensionFeeAmount() != $result->result->extensionFee
            ) {
                $order->setGrandTotal(
                    $order->getGrandTotal() - $order->getTouchExtensionFeeAmount() + $result->result->extensionFee
                );

                $order->setTouchExtensionFeeAmount((float)$result->result->extensionFee);
                $order->setTouchBaseExtensionFeeAmount((float)$result->result->extensionFee);
                $order->save();
            }
            /**
             * - Approve the order in touch
             * - set a transaction ID
             * - set Order to paid
             */
            $apprReturn = $this->_approveTouchOrder($order);
            $this->_handleTouchApprovalResponse($order, $apprReturn);
        }


        return;
    }

    public function confirmAction()
    {
        $this->cleaningAfterSuccess();
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setTitle($this->__('Please confirm your order'));
        $this->renderLayout();
    }

    public function holdAction()
    {
        $token = $this->getRequest()->getParam('token');
        $order = Mage::getModel('sales/order')->loadByAttribute('touch_token', $token);

        if ($order) {
            $order->setStatus('touch_payments_hold');
            $order->setState('touch_payments_hold');

            $order->save();

            exit(json_encode(array('status' => 'success')));
        }

        else exit(json_encode(array('status' => 'error')));
    }

    public function releaseAction()
    {
        $token = $this->getRequest()->getParam('token');
        $order = Mage::getModel('sales/order')->loadByAttribute('touch_token', $token);

        if ($order) {
            // Set order to touch-pending, or processing if the shipping method is to be bypassed.
            $payment         = $order->getPayment();
            $method          = $payment->getMethodInstance();
            $shippingMethods = explode(',', $method->getConfigData('shipping_methods'));
            $pendingSkipped  = false;

            foreach ($shippingMethods as $skipShipping) {
                if (strpos($order->getShippingMethod(), $skipShipping) !== false) {
                    $orderStatus = $method->getConfigData('order_status');
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $orderStatus)->save();
                        $pendingSkipped = true;
                    }
            }

            if (!$pendingSkipped) {
                $order->setState(Mage_Sales_Model_Order::STATE_NEW, Touch_TouchPayment_Model_Sales_Order::STATUS_TOUCH_PENDING)->save();
            }

            exit(json_encode(array('status' => 'success')));
        }

        else exit(json_encode(array('status' => 'error')));
    }

    private function _handleTouchApprovalResponse(Mage_Sales_Model_Order $order, $apprReturn)
    {
        if (in_array($apprReturn->result->status, Touch_Item::$shippableStatus)) {
            $order->addStatusHistoryComment('Touch Status : ----')
                ->setIsCustomerNotified(false)
                ->save();
            $payment = $order->getPayment();
            $grandTotal = $order->getBaseGrandTotal();
            $payment->setTransactionId($apprReturn->result->refNumber)
                ->setPreparedMessage("Payment Sucessfull Result:")
                ->setIsTransactionClosed(0)
                ->registerAuthorizationNotification($grandTotal);
            $order->save();


            try {
                if (!$order->canInvoice()) {
                    Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
                }

                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

                if (!$invoice->getTotalQty()) {
                    Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
                }

                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                $invoice->register();
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());

                $transactionSave->save();
                $message = 'Notified customer about invoice #' . $invoice->getIncrementId() . '.';
                $order->sendNewOrderEmail()->addStatusHistoryComment($message)
                    ->setIsCustomerNotified(true)
                    ->save();

                // Set order to touch-pending, or processing if the shipping method is to be bypassed.
                $method          = $payment->getMethodInstance();
                $shippingMethods = explode(',', $method->getConfigData('shipping_methods'));
                $pendingSkipped  = false;

                foreach ($shippingMethods as $skipShipping) {
                    if (strpos($order->getShippingMethod(), $skipShipping) !== false) {
                        $orderStatus = $method->getConfigData('order_status');
                        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $orderStatus)->save();
                        $pendingSkipped = true;
                    }
                }

                if (!$pendingSkipped) {
                    $order->setState(Mage_Sales_Model_Order::STATE_NEW, Touch_TouchPayment_Model_Sales_Order::STATUS_TOUCH_PENDING)->save();
                }

            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }

            if ($this->getRequest()->isXmlHttpRequest()) {
                $this->getResponse()->setBody(
                    Mage::helper('core')->jsonEncode(
                        array(
                            'success'  => true,
                            'error'    => false,
                            'redirect' => '/checkout/onepage/success/'
                        )
                    )
                );
            } else {
                $url = Mage::getUrl('checkout/onepage/success');
                Mage::register('redirect_url', $url);
                $this->_redirectUrl($url);
            }
        } else {
            $order->registerCancellation()->save();
            if ($this->getRequest()->isXmlHttpRequest()) {
                $this->getResponse()->setBody(
                    Mage::helper('core')->jsonEncode(
                        array(
                            'success'  => false,
                            'error'    => true,
                            'redirect' => '/checkout/onepage/failure/'
                        )
                    )
                );
                Mage::getSingleton('core/session')->addError('Wrong return status.');
            } else {
                $url = Mage::getUrl('checkout/onepage/failure');
                Mage::register('redirect_url', $url);
                $this->_redirectUrl($url);
            }
        }
    }

    private function cleaningAfterSuccess()
    {
        $session = $this->getOnepage()->getCheckout();
        $session->clear();
    }

    private function _getTouchStatus(Mage_Sales_Model_Order $order)
    {
        $touchApi = new Touch_TouchPayment_Model_Api_Touch();
        return $touchApi->getOrderByTokenStatus($order->getTouchToken());
    }

    /**
     *
     * @param Mage_Sales_Model_Order $order
     * @param string                 $code
     *
     * @return type
     */
    private function _approveTouchOrderViaSMSCode(Mage_Sales_Model_Order $order, $code)
    {
        $touchApi = new Touch_TouchPayment_Model_Api_Touch();
        $grandTotal = $this->_getNakedGrandTotal($order);

        return $touchApi->approveOrderBySmsCode($order->getTouchToken(), $order->getIncrementId(), $grandTotal, $code);
    }

    private function _approveTouchOrder(Mage_Sales_Model_Order $order)
    {
        $touchApi = new Touch_TouchPayment_Model_Api_Touch();
        $grandTotal = $this->_getNakedGrandTotal($order);
        return $touchApi->approveOrder($order->getTouchToken(), $order->getIncrementId(), $grandTotal);
    }

    /**
     * Don't get any ideas... Just returning the grandtotal of an order,
     * stripped of TouchFees
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return float
     */
    private function _getNakedGrandTotal(Mage_Sales_Model_Order $order)
    {
        return (float)($order->getGrandTotal() - $order->getTouchBaseFeeAmount()
            - $order->getTouchBaseExtensionFeeAmount());
    }

    public function successAction()
    {
        $request = $_REQUEST;
        $orderIncrementId = $request['Merchant_ref_number'];
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);

        try {
            if ($request['Status_'] == 05) {
                $comment = $order->sendNewOrderEmail()->addStatusHistoryComment('Bank Status : Declined By Bank')
                    ->setIsCustomerNotified(false)
                    ->save();
                $this->_forward('error');
            } elseif ($request['Status_'] == 90) {
                $comment = $order->sendNewOrderEmail()->addStatusHistoryComment('Bank Status : Comm. Failed')
                    ->setIsCustomerNotified(false)
                    ->save();
                $this->_forward('error');
            } elseif ($request['Status_'] == 00) {
                $comment = $order->sendNewOrderEmail()->addStatusHistoryComment('Bank Status : ----')
                    ->setIsCustomerNotified(false)
                    ->save();
                $payment = $order->getPayment();
                $grandTotal = $order->getBaseGrandTotal();
                if (isset($request['Transactionid'])) {
                    $tid = $request['Transactionid'];
                } else {
                    $tid = -1;
                }

                $payment->setTransactionId($tid)
                    ->setPreparedMessage("Payment Sucessfull Result:")
                    ->setIsTransactionClosed(0)
                    ->registerAuthorizationNotification($grandTotal);
                $order->save();


                /* if ($invoice = $payment->getCreatedInvoice()) {
                  $message = Mage::helper('pay')->__('Notified customer about invoice #%s.', $invoice->getIncrementId());
                  $comment = $order->sendNewOrderEmail()->addStatusHistoryComment($message)
                  ->setIsCustomerNotified(true)
                  ->save();
                  } */
                try {
                    if (!$order->canInvoice()) {
                        Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
                    }

                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();

                    if (!$invoice->getTotalQty()) {
                        Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
                    }

                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
                    //Or you can use
                    //$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                    $invoice->register();
                    $transactionSave = Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());

                    $transactionSave->save();
                    $message = Mage::helper('pay')->__(
                        'Notified customer about invoice #%s.',
                        $invoice->getIncrementId()
                    );
                    $comment = $order->sendNewOrderEmail()->addStatusHistoryComment($message)
                        ->setIsCustomerNotified(true)
                        ->save();
                } catch (Mage_Core_Exception $e) {

                }
                //Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
                //$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
                $url = Mage::getUrl('checkout/onepage/success', array('_secure' => true));
                Mage::register('redirect_url', $url);
                $this->_redirectUrl($url);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Get one page checkout model
     *
     * @return Mage_Checkout_Model_Type_Onepage
     */
    public function getOnepage()
    {
        return Mage::getSingleton('checkout/type_onepage');
    }

    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function errorAction()
    {
        $gotoSection = false;
        $session = $this->_getCheckout();
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                //Cancel order
                if ($order->getState() != Mage_Sales_Model_Order::STATE_CANCELED) {
                    $order->registerCancellation()->save();
                }
                $quote = Mage::getModel('sales/quote')
                    ->load($order->getQuoteId());
                //Return quote
                if ($quote->getId()) {
                    $quote->setIsActive(1)
                        ->setReservedOrderId(null)
                        ->save();
                    $session->replaceQuote($quote);
                }

                //Unset data
                $session->unsLastRealOrderId();
                //Redirect to payment step
                $gotoSection = 'payment';
                $url = Mage::getUrl('checkout/onepage/index', array('_secure' => true));
                Mage::register('redirect_url', $url);
                $this->_redirectUrl($url);
            }
        }

        return $gotoSection;
    }

}
