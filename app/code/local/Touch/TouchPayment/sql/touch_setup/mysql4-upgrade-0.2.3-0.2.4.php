<?php
/**
 * Setup db fields for order
 * to save a touch token
 * @see http://www.magentocommerce.com/knowledge-base/entry/magento-for-dev-part-6-magento-setup-resources
 * @copyright  2013 Touch Payments / Checkn Pay Ltd Pltd
 */
$installer = $this;

$installer->startSetup();
$salesResourceSetupModel = Mage::getModel('sales/resource_setup', 'core_setup');

$options = array(
    'group' => 'Touch',
    'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'is_required' => '0',
    'is_comparable' => '0',
    'is_searchable' => '0',
    'is_unique' => '0',
    'is_configurable' => '0',
    'user_defined' => '1',
);

$salesResourceSetupModel->addAttribute( 'quote', 'touch_fee_amount', array_merge($options, array(
    'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
    'label' => 'touch_fee_amount'
)));
$salesResourceSetupModel->addAttribute( 'quote', 'touch_base_fee_amount', array_merge($options, array(
        'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'label' => 'touch_base_fee_amount'
)));
$salesResourceSetupModel->addAttribute( 'quote', 'touch_token', array_merge($options, array(
        'type' => Varien_Db_Ddl_Table::TYPE_VARCHAR,
            'label' => 'touch_token'
)));
$salesResourceSetupModel->addAttribute( 'quote', 'touch_extension_fee_amount', array_merge($options, array(
        'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'label' => 'touch_extension_fee_amount'
)));
$salesResourceSetupModel->addAttribute( 'quote', 'touch_extension_fee_days', array_merge($options, array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'label' => 'touch_extension_fee_days'
)));
$salesResourceSetupModel->addAttribute( 'quote', 'touch_base_extension_fee_amount', array_merge($options, array(
        'type' => Varien_Db_Ddl_Table::TYPE_DECIMAL,
            'label' => 'touch_base_extension_fee_amount'
)));
$salesResourceSetupModel->addAttribute( 'quote', 'touch_base_extension_fee_days', array_merge($options, array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'label' => 'touch_base_extension_fee_days'
)));

$installer->endSetup();
