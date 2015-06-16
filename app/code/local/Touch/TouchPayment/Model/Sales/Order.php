<?php
/**
 * Observer for certain order events like Shipment
 * and Invoice creation
 *
 * @copyright  2015 Touch Payments / Checkn Pay Ltd Pltd
 */

class Touch_TouchPayment_Model_Sales_Order {

    const STATUS_TOUCH_HOLD    = 'touch_payments_hold';
    const STATUS_TOUCH_PENDING = 'touch_payments_pending';

}