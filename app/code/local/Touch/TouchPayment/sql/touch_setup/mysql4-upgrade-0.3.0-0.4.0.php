<?php
/**
 * Setup db fields for order
 * to save a touch token
 * @see http://www.magentocommerce.com/knowledge-base/entry/magento-for-dev-part-6-magento-setup-resources
 * @copyright  2013 Touch Payments / Checkn Pay Ltd Pltd
 */
$installer = $this;

$installer->startSetup();

$installer->getConnection()->insertArray(
    $installer->getTable('sales/order_status'),
    array('status', 'label'),
    array(
        array('touch_payments_pending', 'Touch Payments - Pending')
    )
);


$installer->getConnection()->insertArray(
    $installer->getTable('sales/order_status_state'),
    array('status', 'state', 'is_default'),
    array(
        array('touch_payments_pending',  'new',  '0')
    )
);

$installer->endSetup();
