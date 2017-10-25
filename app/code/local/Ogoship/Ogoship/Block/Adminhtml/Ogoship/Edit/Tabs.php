<?php

class Ogoship_Ogoship_Block_Adminhtml_Ogoship_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('ogoship_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('ogoship')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('ogoship')->__('Item Information'),
          'title'     => Mage::helper('ogoship')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('ogoship/adminhtml_ogoship_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}