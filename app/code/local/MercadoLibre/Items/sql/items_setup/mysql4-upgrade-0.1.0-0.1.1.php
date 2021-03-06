<?php

try {
$installer = $this;

$installer->startSetup();

$installer->run("

	-- DROP TABLE IF EXISTS {$this->getTable('meli_category_attributes_temp')};
CREATE TABLE {$this->getTable('meli_category_attributes_temp')} (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `meli_attribute_id` varchar(200) DEFAULT NULL,
  `meli_attribute_name` varchar(100) DEFAULT NULL,
  `meli_attribute_type` varchar(200) DEFAULT NULL,
  `required` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- DROP TABLE IF EXISTS {$this->getTable('meli_category_attribute_values_temp')};
CREATE TABLE {$this->getTable('meli_category_attribute_values_temp')} (
  `value_id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(11) NOT NULL,
  `meli_value_id` varchar(200) DEFAULT NULL,
  `meli_value_name` varchar(100) DEFAULT NULL,
  `meli_value_name_extended` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`value_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	
	
	
	");

    $installer->endSetup();
} catch (Exception $e) {
print_r($e);
    die;
}