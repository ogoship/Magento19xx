<?php 
class Ogoship_Ogoship_Model_Adminhtml_Observer 
{
    public function sendOgoship($observer) {
        $block = Mage::app()->getLayout()->getBlock('sales_order_edit');
        if (!$block){
            return $this;
        }
        $order = Mage::registry('current_order');
        $url = Mage::helper("adminhtml")->getUrl(
            "ogoship/adminhtml_ogoship/sendogoship",
            array('order_id'=>$order->getId())
        );
        $block->addButton('order_send_ogoship', array(
                'label'     => Mage::helper('sales')->__('Send Ogoship'),
                'onclick'   => 'setLocation(\'' . $url . '\')',
                'class'     => 'go'
        ));
        return $this;
    }
}