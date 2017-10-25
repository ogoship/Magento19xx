<?php

class Ogoship_Ogoship_Block_Adminhtml_Form_Field_Shippingmethods extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * @var Mage_CatalogInventory_Block_Adminhtml_Form_Field_Customergroup
     */
    protected $_groupRenderer;

    /**
     * Retrieve group column renderer
     *
     * @return Mage_CatalogInventory_Block_Adminhtml_Form_Field_Customergroup
     */
    protected function _getGroupRenderer()
    {
        if (!$this->_groupRenderer) {
            $this->_groupRenderer = $this->getLayout()->createBlock(
                'ogoship/adminhtml_form_field_shippinggroup', '',
                array('is_render_to_js_template' => true)
            );
            //$this->_groupRenderer->setClass('shipping_group_select');
            //$this->_groupRenderer->setExtraParams('style="width:120px"');
        }
        return $this->_groupRenderer;
    }

    /**
     * Prepare to render
     */
    protected function _prepareToRender()
    {
        $this->addColumn('shipping_group_id', array(
            'label' => Mage::helper('customer')->__('Name'),
            'renderer' => $this->_getGroupRenderer(),
        ));
        $this->addColumn('shipping_method_code', array(
            'label' => Mage::helper('customer')->__('Code'),
            'style' => 'width:100px',
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('customer')->__('Add Code');
    }

    /**
     * Prepare existing row data object
     *
     * @param Varien_Object
     */
    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getGroupRenderer()->calcOptionHash($row->getData('shipping_group_id')),
            'selected="selected"'
        );
    }
}
