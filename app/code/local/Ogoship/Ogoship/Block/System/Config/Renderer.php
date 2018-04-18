<?php
class Ogoship_Ogoship_Block_System_Config_Renderer extends Mage_Adminhtml_Block_System_Config_Form_Field{
    protected function _getElementHtml($element) {

            $element->setDisabled('disabled');

            return parent::_getElementHtml($element);
    }
}