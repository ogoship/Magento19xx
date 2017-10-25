<?php
class Ogoship_Ogoship_Block_Ogoship extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getOgoship()     
     { 
        if (!$this->hasData('ogoship')) {
            $this->setData('ogoship', Mage::registry('ogoship'));
        }
        return $this->getData('ogoship');
        
    }
}