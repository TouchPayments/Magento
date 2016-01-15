<?php
/**
 * Created by PhpStorm.
 * User: Ralf
 * Date: 15/01/2016
 * Time: 2:29 PM
 */
class Touch_TouchPayment_Block_Adminhtml_Version
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $helper = new Touch_TouchPayment_Helper_Data();
        return (string) $helper->getExtensionVersion();
    }
}