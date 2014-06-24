<?php
/**
 * Setup db fields for invoice
 * to save a touch fee
 * @see http://www.magentocommerce.com/knowledge-base/entry/magento-for-dev-part-6-magento-setup-resources
 * @copyright  2013 Touch Payments / Checkn Pay Ltd Pltd
 */
$installer = $this;

$installer->startSetup();

$installer->run("
	ALTER TABLE  `".$this->getTable('sales/invoice')."` ADD  `touch_fee_amount` DECIMAL( 10, 2 ) NOT NULL;
	ALTER TABLE  `".$this->getTable('sales/invoice')."` ADD  `touch_base_fee_amount` DECIMAL( 10, 2 ) NOT NULL;
    ");


$installer->endSetup(); 