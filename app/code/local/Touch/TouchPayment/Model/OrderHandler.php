<?php

class Touch_TouchPayment_Model_OrderHandler extends Mage_Core_Model_Abstract
{
    private $_storeId = '1';
    private $_groupId = '1';
    private $_sendConfirmation = '0';
    private $orderData = array();
    private $_product;
    private $_sourceCustomer;
    private $_sourceOrder;
    private $_sourceQuote;

    public function generateOrder($sourceOrder, $quote)
    {
        $this->_sourceOrder = $sourceOrder;
        //$this->_sourceCustomer = $sourceCustomer;
        $this->_sourceQuote = $quote;
        $this->_sourceQuote->setCustomerEmail($sourceOrder['customer']['email']);

        $this->_sourceQuote->save();

        $customerId = $quote->getCustomerId();

        if (!$customerId) {
            $customer = Mage::getModel('customer/customer');
            $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
            $customer->loadByEmail($sourceOrder['customer']['email']);

            if ($customer->getId()) {
                $customerId = $customer->getId();
            } else {
                $customer->setEmail($sourceOrder['customer']['email']);
                $customer->setFirstname($sourceOrder['addressBilling']['firstName']);
                $customer->setLastname($sourceOrder['addressBilling']['lastName']);
                $customer->setPassword(Mage::helper('core')->getRandomString($length = 16));

                $customer->save();
                $customer->setConfirmation(null);
                $customer->save();

                $customerId = $customer->getId();
            }
        }

        //Load full product data to product object
        $this->orderData = array(
            'session'      => array(
                'customer_id' => $customerId,
                'store_id'    => $quote->getStoreId(),
            ),
            'payment'      => array(
                'method' => 'touch_touchexpress',
            ),
            'order'        => array(
                'currency'          => Mage::app()->getStore($quote->getStoreId())->getCurrentCurrencyCode(),
                'account'           => array(
                    'group_id' => $this->_groupId,
                    'email'    => $sourceOrder['customer']['email']
                ),
                'billing_address'   => array(
                    'customer_address_id' => '', //$this->_sourceCustomer->getCustomerAddressId(),
                    'prefix'              => '',
                    'firstname'           => $sourceOrder['addressBilling']['firstName'],
                    'middlename'          => '',
                    'lastname'            => $sourceOrder['addressBilling']['lastName'],
                    'suffix'              => '',
                    'company'             => '',
                    'street'              => array($sourceOrder['addressBilling']['addressOne'], $sourceOrder['addressBilling']['addressTwo']),
                    'city'                => $sourceOrder['addressBilling']['suburb'],
                    'country_id'          => $sourceOrder['addressBilling']['idCountry'],
                    'region'              => $sourceOrder['addressBilling']['idState'],
                    'region_id'           => $sourceOrder['addressBilling']['idState'],
                    'postcode'            => $sourceOrder['addressBilling']['postcode'],
                    'telephone'           => $sourceOrder['customer']['telephoneMobile'],
                    'fax'                 => '',
                ),
                'shipping_address'  => array(
                    'customer_address_id' => '', //$this->_sourceCustomer->getCustomerAddressId(),
                    'prefix'              => '',
                    'firstname'           => $sourceOrder['addressShipping']['firstName'],
                    'middlename'          => '',
                    'lastname'            => $sourceOrder['addressShipping']['lastName'],
                    'suffix'              => '',
                    'company'             => '',
                    'street'              => array($sourceOrder['addressShipping']['addressOne'], $sourceOrder['addressShipping']['addressTwo']),
                    'city'                => $sourceOrder['addressShipping']['suburb'],
                    'country_id'          => $sourceOrder['addressShipping']['idCountry'],
                    'region'              => $sourceOrder['addressShipping']['idState'],
                    'region_id'           => $sourceOrder['addressShipping']['idState'],
                    'postcode'            => $sourceOrder['addressShipping']['postcode'],
                    'telephone'           => $sourceOrder['customer']['telephoneMobile'],
                    'fax'                 => '',
                ),
                'shipping_method'   => $sourceOrder['shippingMethod']['label'],
                'comment'           => array(
                    'customer_note' => 'This order has been automatically generated by Touch Express checkout.',
                ),
                'send_confirmation' => $this->_sendConfirmation
            ),
        );

        // Modifying shipping address in quote so it is marked as different from billing if that's the case
        if ($sourceOrder['addressBilling']['addressOne'] != $sourceOrder['addressShipping']['addressOne'] ||
            $sourceOrder['addressBilling']['addressTwo'] != $sourceOrder['addressShipping']['addressTwo'] ||
            $sourceOrder['addressBilling']['suburb'] != $sourceOrder['addressShipping']['suburb'] ||
            $sourceOrder['addressBilling']['postcode'] != $sourceOrder['addressShipping']['postcode'] ||
            $sourceOrder['addressBilling']['idState'] != $sourceOrder['addressShipping']['idState'] ||
            $sourceOrder['addressBilling']['idCountry'] != $sourceOrder['addressShipping']['idCountry']) {

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setSameAsBilling(false);
            $shippingAddress->save();
        }
    }

