<?php

class Ogoship_Ogoship_Helper_Shippingmethods extends Mage_Core_Helper_Abstract
{
	
	/**
     * Retrieve fixed code value
     *
     * @param mixed $code
     * @return float|null
     */
    protected function fixCode($code)
    {
        return (!empty($code) ? $code : null);
    }

    /**
     * Generate a storable representation of a value
     *
     * @param mixed $value
     * @return string
     */
    protected function serializeValue($value)
    {
        if (is_numeric($value)) {
            $data = (float) $value;
            return (string) $data;
        } elseif (is_array($value)) {
            $data = [];
            foreach ($value as $groupId => $code) {
                if (!array_key_exists($groupId, $data)) {
                    $data[$groupId] = $this->fixCode($code);
                }
            }
            if (count($data) == 1 && array_key_exists($this->getAllShippingGroupId(), $data)) {
                return (string) $data[$this->getAllShippingGroupId()];
            }
            return serialize($data);
        } else {
            return '';
        }
    }

    /**
     * Create a value from a storable representation
     *
     * @param mixed $value
     * @return array
     */
    protected function unserializeValue($value)
    {
        if (is_numeric($value)) {
            return [$this->getAllShippingGroupId() => $this->fixCode($value)];
        } elseif (is_string($value) && !empty($value)) {
            return unserialize($value);
        } else {
            return [];
        }
    }

    /**
     * Check whether value is in form retrieved by _encodeArrayFieldValue()
     *
     * @param mixed
     * @return bool
     */
    protected function isEncodedArrayFieldValue($value)
    {
        if (!is_array($value)) {
            return false;
        }
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row)
                || !array_key_exists('shipping_group_id', $row)
                || !array_key_exists('shipping_method_code', $row)
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Encode value to be used in Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     *
     * @param array
     * @return array
     */
    protected function encodeArrayFieldValue(array $value)
    {
        $result = array();
        foreach ($value as $groupId => $code) {
            $_id = Mage::helper('core')->uniqHash('_');
            $result[$_id] = array(
                'shipping_group_id' => $groupId,
                'shipping_method_code' => $this->fixCode($code),
            );
        }
        return $result;
    }

    /**
     * Decode value from used in Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     *
     * @param array
     * @return array
     */
    protected function decodeArrayFieldValue(array $value)
    {
        $result = [];
        unset($value['__empty']);
        foreach ($value as $row) {
            if (!is_array($row)
                || !array_key_exists('shipping_group_id', $row)
				|| !array_key_exists('shipping_method_code', $row)
            ) {
                continue;
            }
            $groupId = $row['shipping_group_id'];
            $code = $this->fixCode($row['shipping_method_code']);
            $result[$groupId] = $code;
        }
        return $result;
    }

    /**
     * Retrieve min_sale_qty value from config
     *
     * @param int $customerGroupId
     * @param mixed $store
     * @return float|null
     */
    public function getConfigValue($shippingGroupId, $store = null)
    {
        $value = Mage::getStoreConfig('ogoship/general/ogoship_shipping_method', $store);
        $value = $this->unserializeValue($value);
        
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }
        
        $result = null;
        foreach ($value as $groupId => $code) {
            if ($groupId == $shippingGroupId) {
                $result = $code;
                break;
            } else if ($groupId == Mage_Customer_Model_Group::CUST_GROUP_ALL) {
                $result = $code;
            }
        }
        return $this->fixCode($result);
    }

    /**
     * Make value readable by Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     *
     * @param mixed $value
     * @return array
     */
    public function makeArrayFieldValue($value)
    {
        $value = $this->unserializeValue($value);
        if (!$this->isEncodedArrayFieldValue($value)) {
            $value = $this->encodeArrayFieldValue($value);
        }
        return $value;
    }

    /**
     * Make value ready for store
     *
     * @param mixed $value
     * @return string
     */
    public function makeStorableArrayFieldValue($value)
    {
        if ($this->isEncodedArrayFieldValue($value)) {
            $value = $this->decodeArrayFieldValue($value);
        }
        $value = $this->serializeValue($value);
        return $value;
    }
	
	public function getAllShippingGroupId($store = null)
    {
		return "all";
	}
	
	public function getActiveCarriers($store = null)
    {
        $deliveryMethods = Mage::getSingleton('shipping/config')->getActiveCarriers();
		$deliveryMethodsArray = array();
		foreach($deliveryMethods as $_code => $_method)
		{
			if(!$_title = Mage::getStoreConfig("carriers/$_code/title")) {
				$_title = $_code;
			}
			$deliveryMethodsArray[$_code] = $_title . " ($_code)";
		}
		return $deliveryMethodsArray;
    }

}