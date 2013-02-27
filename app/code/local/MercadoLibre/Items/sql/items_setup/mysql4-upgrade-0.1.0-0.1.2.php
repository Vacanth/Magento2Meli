<?php

try {
$installer = $this;
$meli_categories = $dir.DS.'meli_categories.csv';
$installer->startSetup();

$installer->run('LOAD DATA LOCAL INFILE "'.$meli_categories.'" INTO TABLE meli_categories FIELDS TERMINATED BY "," enclosed by \'"\' LINES TERMINATED BY "\n" (category_id, meli_category_id, meli_category_name,site_id, has_attributes,root_id,listing_allowed,buying_allowed);');

    $installer->endSetup();
} catch (Exception $e) {
  print_r($e);
    die;
}