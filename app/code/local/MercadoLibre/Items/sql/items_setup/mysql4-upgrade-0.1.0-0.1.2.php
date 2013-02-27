<?php 
try {

$dataFile = Mage::getBaseDir('var').DS.'category'. DS. 'meli_categories.sql';
if (file_exists($dataFile) && is_readable($dataFile)) {
	$dataFileData  = file_get_contents($dataFile);
}

$installer = $this;
$installer->startSetup();
$installer->run("

$dataFileData


");

    $installer->endSetup();
} catch (Exception $e) {
print_r($e);
    die;
}