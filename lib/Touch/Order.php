<?php
/**
 * Touch Payments Order Object Class
 *
 * @copyright 2013 Check'n Pay Finance Pty Limited
 */
class Touch_Order extends Touch_Object {

    /**
     * @var Float
     */
    public $grandTotal;

    /**
     * @var Float
     */
    public $shippingCosts;

    /**
     * @var Float
     */
    public $discount;

    /**
     * @var float
     */
    public $gst;

    /**
     * @var Touch_Item[]
     */
    public $items;

    /**
     * @var Touch_Address
     */
    public $addressShipping;

    /**
     * @var Touch_Address
     */
    public $addressBilling;

    /**
     * @var Touch_Customer
     */
    public $customer;

    /**
     * @var Int
     */
    public $extendingDays;

    /**
     * @var Touch_ShippingMethod[]
     * If not set, Touch will ask to the shop to update it later, by sending the shipping address
     */
    public $shippingMethods;

    /**
     * @var string
     */
    public $clientSessionId;

    /**
     * @return array()
     * @throws Exception
     */
    public function toArray()
    {
        $return = array();
        foreach ($this as $key => $value) {
            if ($key == 'items') {
                if (!array($value)) {
                    throw new Exception('Items need to be an array of items');
                }
                foreach ($value as $item) {
                    if (!$item instanceof Touch_Item) {
                        throw new Exception('Items needs to be of type Touch_Item');
                    }
                    $return['items'][] = $item->toArray();
                }
                continue;
            }

            if ($value instanceof Touch_Object) {
                $return[$key] = $value->toArray();
            } else {
                $return[$key] = $value;
            }
        }
        return $return;
    }

}
