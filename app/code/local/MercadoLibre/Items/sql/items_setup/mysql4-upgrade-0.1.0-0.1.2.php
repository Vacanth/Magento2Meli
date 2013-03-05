<?php 
try {

	$db = Mage::getSingleton('core/resource')->getConnection('core_write');
	$filename = implode('/',split('[\]',Mage::getBaseDir('code').DS.'local\MercadoLibre\dump\category\meli_categories.csv'));
	$sql = "LOAD DATA  INFILE '".$filename."' INTO TABLE `meli_categories` FIELDS TERMINATED BY ',' lines terminated by '\n'";
	$db->query($sql);
	
	$melicategoriesModel = Mage::getModel('items/melicategories');
	$service_url = 'https://api.mercadolibre.com/sites/MLB/categories/all';
	$x_content_created = $melicategoriesModel->getMLXContentCreated($service_url);
	$runDateTime = date('Y-m-d h:i:s', time());
	$melicategoryupdate = Mage::getModel('items/melicategoryupdate');
	$melicategoryupdate->setCreatedDatetime($x_content_created);
	$melicategoryupdate->setRunDatetime($runDateTime);
	$melicategoryupdate->save();
	$db->query("UPDATE core_config_data  set value = '".$x_content_created."' where path='mlitems/categoriesupdateinformation/contentcreationdate'"); 
	$db->query("UPDATE core_config_data  set value = '".$runDateTime."' where path='mlitems/categoriesupdateinformation/lastrundata'");

} catch (Exception $e) {
print_r($e);
    die;
}