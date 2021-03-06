<?php
/**
 * Controller which will be redirected to from Touch's website
 *
 * @copyright  2013 Touch Payments / Checkn Pay Pty Ltd
 */
class Touch_TouchPayment_ExpressController extends Mage_Core_Controller_Front_Action {

    public function indexAction()
    {
        switch ($this->getRequest()->getParam('do')) {
            case 'generate-order':
                $quote      = Mage::getModel('checkout/cart')->getQuote();

                if (!count($quote->getAllItems())) {
                    exit(json_encode(array('status' => 'error', 'redirect' => '/checkout/cart')));
                }

                $touchOrder = Touch_TouchPayment_Model_Data_Helper::getTouchOrderFromQuote($quote);
                $touchApi   = new Touch_TouchPayment_Model_Api_Touch();

                $response = $touchApi->generateExpressOrder($touchOrder);

                if (!empty($response->result->token)) {
                    $quote->setTouchToken($response->result->token);
                    $quote->save();

                    $this->respondWithSuccess($response->result);
                }
                break;


            case 'get-shipping-methods':

                $this->handleShippingMethodsRequest();
                break;

            case 'save-order':

                $this->handleSaveOrderRequest();
                break;

            case 'is-returning':

                $this->handleIsReturningRequest();
                break;
            default:
                exit(json_encode(array('status' => 'error')));
        }
        exit;
    }

    protected function handleShippingMethodsRequest()
    {
        $addressShipping = json_decode($this->getRequest()->getParam('addressShipping'), true);

        if ($addressShipping) {
            /**
             * @var $quote Mage_Sales_Model_Quote
             */
            $quote = Mage::getModel('sales/quote')->load($this->getRequest()->getParam('token'), 'touch_token');
            Mage::getSingleton('checkout/session')->setQuoteId($quote->getId());

            $addressTouch = new Touch_Address();
            foreach ($addressShipping as $key => $value) {
                $addressTouch->{$key} = $value;
            }

            $address     = $quote->getShippingAddress();
            $regionModel = Mage::getModel('directory/region')->loadByCode($addressTouch->state, $addressTouch->country);

            $address->setCountryId($addressTouch->country)
                ->setRegion($addressTouch->state)
                ->setRegionId($regionModel->getId())
                ->setPostcode($addressTouch->postcode)
                ->setCity($addressTouch->suburb)
                ->setStreet($addressTouch->addressOne)
                ->setCollectShippingRates(true);

            $address->collectTotals()->save();//this fixes missing free shipping methods in most merchants
            //$address->collectShippingRates()->save();
            $rates = $address->getGroupedAllShippingRates();

            $shippingMethods = array();

            foreach ($rates as $carrier) {
                foreach ($carrier as $rate) {
                    $method = new Touch_ShippingMethod();

                    $method->label       = $rate->getCode();
                    $method->description = $rate->getMethodTitle() ? $rate->getMethodTitle() : $rate->getCarrierTitle();
                    $method->cost        = $rate->getPrice();
                    $method->isEligible  = true;

                    $shippingMethods[] = $method;
                }
            }
            exit(json_encode($shippingMethods));
        }

        exit;
    }

    protected function handleIsReturningRequest()
    {
        $email = $this->getRequest()->getParam('email');

        if ($email) {
            $orders = Mage::getResourceModel('sales/order_collection')
                ->addFieldToSelect('customer_email')
                ->addFieldToFilter('customer_email', $email);

            exit(json_encode(array('existing' => (count($orders) ? count($orders) : false))));
        }

        exit(json_encode(array('existing' => false)));
    }

    protected function handleSaveOrderRequest()
    {
        $orderApi = json_decode($this->getRequest()->getParam('order'), true);
        $quote    = Mage::getModel('sales/quote')->load($this->getRequest()->getParam('token'), 'touch_token');

        try {
            $order = new Touch_TouchPayment_Model_OrderHandler();
            $order->generateOrder($orderApi, $quote);
            $o = $order->create();
        } catch (Exception $e) {
            exit (json_encode(array('status' => 'error', 'message' => $e->getMessage())));
        }

        if ($o instanceof Mage_Sales_Model_Order) {
            exit(json_encode(array('status' => 'success', 'merchantRefNumber' => $o->getIncrementId())));
        } else {
            exit(json_encode(array('status' => 'error', 'message' => $o)));
        }
    }

    protected function respondWithSuccess($data)
    {
        $response = array('status' => 'success');
        foreach($data as $kdata => $vdata) {
            $response[$kdata] = $vdata;
        }
        exit(json_encode($response));
    }
}
