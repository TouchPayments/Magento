<?php
/**
 * Actual Payment Integration
 *
 * @copyright  2013 Touch Payments / Checkn Pay Ltd Pltd
 */
class Touch_TouchPayment_Model_Express extends Mage_Payment_Model_Method_Abstract {

    const METHOD_TOUCH = 'touch_touchexpress';

    /**
     * unique internal payment method identifier
     *
     * @var string [a-z0-9_]
     */
    protected $_code = self::METHOD_TOUCH;

    /**
     * path to Block model
     * @var string
     */
    protected $_formBlockType = 'touchpayment/form_pay';

    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway = true;

    /**
     * Can authorize online?
     */
    protected $_canAuthorize = true;

    /**
     * Can capture funds online?
     */
    protected $_canCapture = true;

    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial = false;

    /**
     * Can refund online?
     */
    protected $_canRefund = false;

    /**
     * Can void transactions online?
     */
    protected $_canVoid = true;

    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal = true;

    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout = false;

    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping = false;

    /**
     * Can save credit card information for future processing?
     */
    protected $_canSaveCc = false;
    private $_redirectUrl;


    public function capture(Varien_Object $payment, $amount)
    {
        parent::capture($payment, $amount);
        $this->setAmount($amount)
                ->setPayment($payment);
    }


    public function authorize(Varien_Object $payment, $amount)
    {
        // Just return true as for Express it's Touch who initiates the auth
        return true;
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|Mage_Core_Model_Store $storeId
     *
     * @return  mixed
     */
    public function getConfigData($field, $storeId = null)
    {

        if ($field == 'title') {
            return 'Touch Express';
        } else {
            return parent::getConfigData($field, $storeId);
        }
    }

    public function getOrderPlaceRedirectUrl()
    {
        $session = Mage::getSingleton('checkout/session');
        return $session['redirectToTouch'];
    }

    /**
     * Check whether payment method can be used
     *
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if (!parent::isAvailable($quote)) {
            return false;
        }

        if (!$quote) {
            $quote = Mage::getModel('sales/quote')->load(Mage::getSingleton('checkout/session')->getQuoteId());
        }

        // Is the module active?
        if (!$this->getConfigData('active')) {
            return false;
        }

        return true;
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();
        $info->setExtensionDays($data->getExtensionDays());
        $info->setAdditionalInformation('extension_days', $data->getExtensionDays());

        $session = Mage::getSingleton('checkout/session');
        $session['extension_days'] = $data->getExtensionDays();
        $session['touchTelephone'] = $data->getMobile();
        $session['touchDob']       = $data->getDob();
        return $this;
    }

}
