<?php
$lib_internal = Mage::getBaseDir('lib');      
$lib_file = $lib_internal.'/ogoship/API.php';
require_once($lib_file);

class Ogoship_Ogoship_Adminhtml_OgoshipController extends Mage_Adminhtml_Controller_action
{

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('ogoship/items')
			->_addBreadcrumb(Mage::helper('adminhtml')->__('Export Ogoship'), Mage::helper('adminhtml')->__('Export Ogoship'));
		
		return $this;
	}
 
	public function indexAction() {
		$method_action = $this->getRequest()->getParam('method_action');
		if(!empty($method_action)) {
			if($method_action=='send_products') {
				$this->exportproducts();
			}
			if($method_action=='update_changes') {
				$this->importlastchanges();
			}
		}
		$this->_initAction()
			->renderLayout();
	}

	public function exportproducts() {
		$deny_product_export = Mage::getStoreConfig('ogoship/general/deny_product_export',Mage::app()->getStore());
		if(!empty($deny_product_export)) {
			Mage::getSingleton('adminhtml/session')->addError($this->__('Export product has been denied.'));
		} else {
    		$response = Mage::getModel('ogoship/ogoship')->export_all_products();
    		if ( $response ) {
    			if (!((string)$response['Response']['Info']['@Success'] == 'true' ) ) {
    				$strError = $response['Response']['Info']['@Error'];
    				Mage::getSingleton('adminhtml/session')->addError($strError);
    			} else {
    				Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Product export completed.'));
    			}
    		}
		}
	}
	
	public function importlastchanges() {
		$deny_latest_changes = Mage::getStoreConfig('ogoship/general/deny_latest_changes',Mage::app()->getStore());
		if(!empty($deny_latest_changes)) {
			Mage::getSingleton('adminhtml/session')->addError($this->__('Last changes has been denied.'));
		} else {
			$response = Mage::getModel('ogoship/ogoship')->get_latest_changes();
			if($response) {
				Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Product and order data updated from Ogoship.'));
			}
		}
	}
	
 
	
	protected function _initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        $order = Mage::getModel('sales/order')->load($id);

        if (!$order->getId()) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('This order no longer exists.'));
            $this->_redirect('*/*/');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        Mage::register('sales_order', $order);
        Mage::register('current_order', $order);
        return $order;
    }
	
	

	public function sendogoshipAction() {
		
		if ($_order = $this->_initOrder()) {
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
                    //$export_to_ogoship = $_product->getAttributeText('export_to_ogoship');
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
		$this->_redirect('adminhtml/sales_order/view', array('order_id' => $_order->getId()));
	}
}