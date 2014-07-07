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

        $customer = new Touch_Customer();
        $customer->email = $order->getCustomerEmail();
        $customer->firstName = $order->getCustomerFirstname();
        $customer->lastName = $order->getCustomerLastname();

        $customer->telephoneMobile = $session['touchTelephone'];
        $customer->dob = $session['touchDob'];

        $touchOrder = new Touch_Order();
        $touchOrder->addressBilling = self::processAddress($order->getBillingAddress());
        $touchOrder->addressShipping = self::processAddress($order->getShippingAddress());
        $grandTotal
            = $order->getGrandTotal() - $order->getTouchBaseFeeAmount() - $order->getTouchBaseExtensionFeeAmount();
        $touchOrder->grandTotal = $grandTotal;
        $touchOrder->shippingCosts = $order->getShippingAmount();
        $touchOrder->gst = $order->getTaxAmount();
        $touchOrder->items = self::processItems($order->getItemsCollection());
        $touchOrder->customer = $customer;
        $extensionDays = $session['extension_days'];
        $touchOrder->extendingDays = $extensionDays;
        $touchOrder->shippingMethods = array();

        return $touchOrder;
    }

    /**
     *
     * @param Mage_Sales_Model_Order $order
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
        // @todo: Check if there's only one shipping method available then apply here
        $touchOrder->shippingCosts = 0;
        $touchOrder->gst = 0; // Not available at quote level, will be confirmed at a later stage
        $touchOrder->items = self::processItems($quote->getAllItems());
        $touchOrder->clientSessionId = Mage::getSingleton("core/session")->getEncryptedSessionId();

        return $touchOrder;
    }

    protected static function processItems($items)
    {
        $touchItems = $processedItems = array();

        foreach ($items as $item) {
            $sku = $item->getSku();
            $parent = $item->getParentItemId();
            $quantityHandler = $item instanceof Mage_Sales_Model_Quote_Item ? 'getQty' : 'getQtyOrdered';

            // The collection could contain simple and configurable items with the same sku...
            if ($parent && !empty($processedItems[$parent]) && !empty($touchItems[$processedItems[$parent]])) {
                $touchItem = $touchItems[$processedItems[$parent]];

                $touchItem->sku = $sku;

                if ($item->getPriceInclTax() && $item->getPriceInclTax() != $touchItem->price) {
                    $touchItem->price = $item->getPriceInclTax();
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
                $touchItems[$sku] = $touchItem;
                $processedItems[$item->getItemId()] = $sku;
            }
        }
        return $touchItems;
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
}
