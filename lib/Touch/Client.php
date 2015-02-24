<?php
/**
 * Touch Payments Rest Client
 *
 * @copyright 2013 Check'n Pay Finance Pty Limited
 */
class Touch_Client {

    private $_apiKey;
    private $_url;
    private $_port;

    public function __construct($apiKey, $url, $port = 80)
    {
        $this->_url = $url;
        $this->_apiKey = $apiKey;
        $this->_port = $port;
    }

    /**
     * @param string $token
     * @return string
     */
    public function getRedirectUrl($token)
    {
        return str_ireplace('/api', '/check/index/token/', $this->_url). $token;
    }

    /**
     * get a maximum checkout Value
     * @return float
     */
    public function getMaximumCheckoutValue()
    {
        $data = array($this->_apiKey);
        return $this->_callMethod('getMaximumCheckoutValue', $data);
    }

    /**
     * simple check if Touch works
     * @return mixed
     */
    public function ping()
    {
        $data = array($this->_apiKey);
        return $this->_callMethod('ping', $data);
    }

    /**
     *
     * @return mixed
     */
    public function getInitialPaymentDelayDuration()
    {
        $data = array($this->_apiKey);
        return $this->_callMethod('getInitialPaymentDelayDuration', $data);
    }

    /**
     * Check if Api is available at the time
     *
     * @return mixed
     */
    public function isApiActive()
    {
        $data = array($this->_apiKey);
        return $this->_callMethod('apiActive', $data);
    }

    /**
     * set whole order to cancelled
     *
     * @param string $refNr
     * @param string $reason
     * @return string
     */
    public function setOrderStatusCancelled($refNr, $reason)
    {
        $data = array($this->_apiKey, $refNr , $reason);
        return $this->_callMethod('setOrderStatusCancelled', $data);
    }

    /**
     * Set order item to cancelled
     *
     * @param string $refNr
     * @param mixed $itemIds
     * @param string $reason
     * @return mixed
     */
    public function setOrderItemStatusCancelled($refNr, $itemIds, $reason)
    {
        $data = array($this->_apiKey, $refNr ,$itemIds , $reason);
        return $this->_callMethod('setOrderItemStatusCancelled', $data);
    }

    /**
     * set order item to return initiated
     *
     * @param string $refNr
     * @param mixed $itemIds
     * @return mixed
     */
    public function setOrderItemStatusReturnPending($refNr, $itemIds)
    {
        $data = array($this->_apiKey, $refNr, $itemIds);
        return $this->_callMethod('setOrderItemStatusReturnPending', $data);
    }

    /**
     * set order item to shipped
     *
     * @param $refNr
     * @param $itemIds
     *
     * @return string
     */
    public function setOrderItemStatusShipped($refNr, $itemIds)
    {
        $data = array($this->_apiKey, $refNr, $itemIds);
        return $this->_callMethod('setOrderItemStatusShipped', $data);
    }

    /**
     * set order item to return denied
     *
     * @param string $refNr
     * @param mixed $itemIds
     * @return mixed
     */
    public function setOrderItemStatusReturnDenied($refNr, $itemIds)
    {
        $data = array($this->_apiKey, $refNr ,$itemIds);
        return $this->_callMethod('setOrderItemStatusReturnDenied', $data);
    }


    /**
     * set order item to return initiated
     *
     * @param string $refNr
     * @param mixed $itemIds
     * @return mixed
     */
    public function setOrderItemStatusReturned($refNr, $itemIds)
    {
        $data = array($this->_apiKey, $refNr ,$itemIds);
        return $this->_callMethod('setOrderItemStatusReturned', $data);
    }

    /**
     *
     * @param string $refNr
     * @internal mixed $articleLines
     * @return string
     */
    public function setOrderStatusShipped($refNr)
    {
        $data = array($this->_apiKey, $refNr);
        return $this->_callMethod('setOrderStatusShipped', $data);
    }

    /**
     *
     * @param Touch_Order $order
     * @return string
     */
    public function generateOrder(Touch_Order $order)
    {
        $data = array($this->_apiKey, $order->toArray());
        return $this->_callMethod('generateOrder', $data);
    }

    /**
     *
     * @param Touch_Order $order
     * @return string
     */
    public function generateExpressOrder(Touch_Order $order)
    {
        $data = array($this->_apiKey, $order->toArray());
        return $this->_callMethod('generateExpressOrder', $data);
    }

    /**
     *
     * @param string $refNr
     * @return string
     */
    public function getOrder($refNr)
    {
        $data = array($this->_apiKey, $refNr);
        return $this->_callMethod('getOrder', $data);
    }

    /**
     * retrieve extensions
     * if applicable
     * @return string
     */
    public function getExtensions()
    {
        $data = array($this->_apiKey);
        return $this->_callMethod('getExtensions', $data);
    }
    /**
     *
     * @param string $token
     * @return string
     */
    public function getOrderStatusFromToken($token)
    {
        $data = array($this->_apiKey, $token);
        return $this->_callMethod('getOrderStatusFromToken', $data);
    }

    /**
     *
     * @param string $token
     * @param string $refNumber
     * @param float $grandTotal
     * @return mixed
     */
    public function approveOrderByToken($token, $refNumber, $grandTotal)
    {
        $data = array($this->_apiKey, $token, $refNumber, $grandTotal);
        return $this->_callMethod('approveOrderByToken', $data);
    }

    /**
     * approving order via SMS code
     *
     * @param string $token
     * @param string $refNumber
     * @param string $grandTotal
     * @param string $smsCode
     * @return mixed
     */
    public function approveOrderBySmsCode($token, $refNumber, $grandTotal, $smsCode)
    {
        $data = array($this->_apiKey, $token, $refNumber, $grandTotal, $smsCode);
        return $this->_callMethod('approveOrderBySmsCode', $data);
    }

    /**
     *
     * @param float $grandTotal
     * @return string
     */
    public function getFee($grandTotal)
    {
        $data = array($this->_apiKey, $grandTotal);
        return $this->_callMethod('getFeeAmount', $data);
    }

    public function getCustomer($email)
    {
        $response = $this->_callMethod('getCustomer', array($this->_apiKey, $email));

        if (isset($response->result->status) && $response->result->status == 'success') {
            $customer = new Touch_Customer();

            $customer->dob             = $response->result->dob;
            $customer->telephoneMobile = $response->result->telephoneMobile;

            return $customer;
        } else {
            return false;
        }
    }

    /**
     * @param $idSession
     * @return string
     */
    public function getJavascriptSources($idSession)
    {
        $response = $this->_callMethod('getJavascriptSources',array($this->_apiKey, $idSession));
        return $response;
    }

    /**
     *
     * @param string $method
     * @param mixed $data
     * @return string
     */
    private function _callMethod($method, $data)
    {
        $params = array(
            'jsonrpc’ => ’2.0',
            'method' => $method,
            'params' => $data,
            'id' => uniqid()
        );



        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-Type: application/json\r\n',
                'content' => json_encode($params)
            )
        ));

        $jsonResponse = file_get_contents($this->_url, FALSE, $context);

        return json_decode($jsonResponse);
    }

}
