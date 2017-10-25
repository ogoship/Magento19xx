<?php
class Ogoship_Ogoship_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
    	
    	/*
    	 * Load an object by id 
    	 * Request looking like:
    	 * http://site.com/ogoship?id=15 
    	 *  or
    	 * http://site.com/ogoship/id/15 	
    	 */
    	/* 
		$ogoship_id = $this->getRequest()->getParam('id');

  		if($ogoship_id != null && $ogoship_id != '')	{
			$ogoship = Mage::getModel('ogoship/ogoship')->load($ogoship_id)->getData();
		} else {
			$ogoship = null;
		}	
		*/
		
		 /*
    	 * If no param we load a the last created item
    	 */ 
    	/*
    	if($ogoship == null) {
			$resource = Mage::getSingleton('core/resource');
			$read= $resource->getConnection('core_read');
			$ogoshipTable = $resource->getTableName('ogoship');
			
			$select = $read->select()
			   ->from($ogoshipTable,array('ogoship_id','title','content','status'))
			   ->where('status',1)
			   ->order('created_time DESC') ;
			   
			$ogoship = $read->fetchRow($select);
		}
		Mage::register('ogoship', $ogoship);
		*/

			
		$this->loadLayout();     
		$this->renderLayout();
    }
}