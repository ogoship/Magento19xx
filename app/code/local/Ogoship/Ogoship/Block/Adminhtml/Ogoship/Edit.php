<?php

class Ogoship_Ogoship_Block_Adminhtml_Ogoship_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'ogoship';
        $this->_controller = 'adminhtml_ogoship';
        
        $this->_updateButton('save', 'label', Mage::helper('ogoship')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('ogoship')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('ogoship_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'ogoship_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'ogoship_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('ogoship_data') && Mage::registry('ogoship_data')->getId() ) {
            return Mage::helper('ogoship')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('ogoship_data')->getTitle()));
        } else {
            return Mage::helper('ogoship')->__('Add Item');
        }
    }
}