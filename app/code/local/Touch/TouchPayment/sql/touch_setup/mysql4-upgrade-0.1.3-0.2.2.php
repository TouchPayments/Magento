<?php
/**
 * Setup db fields for order
 * to save a touch token
 * @see http://www.magentocommerce.com/knowledge-base/entry/magento-for-dev-part-6-magento-setup-resources
 * @copyright  2013 Touch Payments / Checkn Pay Ltd Pltd
 */
$installer = $this;

$installer->startSetup();
$installer->run("
	ALTER TABLE  `".$this->getTable('sales/order')."` DROP COLUMN `touch_fee_amount`;
	ALTER TABLE  `".$this->getTable('sales/order')."` DROP COLUMN `touch_base_fee_amount`;
	ALTER TABLE  `".$this->getTable('sales/order')."` DROP COLUMN `touch_token`;
	ALTER TABLE  `".$this->getTable('sales/order')."` DROP COLUMN `touch_extension_fee_amount`;
	ALTER TABLE  `".$this->getTable('sales/order')."` DROP COLUMN `touch_extension_fee_days`;
	ALTER TABLE  `".$this->getTable('sales/order')."` DROP COLUMN `touch_base_extension_fee_amount`;
	ALTER TABLE  `".$this->getTable('sales/order')."` DROP COLUMN `touch_base_extension_fee_days`;
	ALTER TABLE  `".$this->getTable('sales/quote')."` DROP COLUMN `touch_token`;
    ");
$installer->endSetup();
