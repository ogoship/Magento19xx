<?php
/**
 * Created by PhpStorm.
 * User: pcs166
 * Date: 4/19/2018
 * Time: 10:04 AM
 */

Class Ogoship_Ogoship_Model_Observer
{
    public function sendOgoship(Varien_Event_Observer $observer){
        $invoice = $observer->getEvent()->getInvoice();
        $_order = $invoice->getOrder();
        try {
            $order_id = $_order->getId();
            $merchant_id = Mage::getStoreConfig('ogoship/general/merchant_id',Mage::app()->getStore());
            $secret_token = Mage::getStoreConfig('ogoship/general/secret_token',Mage::app()->getStore());
            $api_call = new \NettivarastoAPI($merchant_id, $secret_token);
            $order = new \NettivarastoAPI_Order($api_call,$order_id);
            $nettivarasto_shipping_method	=	$_order->getShippingMethod();
            $nettivarasto_shipping_methods = explode("_", $nettivarasto_shipping_method);
            $nettivarasto_shipping_method = 'ogoship_code_'.$nettivarasto_shipping_methods[1];
            if(!empty($nettivarasto_shipping_methods)) {
                $_ogoship_shipping_code = Mage::helper('ogoship/shippingmethods')->getConfigValue($nettivarasto_shipping_methods[0]);
                //$_ogoship_shipping_code = '';
                if(!empty($_ogoship_shipping_code)) {
                    $nettivarasto_shipping_method = $_ogoship_shipping_code;
                } else {
                    Mage::getSingleton('adminhtml/session')->addError($this->__('Order Shipping method not enabled in settings'));
                    $this->_redirect('adminhtml/sales_order/view', array('order_id' => $_order->getId()));
                }
            }
            $orderItems = $_order->getAllItems();
            $shippingAddress = $_order->getShippingAddress();
            $index=0;
            foreach ($orderItems as $item) {
                $product_id = Mage::getModel('catalog/product')->loadByAttribute('sku',$item->getSku());
                $_product = Mage::getModel('catalog/product')->load($product_id);
                $export_to_ogoship = $_product->getExportToOgoship();
                if(empty($export_to_ogoship)){
                    $order->setOrderLineCode( $index, ($item->getSku()));
                    $order->setOrderLineQuantity( $index, intval($item->getQtyOrdered()));
                    $order->setOrderLinePrice( $index, $item->getPrice());

                    $index++;
                }
            }

            $order->setPriceTotal($_order->getGrandTotal());
            $order->setCustomerName($shippingAddress->getFirstname().' '.$shippingAddress->getLastname());
            $order->setCustomerAddress1($shippingAddress->getStreet());
            $order->setCustomerAddress2('');
            $order->setCustomerCity($shippingAddress->getCity());
            $order->setCustomerCountry($shippingAddress->getCountryId());
            $order->setCustomerEmail($shippingAddress->getEmail());
            $order->setCustomerPhone($shippingAddress->getTelephone());
            $order->setCustomerZip($shippingAddress->getPostcode());
            $order->setShipping($nettivarasto_shipping_method);
            if ($order->save()) {
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Order successfully transferred to Ogoship.'));
            } else {
                $error_warning = 'Error - Ogoship API'. $api_call->getLastError();
                Mage::getSingleton('adminhtml/session')->addError($error_warning);
            }
        }
        catch (Mage_Core_Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::logException($e);
        }
    }
}