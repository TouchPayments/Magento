<?php

/**
 * Data Helper
 *
 * @copyright  2013 Touch Payments / Checkn Pay Ltd Pltd
 */
class Touch_TouchPayment_Model_Data_Helper
{

    public static function getTouchOrder(Mage_Sales_Model_Order $order)
    {
        $session = Mage::getSingleton('checkout/session');
        $customerSession = Mage::getSingleton('customer/session');

        $customer = new Touch_Customer();
        $customer->email = $order->getCustomerEmail();
        $customer->firstName = $order->getCustomerFirstname();
        $customer->lastName = $order->getCustomerLastname();
        $customer->isReturning = self::isReturning($order->getCustomerEmail(), 1);

        $customer->telephoneMobile = $session->getData('touchTelephone');
        $address = $session->getQuote()->getBillingAddress();

        if($session->getDob()) {
            $customer->dob = $session->getDob();
        } elseif ($session->getData('touchDob')) {
            $customer->dob = $session->getData('touchDob');
        }

        if(!$customer->dob && $address->getDob()) {
            $customer->dob = $address->getDob();
        }
        if(!$customer->dob && $session->getQuote()->getDob()) {
            $customer->dob = $session->getQuote()->getDob();
        }
        if(!$customer->dob && $customerSession->isLoggedIn()) {
            $customer->dob = $customerSession->getCustomer()->getDob();
        }
        if(!$customer->dob && $order->getCustomerDob()) {
            $customer->dob = $order->getCustomerDob();
        }

        $touchOrder = new Touch_Order();
        $touchOrder->addressBilling = self::processAddress($order->getBillingAddress());
        $touchOrder->addressShipping = self::processAddress($order->getShippingAddress());
        $grandTotal
            = $order->getGrandTotal() - $order->getTouchBaseFeeAmount() - $order->getTouchBaseExtensionFeeAmount();
        $touchOrder->grandTotal = $grandTotal;
        $touchOrder->shippingCosts = $order->getShippingAmount();
        $touchOrder->gst = $order->getTaxAmount();
        $touchOrder->customer = $customer;
        $extensionDays = $session->getExtensionDays();
        $touchOrder->extendingDays = $extensionDays;
        $touchOrder->shippingMethods = array();
        $touchOrder->clientSessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $touchOrder = self::processItems($order->getItemsCollection(), $touchOrder);

        if ($order->getGwPrice()) {
            // Gift wrap at the order level. TP API only allows at item level, so just assign to the first item
            reset($touchOrder->items);
            $key = key($touchOrder->items);

            $item = $touchOrder->items[$key];

            $item->pricePaid += $order->getGwPrice();
            $item->giftWrapPrice = $order->getGwPrice();

            $touchOrder->items[$key] = $item;
        }

        if ($order->getGiftCardsAmount()) {
            $touchOrder->discount += $order->getGiftCardsAmount();
        }

        return $touchOrder;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public static function getArticleLines(Mage_Sales_Model_Order $order)
    {
        $return = array();
        $items = $order->getItemsCollection();
        foreach ($items as $item) {
            $return[$item->getSku()] = $item->getQtyOrdered();
        }

        return $return;
    }

    public static function getTouchOrderFromQuote(Mage_Sales_Model_Quote $quote)
    {
        // @TODO: If the user is logged in send the information to Touch as well
        $touchOrder = new Touch_Order();

        unset($touchOrder->addressShipping);
        unset($touchOrder->addressBilling);
        unset($touchOrder->customer);
        unset($touchOrder->shippingMethods);

        $touchOrder->grandTotal = $quote->getGrandTotal();

        if ($quote->getShippingAddress()->getShippingInclTax() > 0) {
            $touchOrder->shippingCosts = $quote->getShippingAddress()->getShippingInclTax();
        } else {
            $touchOrder->shippingCosts = 0;
        }

        $touchOrder->gst = 0; // Not available at quote level, will be confirmed at a later stage
        $touchOrder->clientSessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();
        $touchOrder = self::processItems($quote->getAllItems(), $touchOrder);

        if ($quote->getGiftCardsAmount()) {
            $touchOrder->discount += $quote->getGiftCardsAmount();
        }

        if ($quote->getGwPrice()) {
            // Gift wrap at the order level. TP API only allows at item level, so just assign to the first item
            reset($touchOrder->items);
            $key = key($touchOrder->items);

            $item = $touchOrder->items[$key];

            $item->pricePaid += $quote->getGwPrice();
            $item->giftWrapPrice = $quote->getGwPrice();

            $touchOrder->items[$key] = $item;
        }

        return $touchOrder;
    }

    protected static function processItems($items, Touch_Order $order)
    {
        $touchItems = $processedItems = array();
        $discount   = 0;

        foreach ($items as $item) {
            $sku = $item->getSku();
            $parent = $item->getParentItemId();
            $quantityHandler = $item instanceof Mage_Sales_Model_Quote_Item ? 'getQty' : 'getQtyOrdered';

            if ($item->getDiscountAmount() > 0) {
                $discount += $item->getDiscountAmount();
            }

            // The collection could contain simple and configurable items with the same sku...
            if ($parent && !empty($processedItems[$parent]) && !empty($touchItems[$processedItems[$parent]])) {
                $touchItem = $touchItems[$processedItems[$parent]];

                $touchItem->sku = $sku;

                if ($item->getPriceInclTax() && $item->getPriceInclTax() != $touchItem->price) {
                    $touchItem->price     = $item->getPriceInclTax();
                    $touchItem->pricePaid = $item->getPriceInclTax();
                }

                if ($item->getDiscountAmount()) {
                    $touchItem->pricePaid -= $item->getDiscountAmount() / $item->{$quantityHandler}();
                }

                if ($item->getGwPrice()) {
                    $touchItem->giftWrapPrice = $item->getGwPrice();
                    $touchItem->pricePaid += $item->getGwPrice();
                }

                if ($item->{$quantityHandler}() && $item->{$quantityHandler}() >= $touchItem->quantity) {
                    $touchItem->quantity = $item->{$quantityHandler}();
                }

                $touchItems[$sku] = $touchItem;
                $processedItems[$item->getItemId()] = $sku;

                if ($sku !== $processedItems[$parent]) {
                    unset($touchItems[$processedItems[$parent]]);
                }
            } else {
                $touchItem = new Touch_Item();
                $touchItem->sku = $sku;
                $touchItem->quantity = $item->{$quantityHandler}();
                $touchItem->description = $item->getName() . ' ' . (string)$item->getGiftMessageAvailable();
                $touchItem->price = $item->getPriceInclTax();
                $touchItem->pricePaid = $item->getPriceInclTax();
                $touchItems[$sku] = $touchItem;
                $processedItems[$item->getItemId()] = $sku;

                if ($item->getDiscountAmount()) {
                    $touchItem->pricePaid -= $item->getDiscountAmount() / $item->{$quantityHandler}();
                }

                if ($item->getGwPrice()) {
                    $touchItem->giftWrapPrice = $item->getGwPrice();
                    $touchItem->pricePaid += $item->getGwPrice();
                }
            }
        }

        $order->items    = $touchItems;
        $order->discount = $discount;

        return $order;
    }

    protected static function processAddress($address)
    {
        $touchAddress = new Touch_Address();
        $shippingData = $address->getData();

        if ($address->getStreet(1) != $address->getStreet(2)) {
            $touchAddress->addressTwo = $address->getStreet(2);
        }

        $touchAddress->addressOne = $address->getStreet(1);
        $touchAddress->suburb = $shippingData['city'];
        $touchAddress->state = self::adaptStateForTouch($shippingData['region']);
        $touchAddress->postcode = $shippingData['postcode'];
        $touchAddress->firstName = $shippingData['firstname'];
        $touchAddress->middleName = $shippingData['middlename'];
        $touchAddress->lastName = $shippingData['lastname'];

        return $touchAddress;
    }


    public static function adaptStateForTouch($givenState)
    {

        $states = array(
            'au' => array(
                "NSW" => "New South Wales",
                "ACT" => "Australian Capital Territory",
                "TAS" => "Tasmania",
                "NT"  => "Northern Territory",
                "SA"  => "South Australia",
                "QLD" => "Queensland",
                "VIC" => "Victoria",
                "WA"  => "Western Australia"
            )
        );
        $givenStateUpper = mb_strtoupper($givenState);
        if (in_array($givenStateUpper, array_keys($states))) {
            return $givenStateUpper;
        }

        $normalizedState = static::normalizeAlpha($givenStateUpper);
        foreach ($states['au'] as $key => $value) {
            if ($normalizedState === self::normalizeAlpha($value)) {
                return $key;
            }
        }
        return $givenState;
    }

    public static function normalizeAlpha($str)
    {
        // lowercassing
        $str = mb_strtolower($str);
        // Replace not word class char by void
        return preg_replace('/[^a-z]/', '', $str);
    }

    protected static function isReturning($email, $count = 0)
    {
        $orders = Mage::getResourceModel('sales/order_collection')
            ->addFieldToSelect('customer_email')
            ->addFieldToFilter('customer_email', $email);

        return count($orders) > $count;
    }
}
