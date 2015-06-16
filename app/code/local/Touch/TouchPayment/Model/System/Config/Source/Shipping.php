<?php

class Touch_TouchPayment_Model_System_Config_Source_Shipping
{
    /**
     * Gets all the options in the key => value type array.
     *
     * @return array
     */
    public function getOptions()
    {
        $methods = Mage::getSingleton('shipping/config')->getAllCarriers();
        $options = array();

        foreach($methods as $_code => $_method)
        {
            if (!$_title = Mage::getStoreConfig("carriers/$_code/title")) {
                $_title = $_code;
            }

            $options[] = array('value' => $_code, 'label' => $_title . " ($_code)");
        }

        return $options;
    }

    /**
     * Converts the options into a format suitable for use in the admin area.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $array = array();

        foreach ($this->getOptions() as $option) {
            $array[] = array(
                'value' => $option['value'],
                'label' => $option['label'],
            );
        }

        return $array;
    }
}
