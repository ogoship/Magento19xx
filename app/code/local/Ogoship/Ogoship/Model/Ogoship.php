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
		
		$NV_products = array();
		$_productCollection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*')->load();
		foreach ($_productCollection as $product){
			$currency_iso_code = Mage::app()->getStore()->getBaseCurrencyCode();
			$_product = Mage::getModel('catalog/product')->load($product->getId());
            //$export_to_ogoship = $_product->getExportToOgoship();
            $export_to_ogoship = $_product->getAttributeText('export_to_ogoship');
			if($export_to_ogoship == "Yes"){
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
        $previous = Mage::getStoreConfig('ogoship/general/last_updated_timestamp', Mage::app()->getStore());

		$api_call = new \NettivarastoAPI($merchant_id, $secret_token);
        $api_call->setTimestamp($previous);
		$success = $api_call->latestChanges($latestProducts, $latestOrders);
		if($latestOrders) {
			foreach($latestOrders as $latestOrder) {
				$order = Mage::getModel('sales/order')->load($latestOrder->getReference());
				switch ( $latestOrder->getStatus() ) {	
					 case  'SHIPPED': 
						//$order->setState(Mage_Sales_Model_Order::STATE_COMPLETE, true);
						if($order->getState() != Mage_Sales_Model_Order::STATE_COMPLETE)
						{
							if($order->canShip())
							{
								if($latestOrder->getTrackingNumber() != null)
								{
									Mage::log($latestOrder->getTrackingNumber());
									foreach(explode(',', $latestOrder->getTrackingNumber()) as $track)
									{
										$shipmentApi = Mage::getModel('sales/order_shipment_api');
										$carriers = $shipmentApi->getCarriers($order->getIncrementId());
										//Mage::log($order->getShippingMethod() . " " . $order->getShippingDescription());
										$type = $latestOrder->getShipping();
										$type = str_replace('()', $type);
										$shipment = $shipmentApi->create($order->getIncrementId(), array(), '' , false, 0);
										if(isset($carriers[$type]))
										{
											$shipmentApi->addTrack($shipment, $type, $carriers[$type], $track);
										} else {
											$shipmentApi->addTrack($shipment, 'custom', $carriers['custom'], $track);
										}
									}
									// send mail
									//$shipmentApi->sendInfo($shipment);
								}
							}
							$order->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE);
							$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to SHIPPED.', false);
							$order->save();
						}
                        break;
                    case  'CANCELLED':
						//$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true);
						//$order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to CANCELLED.', false);
						$order->save();
                        break;
                    case  'COLLECTING':
						//$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
						//$order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to COLLECTING.', false);
						$order->save();
                        break;
                    case  'PENDING':
						//$order->setState(Mage_Sales_Model_Order::STATE_PENDING, true);
						//$order->setStatus(Mage_Sales_Model_Order::STATE_PENDING);
						$order->addStatusToHistory($order->getStatus(), 'Ogoship change of status to PENDING.', false);
						$order->save();
                        break;
					case  'RESERVED':
						if($order->canHold() == true){
							//$order->setState(Mage_Sales_Model_Order::STATE_HOLDED, true);
							$order->setStatus(Mage_Sales_Model_Order::STATE_HOLDED);
						}
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
        $latest = $api_call->getTimestamp();
		Mage::getModel('core/config')->saveConfig('ogoship/general/last_updated_timestamp', $latest, 'stores', Mage::app()->getStore());
		return true;
	}
}