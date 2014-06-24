<?php
/**
 * Radio Box for Touch Payment Selection
 *
 * @copyright  2013 Touch Payments / Checkn Pay Ltd Pltd
 */
class Touch_TouchPayment_Block_Form_Pay extends Mage_Payment_Block_Form
{
    /**
     * @var string
     */
    public $telephoneMobile;

    /**
     * @var string
     */
    public $dob;

    /**
     * @var Touch_Client
     */
    private $_touchClient;
    public $initialDelay;
    public $extensions;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('touch/form/pay.phtml');

        $mark = Mage::getConfig()->getBlockClassName('core/template');
        $mark = new $mark;
        $mark->setTemplate('touch/form/mark.phtml');

        $apiUrl = Mage::getStoreConfig('payment/touch_touchpayment/api_url');
        $apiKey = Mage::getStoreConfig('payment/touch_touchpayment/api_key');
        $this->_touchClient = new Touch_Client($apiKey, $apiUrl);

        $this->getOrderData();

        $this->setMethodLabelAfterHtml($mark->toHtml());
    }

    public function getOrderData()
    {
        $session = Mage::getSingleton('checkout/session');
        $address = $session->getQuote()->getBillingAddress();

        $customer = $this->_touchClient->getCustomer($address->getEmail());

        $this->initialDelay = $this->_touchClient->getInitialPaymentDelayDuration()->result;
        $this->extensions = $this->_touchClient->getExtensions()->result;

        if ($customer instanceof Touch_Customer) {
            $this->telephoneMobile = $customer->telephoneMobile;
            $this->dob = $customer->dob;
        } else {
            $this->telephoneMobile = $address->getTelephone();
        }
    }
}
