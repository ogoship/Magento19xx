<?php
$lib_internal = Mage::getBaseDir('lib');      
$lib_file = $lib_internal.'/ogoship/API.php';
require_once($lib_file);
class Ogoship_Ogoship_Model_Ogoship extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('ogoship/ogoship');
    }
    
    public function ProductOrderUpdate(){
        $deny_latest_changes = Mage::getStoreConfig('ogoship/general/deny_latest_changes',Mage::app()->getStore());
		if(!empty($deny_latest_changes)) {
		    echo $this->__('Export product has been denied.');
		} else {
		   $response = $this->get_latest_changes();
		   if($response) {
		        echo $this->__('Product and order data updated from Ogoship.');
			}
		}
        
    }
	
	public function export_all_products(){
		
		$_productCollection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*')->load();
		foreach ($_productCollection as $product){
			$currency_iso_code = Mage::app()->getStore()->getCurrentCurrencyCode();
			$_product = Mage::getModel('catalog/product')->load($product->getId());
			$export_to_ogoship = $_product->getExportToOgoship();
			if(empty($export_to_ogoship)){
			    $imageUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $_product->getImage();
			    $product_array = array(
					'Code' => $_product->getSku(),
					'Name' => $_product->getName(),
					'Description' => strip_tags($_product->getDescription()),
					'ShortDescription' => strip_tags($_product->getShortDescription()),
					'InfoUrl' => $_product->getProductUrl(),
					'SalesPrice' => intval($_product->getPrice()),
					'Price' => intval($_product->getPrice()),
					'Weight'=> $_product->getWeight(),
					'VatPercentage'=> '',
					'PictureUrl'=>$imageUrl,
					'Currency' => $currency_iso_code
				);
				$NV_products['Products']['Product'][] = $product_array;
				$product_array = '';
			}
		}
		$merchant_id = Mage::getStoreConfig('ogoship/general/merchant_id',Mage::app()->getStore());
		$secret_token = Mage::getStoreConfig('ogoship/general/secret_token',Mage::app()->getStore());
		$api_call = new \NettivarastoAPI($merchant_id, $secret_token);
		$response = $api_call->updateAllProducts($NV_products);
		return $response;
	}
  
	public function get_latest_changes() {
		
		$merchant_id = Mage::getStoreConfig('ogoship/general/merchant_id',Mage::app()->getStore());
		$secret_token = Mage::getStoreConfig('ogoship/general/secret_token',Mage::app()->getStore());
		$api_call = new \NettivarastoAPI($merchant_id, $secret_token);
		$latest = $api_call->latestChanges($latestProducts, $latestOrders);
		if($latestOrders) {
			foreach($latestOrders as $latestOrder) {
				$order = Mage::getModel('sales/order')->load($latestOrder->getReference());
				switch ( $latestOrder->getStatus() ) {	
					 case  'SHIPPED': 
						$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
						$order->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE);
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to SHIPPED.', false);
						$order->save();
                        break;
                    case  'CANCELLED':
						$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
						$order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to CANCELLED.', false);
						$order->save();
                        break;
                    case  'COLLECTING':
						$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
						$order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to COLLECTING.', false);
						$order->save();
                        break;
                    case  'PENDING':
						$order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true);
						$order->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to PENDING.', false);
						$order->save();
                        break;
                    case  'RESERVED':
						$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
						$order->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE);
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to RESERVED.', false);
						$order->save();
                        break;
				}
			}
		}
		if($latestProducts) {
			foreach($latestProducts as $latestProduct) {
				$product_id = Mage::getModel('catalog/product')->getIdBySku($latestProduct->getCode());
				$_product = Mage::getModel('catalog/product')->load($product_id);
				if(!empty($_product)){
					if ($latestProduct->getStock()) {
						$_product->setQuantityAndStockStatus(['qty' => $latestProduct->getStock(), 'is_in_stock' => 1]);
						$_product->save();
					}
				}
			}
		}
		return true;
	}
}