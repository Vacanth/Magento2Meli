<?php

class MercadoLibre_Items_Model_MeliCategories extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('items/melicategories');
    }
	
	
	public function getMLCatergoriesAllData()
    {
	
		$resCheckUpdate = array();
        $service_url = 'https://api.mercadolibre.com/sites/MLB/categories/all';
		$x_content_created = $this->getMLXContentCreated($service_url);
		$resMelicategoryupdate = Mage::getModel('items/melicategoryupdate')->getCollection()->addFieldToFilter('created_datetime',$x_content_created);
		$resCheckUpdate = $resMelicategoryupdate->getData();
		if(empty($resCheckUpdate)){
			$resCheckUpdate['0']['update_id'] = 0;
		}
	
		if(trim($resCheckUpdate['0']['update_id']) == '' || trim($resCheckUpdate['0']['update_id']) == '0'){

			/**
			*  TRUNCATE TABLE `meli_categories` to add new data again
			*/
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$write->query("TRUNCATE TABLE `meli_categories`");

			$melicategoryupdate = Mage::getModel('items/melicategoryupdate');
			$melicategoryupdate->setCreatedDatetime($x_content_created);
			$melicategoryupdate->setRunDatetime(date('Y-m-d h:i:s', time()));
			$melicategoryupdate->save();

			$fileName = 'allCategory';
			$dir = Mage::getBaseDir('var').DS.'category';
			if (!is_dir($dir)) {
				if (!@mkdir($dir , 0777, true)) {
				}
       	    }
			$service_url = 'https://api.mercadolibre.com/sites/MLB/categories/all';
			$data = Mage::helper('items')->connect($service_url);
			if (!@file_put_contents($dir . DS . $fileName . '.txt',  $data)) {
				return false;
			}
		    $dataFile = $dir . DS . $fileName . '.txt';
			if (file_exists($dataFile) && is_readable($dataFile)) {
				$dataFileData  = file_get_contents($dataFile);
			}

		   $dataArr = json_decode($dataFileData);
		   $i=0;
		   foreach($dataArr as $row){
				$site_id = 'NULL';
				$site_id = substr($row->id,0,3);
				$root_id = Mage::helper('items')->getMLRootId($row->path_from_root);
				$has_attributes = 0;
				$melicategories = Mage::getModel('items/melicategories');
				$melicategories->setMeliCategoryId($row->id);
				$melicategories->setMeliCategoryName($row->name);
				$melicategories->setSiteId($site_id);
				$melicategories->setHasAttributes($has_attributes);
				$melicategories->setRootId($root_id);
				$melicategories->save();
				$i++;
			 }
		  }
			if (is_dir($dir)) {
				if (!@unlink($dir)) {
				}
			}
    
	}
	
	
	public function getMLCategoryAttributes()
	{
			/**
			* Get meli_category_id from meli_categories table
			*/
			$melicategories = Mage::getModel('items/melicategories')->getCollection(); 
			//$melicategories->getSelect()->limit(1); 
			$dataMLcat = $melicategories->getData();
			
			/**
			* Check for meli_categories data exist
			*/
			if(count($dataMLcat) > 0){
				/**
				* TRUNCATE TABLE meli_category_attributes and meli_category_attribute_values before Insert data
				*/
				$write = Mage::getSingleton('core/resource')->getConnection('core_write');
				$write->query("TRUNCATE TABLE `meli_category_attributes`");
				$write->query("TRUNCATE TABLE `meli_category_attribute_values`");
				//$startTime = "Start Time". date('Y-m-d h:i:s', time());
				foreach($dataMLcat as $row){
					$meli_category_id = $row['meli_category_id'];
					$category_id = $row['category_id'];
					$service_url_category = "https://api.mercadolibre.com/categories/".$meli_category_id."/attributes";
					$json_data = Mage::helper('items')->connect($service_url_category);
					$attributesArr = json_decode($json_data);
					if(count($attributesArr) > 0)
					{
						/**
						* Update table meli_categories for has_attributes 
						*/
						$melicateUpdate = Mage::getSingleton('items/melicategories');
						$melicateUpdate->setCategoryId($category_id);
						$melicateUpdate->setHasAttributes('1');
						$melicateUpdate->save();
					
						$i=0;
						foreach($attributesArr as $rowAttribute)
						{
							/**
							* Insert data into meli_category_attributes
							*/
							$rowAttribute = (array)$rowAttribute;
							$mcAttributes = Mage::getModel('items/melicategoryattributes');
							$mcAttributes->setCategoryId($category_id);
							$mcAttributes->setMeliAttributeId($rowAttribute['id']);
							$mcAttributes->setMeliAttributeName($rowAttribute['name']);
							$mcAttributes->setMeliAttributeType($rowAttribute['type']);
							$mcAttributes->setRequired($rowAttribute['tags']->required); 
							$insertId = $mcAttributes->save()->getId(); // last InsertId attribute_id
							if(count($rowAttribute['values']) > 0)
							{
								/**
								* Insert data into meli_category_attribute_values 
								*/
								$this->getMLCategoryAttributesValue($insertId,$rowAttribute['values']);
							}
							$i++;
						}
					}
				}	
				//echo $startTime."<br /> End Now ". date('Y-m-d h:i:s', time());
			} else {
				echo "No Categories Data Found";
			}	
	}
	
	/**
	* Insert data into meli_category_attribute_values 
	*/
	public function getMLCategoryAttributesValue($attributeId, $arrayAttribute = array())
	{
		$j=0;
	 	foreach($arrayAttribute as $rowAttriVal){
			$rowAttriVal = (array)$rowAttriVal;
			$mcAttributesVal = Mage::getModel('items/melicategoryattributevalues');
			$mcAttributesVal->setAttributeId($attributeId);
			$mcAttributesVal->setMeliValueId($rowAttriVal['id']);
			$mcAttributesVal->setMeliValueName($rowAttriVal['name']);
			$mcAttributesVal->setMeliValueNameExtended(''); 
			$mcAttributesVal->save(); 
			$j++;		
	   }	
	}
		
	public function getMLXContentCreated($service_url)
	{
		$data = Mage::helper('items')->connect1($service_url);
		$dataArr =  explode('X-Content-Created:',$data);
		$ebay_date = substr(trim($dataArr['1']),0,26);
		return Mage::helper('items')->getMLebayDateToDateTime($ebay_date);
	}
	
}