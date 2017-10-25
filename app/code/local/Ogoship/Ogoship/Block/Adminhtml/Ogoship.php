<?php
class Ogoship_Ogoship_Block_Adminhtml_Ogoship extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_ogoship';
    $this->_blockGroup = 'ogoship';
    $this->_headerText = Mage::helper('ogoship')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('ogoship')->__('Add Item');
    parent::__construct();
  }
}