    /**
     * Retrieve order create model
     *
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    protected function _getOrderCreateModel()
    {
        return Mage::getSingleton('adminhtml/sales_order_create');
    }

    /**
     * Retrieve session object
     *
     * @return Mage_Adminhtml_Model_Session_Quote
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }

    /**
     * Initialize order creation session data
     *
     * @param array $data
     *
     * @return Mage_Adminhtml_Sales_Order_CreateController
     */
    protected function _initSession($data)
    {
        /* Get/identify customer */
        if (!empty($data['customer_id'])) {
            $this->_getSession()->setCustomerId((int)$data['customer_id']);
        }
        /* Get/identify store */
        if (!empty($data['store_id'])) {
            $this->_getSession()->setStoreId((int)$data['store_id']);
        }
        return $this;
    }

    /**
     * Creates order
     */
    public function create()
    {
        $orderData = $this->orderData;
        $model     = $this->_getOrderCreateModel();

        if (!empty($orderData)) {
            $this->_initSession($orderData['session']);
            try {
                $this->_processQuote($orderData);
                if (!empty($orderData['payment'])) {
                    $model->setPaymentData($orderData['payment']);
                    $model->getQuote()->getPayment()->addData($orderData['payment']);
                }

                Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, "0");
                $_order = $model->importPostData($orderData['order'])->createOrder();


                $_order->setTouchToken($this->_sourceQuote->getTouchToken());
                $_order->setState(Mage_Sales_Model_Order::STATE_NEW, true);
                $_order->save();

                $this->_getSession()->clear();
                Mage::unregister('rule_data');
                return $_order;
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
        return null;
    }

    protected function _processQuote($data = array())
    {
        $model = $this->_getOrderCreateModel();
        Mage::getSingleton('checkout/session')->setQuoteId($this->_sourceQuote->getId());

        $billing = $data['order']['billing_address'];
        $shipping = $data['order']['shipping_address'];

        $billing['street'] = implode("\n", $billing['street']);
        $shipping['street'] = implode("\n", $shipping['street']);

        $this->_sourceQuote->getBillingAddress()->addData($billing);
        $this->_sourceQuote->getShippingAddress()->addData($shipping);
        $this->_sourceQuote->getShippingAddress()->setShippingMethod($data['order']['shipping_method']);

        $this->_sourceQuote->getShippingAddress()->collectTotals();

        $this->_sourceQuote->getPayment()->addData($data['payment']);
        $this->_sourceQuote->collectTotals();

        $model->setQuote($this->_sourceQuote);

        /* Saving order data */
        if (!empty($data['order'])) {
            $model->importPostData($data['order']);
        }

        /* Collect shipping rates */
        $model->collectShippingRates();

        /* Add payment data */
        if (!empty($data['payment'])) {
            $model->getQuote()->getPayment()->addData($data['payment']);
        }
        return $this;
    }
}
