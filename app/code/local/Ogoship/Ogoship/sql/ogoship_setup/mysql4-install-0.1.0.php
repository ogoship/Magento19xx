<?php
$installer = $this;
//$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();
$installer->addAttribute('catalog_product', 'export_to_ogoship', array(
     'type'              => 'int',
     'backend'           => '',
     'frontend'          => '',
     'label'             => 'Export To Ogoship',
     'input'             => 'select',
     'class'             => '',
     'backend'    		 => 'eav/entity_attribute_backend_array',
     'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
     'visible'           => true,
     'required'          => false,
     'user_defined'      => true,
     'default'           => false,
     'searchable'        => false,
     'filterable'        => false,
     'comparable'        => false,
     'visible_on_front'  => false,
     'unique'            => false,
     'apply_to'          => '',
	 'option'     => array (
						'values' => array(
							0 => 'No',
							1 => 'Yes',
						)
					),
     'is_configurable'   => false
));
$installer->endSetup();
?>