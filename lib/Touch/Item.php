<?php
/**
 * Touch Payments Item Class
 *
 * @copyright 2013 Check'n Pay Finance Pty Limited
 */
class Touch_Item extends Touch_Object {


    const STATUS_ACTIVE = 'active';
    const STATUS_ACTIVEDUE = 'activeDue';
    const STATUS_APPROVED = 'approved';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_INCOLLECTION = 'inCollection';
    const STATUS_MIXED = 'mixed';
    const STATUS_NEW = 'new';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_PAID = 'paid';
    const STATUS_PAYMENTDELAYED = 'paymentDelayed';
    const STATUS_PAYMENTREFUSED = 'paymentRefused';
    const STATUS_PENDING = 'pending';
    const STATUS_RETURNAPPROVALPENDING = 'returnApprovalPending';
    const STATUS_RETURNAPPROVALPENDINGAFTERPAYMENT = 'returnApprovalPendingAfterPayment';
    const STATUS_RETURNED = 'returned';
    const STATUS_RETURNEDAFTERPAYMENT = 'returnedAfterPayment';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_UNABLETOFULLFILL = 'unableToFullFill';

    /**
     * @var String
     */
    public $sku;

    /**
     * @var Float
     */
    public $price;

    /**
     * @var Float
     */
    public $pricePaid;

    /**
     * @var Float
     */
    public $giftWrapPrice;

    /**
     * @var boolean
     */
    public $onSale;

    /**
     * @var String
     */
    public $description;

    /**
     * @var int
     */
    public $quantity;

    /**
     * @var string
     */
    public $image;

    /**
     * @var string
     */
    public $customLabel;

    /**
     * @var string
     */
    public $customValue;

}
