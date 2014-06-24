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
	ALTER TABLE  `".$this->getTable('sales/quote')."` ADD  `touch_token` varchar(255) default NULL;

    ");

$installer->endSetup();
