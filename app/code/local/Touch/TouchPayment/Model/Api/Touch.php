<?php
/**
 * Api Model
 *
 * @copyright  2013 Touch Payments / Checkn Pay Ltd Pltd
 */
class Touch_TouchPayment_Model_Api_Touch {

    /**
     * @var string
     */
    private $_redirectUrl;

    /**
     * @var Touch_Client
     */
    private $_touchClient;

    public function __construct()
    {
        $apiUrl = Mage::getStoreConfig('payment/touch_touchpayment/api_url');
        $apiKey = Mage::getStoreConfig('payment/touch_touchpayment/api_key');
        $this->_redirectUrl = Mage::getStoreConfig('payment/touch_touchpayment/redirect_url');
        $this->_touchClient = new Touch_Client($apiKey, $apiUrl);
    }

    /**
     * check if API is active
     * @return boolean
     */
    public function isApiActive()
    {
        $result = $this->_touchClient->isApiActive();
        if ($result->result == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param mixed $articleLines
     */
    public function setOrderItemsShipped($refNr)
    {
        $response = $this->_touchClient->setOrderStatusShipped($refNr);
        return $response;
    }

    /**
     *
     * @param Touch_Order $order
     * @return type
     */
    public function generateOrder(Touch_Order $order)
    {
        $response = $this->_touchClient->generateOrder($order);
        return $response;
    }

    /**
     *
     * @param Touch_Order $order
     * @return type
     */
    public function generateExpressOrder(Touch_Order $order)
    {
        $response = $this->_touchClient->generateExpressOrder($order);
        return $response;
    }

    /**
     *
     * @param float $grandTotal
     * @return type
     */
    public function getFee($grandTotal)
    {
        $response = $this->_touchClient->getFee($grandTotal);
        return $response;
    }

    public function getOrderByTokenStatus($token)
    {
        $response = $this->_touchClient->getOrderStatusFromToken($token);
        return $response;
    }

    /**
     *
     * @param type $token
     * @param type $refNumber
     * @param type $grandTotal
     * @return type
     */
    public function approveOrder($token, $refNumber, $grandTotal)
    {
        $response = $this->_touchClient->approveOrderByToken($token, $refNumber, $grandTotal);
        return $response;
    }

    public function getExtensions()
    {
        return $this->_touchClient->getExtensions();
    }

    /**
     *
     * @param string $token
     * @param string $refNumber
     * @param string $grandTotal
     * @param string $code
     * @return type
     */
    public function approveOrderBySmsCode($token, $refNumber, $grandTotal, $code)
    {
        $response = $this->_touchClient->approveOrderBySmsCode($token, $refNumber, $grandTotal, $code);
        return $response;
    }

    /**
     * @param String $idSession
     * @return type
     */
    public function getJavascriptSources($idSession)
    {
        $response = $this->_touchClient->getJavascriptSources($idSession);
        return $response;
    }

    /**
     * Cancel order item
     *
     * @param $refNumber
     * @param $itemIds
     * @param $reason
     *
     * @return mixed
     */
    public function setOrderItemStatusCancelled($refNumber, $itemIds, $reason)
    {
        $response = $this->_touchClient->setOrderItemStatusCancelled($refNumber, $itemIds, $reason);
        return $response;
    }

    public function getOrder($refNumber)
    {
        return $this->_touchClient->getOrder($refNumber);
    }

    public function setOrderItemStatusShipped($refNumber, $itemIds)
    {
        $response = $this->_touchClient->setOrderItemStatusShipped($refNumber, $itemIds);
        return $response;
    }
}
