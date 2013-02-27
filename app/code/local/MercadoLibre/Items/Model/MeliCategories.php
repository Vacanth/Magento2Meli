<?php

class MercadoLibre_Items_Model_MeliCategories extends Mage_Core_Model_Abstract
{
   
    private $site_id = 'NULL';
	private $root_id = '';
   	private $moduleName = "Items";
	private $fileName = "MeliCategories.php";
	private $fileNameAttr = '';
	private $fileNameCat = 'allCategoryJsonData.txt';
	private $to = '';
	
	//message variable
	private $infoMessage = "";
	private $errorMessage = "";
	private $successMessage = "";
   
   
    public function _construct()
    {
        parent::_construct();
        $this->_init('items/melicategories');
    }
	
	
	public function getMLCatergoriesAllData()
    {
		try{
			$this->to = Mage::getStoreConfig("mlitems/meligeneralsetting/notificationemailid",Mage::app()->getStore());
			//Initilize logger model
			$commonModel = Mage::getModel('items/common');
		
			$this->infoMessage ="INFORMATION:: getMLCatergoriesAllDataAction Started";
			$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
			$commonModel->sendNotificationMail($this->to, 'ML Catergories All Data Cron Started', $this->infoMessage);

			$resCheckUpdate = array();
			$service_url = 'https://api.mercadolibre.com/sites/MLB/categories/all';
			$x_content_created = $this->getMLXContentCreated($service_url);
			$resMelicategoryupdate = Mage::getModel('items/melicategoryupdate')->getCollection()->addFieldToFilter('created_datetime',$x_content_created);
			$resCheckUpdate = $resMelicategoryupdate->getData();
			
			$runDateTime = date('Y-m-d h:i:s', time());
			$melicategoryupdate = Mage::getModel('items/melicategoryupdate');
			$updateCofig = '';
			$updateCofig .= "INSERT INTO `meli_category_update` (`update_id`, `created_datetime`, `run_datetime`) VALUES (NULL, '".$x_content_created."', '".$runDateTime."')".";\n\n";
			$updateCofig .= "UPDATE core_config_data  set value = '".$x_content_created."' where path='mlitems/categoriesupdateinformation/contentcreationdate'".";\n\n";
			$updateCofig .= "UPDATE core_config_data  set value = '".$runDateTime."' where path='mlitems/categoriesupdateinformation/lastrundata'".";\n\n";
			
			if(empty($resCheckUpdate)){
				$resCheckUpdate['0']['update_id'] = 0;
			}
			
			try{
			  
			   if(trim($resCheckUpdate['0']['update_id']) == '' || trim($resCheckUpdate['0']['update_id']) == '0'){
				$dir = Mage::getBaseDir('var').DS.'category';
				try{
					if (!is_dir($dir)) {
						if (!@mkdir($dir , 0777, true)) {
						}
					}
				}catch(Exception $e){
						$this->errorMessage = "Error::Unable to directory create (".$dir.")";
						$commonModel->saveLogger($this->moduleName, "Error", $this->fileName, $this->errorMessage);
				}
				/*  Get & save all category json data into allCategoryJsonData.txt */
				$service_url = 'https://api.mercadolibre.com/sites/MLB/categories/all';
				$data = $commonModel ->connect($service_url);
				
				try{
					if (!@file_put_contents($dir . DS . $this->fileNameCat,  $data)) {
						  return false;
					}
				}catch(Exception $e){
						$this->errorMessage = "Error::Unable to write data in file(".$dir . DS . $this->fileNameCat.")";
						$commonModel->saveLogger($this->moduleName, "Error", $this->fileName, $this->errorMessage);
				}
				try{
					$dataFile = $dir . DS . $this->fileNameCat;
					if (file_exists($dataFile) && is_readable($dataFile)) {
						$dataFileData  = file_get_contents($dataFile);
					}
				}catch(Exception $e){
						$this->errorMessage = "Error::Unable to read data in file(".$dir . DS . $this->fileNameCat.")";
						$commonModel->saveLogger($this->moduleName, "Error", $this->fileName, $this->errorMessage);
				}
				/* Get Json data to array*/
			    $dataArr = json_decode($dataFileData);
				$catList = array();
					$sqlInsert = '';
					$sqlInsert = 'INSERT INTO `meli_categories` (`category_id`, `meli_category_id`, `meli_category_name`, `site_id`, `has_attributes`, `root_id`, `listing_allowed`, `buying_allowed`) VALUES '."\n";
				if(count($dataArr) > 0){
					foreach($dataArr as $row)
					{	
						$site_id = 'NULL';
						$site_id = substr($row->id,0,3);
						$root_id = (Mage::helper('items')->getMLRootId($row->path_from_root)) ? Mage::helper('items')->getMLRootId($row->path_from_root):0;
						$has_attributes = 0;	
						$listing_allowed = (isset($row->settings->listing_allowed) && $row->settings->listing_allowed == true)?$row->settings->listing_allowed:0;	
						$buying_allowed = (isset($row->settings->buying_allowed) && $row->settings->buying_allowed == true)?$row->settings->buying_allowed:0;						
					    $sqlInsert .= "(NULL, '".mysql_real_escape_string($row->id)."','".mysql_real_escape_string($row->name)."', '".mysql_real_escape_string($site_id)."', ".mysql_real_escape_string($has_attributes).",'".mysql_real_escape_string($root_id)."', '".mysql_real_escape_string($listing_allowed)."','".mysql_real_escape_string($buying_allowed)."'),\n";
					}
						$sqlInsert = substr($sqlInsert, 0 ,-2).";";
						$sqlInsert .= "\n\n\n".$updateCofig;
						/* get category data into meli_categories.sql*/
						$dataFile = Mage::getBaseDir('var').DS.'category'. DS. 'meli_categories.sql';
						file_put_contents($dataFile, trim($sqlInsert));			
					try{				
						/** TRUNCATE TABLE `meli_categories` to add new data again */
						$write = Mage::getSingleton('core/resource')->getConnection('core_write');
						$write->query("TRUNCATE TABLE `meli_categories`");
						$write->query("TRUNCATE TABLE `meli_category_update`");
						/* import category data into meli_categories*/
						$dataFile = Mage::getBaseDir('var').DS.'category'. DS. 'meli_categories.sql';
						if (file_exists($dataFile) && is_readable($dataFile)) {
							$dataFileData  = file_get_contents($dataFile);
						}
						/** Write data in to table meli_categories & melicategoryupdate */
						$write->multiQuery(" $dataFileData "); 
						
					} catch(PDOException $e){
						$this->errorMessage = $e->getTrace()."::".$e->getMessage();
						$commonModel->saveLogger($this->moduleName, "PDOException", $this->fileName, $this->errorMessage);
					}
					
				}

				if (is_dir($dir)) {
					if (!@unlink($dir)) {
					}
				}
			}
			}catch(Exception $e){
					$this->infoMessage = "INFORMATION::All Categories already added in database";
					$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
				}
			
		}catch(Exception $e){
				$commonModel->saveLogger($this->moduleName, "Exception", $this->fileName, $e->getMessage());
				$commonModel->sendNotificationMail($this->to, 'Exception::ML Catergories All Data Cron', $e->getMessage());
		}	
		$this->infoMessage ="INFORMATION::Finished getMLCatergoriesAllDataAction ";
		$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
		$commonModel->sendNotificationMail($this->to, 'ML Catergories All Data Cron Finished', $this->infoMessage);
    }
	
	public function getMLCategoryHasAttributes()
	{
			try{
				$this->to = Mage::getStoreConfig("mlitems/meligeneralsetting/notificationemailid",Mage::app()->getStore());
				//Initilize logger model
				$commonModel = Mage::getModel('items/common');
			
				$this->infoMessage ="INFORMATION::Started getMLCategoryHasAttributes ";
				$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
				$commonModel->sendNotificationMail($this->to, 'ML Catergories HasAttributes Cron Started', $this->infoMessage);
				
				/** Get meli_category_id from meli_categories table */
				$melicategories = Mage::getModel('items/melicategories')->getCollection()->addFieldToFilter('has_attributes','0');
				//$melicategories->getSelect()->limit(100);
				$dataMLcat = $melicategories->getData();
				
				/**  Check for meli_categories data exist */
				try{
					if(count($dataMLcat) > 0){
						
						$dir = Mage::getBaseDir('var').DS.'category-attributes';
						
						try{
							if (!is_dir($dir)) 
							{
								if (!@mkdir($dir , 0777, true)) 
								{
								}
							}
						}catch(Exception $e){
							 $this->errorMessage ="Error::Unable to create directory (".$dir.")";
							 $commonModel->saveLogger($this->moduleName, "Error", $this->fileName, $this->errorMessage);
						}
					
					foreach($dataMLcat as $row){
					
						$meli_category_id = '';
						$meli_category_id = $row['meli_category_id'];
						$category_id = $row['category_id'];
						$service_url_category = "https://api.mercadolibre.com/categories/".$meli_category_id."/attributes";
						$json_data = $commonModel ->connect($service_url_category);
						$attributesArr = json_decode($json_data);
						try{
							if(count($attributesArr) > 0)
							{
								/**
								* Update table meli_categories for has_attributes
								*/
								$melicateUpdate = Mage::getModel('items/melicategories');
								$melicateUpdate->setCategoryId($category_id);
								$melicateUpdate->setHasAttributes('1');
								$melicateUpdate->save();
								/*Save Category Attributes Json Data*/
								$this->fileNameAttr = $meli_category_id;
								if (!@file_put_contents($dir . DS . $this->fileNameAttr . '.json',  $json_data)) {
									return false;
								}
							}else {
								$this->infoMessage ="INFORMATION::No Attributes Data Found In (Meli Category Id:".$meli_category_id.")";
								$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
							} 
						}catch(Exception $e){
							$this->errorMessage = "Error::Unable to write data in file(".$dir . DS . $this->fileNameAttr . ".json)";
							$commonModel->saveLogger($this->moduleName, "Error", $this->fileName, $this->errorMessage);
						}
					}
						
				} 
			} catch(Exception $e){
				$this->infoMessage = "INFORMATION::No Categories Data Found";
				$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
			}
		}catch(Exception $e){
				$commonModel->saveLogger($this->moduleName, "Exception", $this->fileName,$e->getMessage());
				$commonModel->sendNotificationMail($this->to, 'Exception::ML Catergories HasAttributes Cron Started', $e->getMessage());
		}
		$this->infoMessage = "INFORMATION::Finished getMLCategoryHasAttributes";
		$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
		$commonModel->sendNotificationMail($this->to, 'ML Catergories HasAttributes Cron Finished', $this->infoMessage);
	}
	
	public function getMLCategoryAttributes()
	{
			try{
				$this->to = Mage::getStoreConfig("mlitems/meligeneralsetting/notificationemailid",Mage::app()->getStore());
				
				//Initilize logger model
				$commonModel = Mage::getModel('items/common');
				$this->infoMessage = "INFORMATION::Started getMLCategoryAttributes ";
				$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
				$commonModel->sendNotificationMail($this->to, 'ML Catergories Attributes Cron Started', $this->infoMessage);
				
				/**  Get meli_category_id from meli_categories table */
				$melicategories = Mage::getModel('items/melicategories')->getCollection()->addFieldToFilter('has_attributes','1');
				//$melicategories->getSelect()->limit(1);
				$dataMLcat = $melicategories->getData();
					
				/** Check for meli_categories data exist */			
				if(count($dataMLcat) > 0){
					/** TRUNCATE TABLE meli_category_attributes and meli_category_attribute_values before Insert data */
					$dir = Mage::getBaseDir('var').DS.'category-attributes';
					$write = Mage::getSingleton('core/resource')->getConnection('core_write');
					
					$write->query("TRUNCATE TABLE `meli_category_attributes_temp`");
					$write->query("TRUNCATE TABLE `meli_category_attribute_values_temp`");
									
					foreach($dataMLcat as $row){
					
						$meli_category_id = '';
						$meli_category_id = $row['meli_category_id'];
						$category_id = $row['category_id'];
						$this->fileNameAttr = $meli_category_id;
						$dataFile = $dir . DS . $this->fileNameAttr . '.json';
						try{
							if(file_exists($dataFile) && is_readable($dataFile)) {
								$json_data = file_get_contents($dataFile);
							}
						} catch(Exception $e){
							if(file_exists($dataFile)){
								$this->errorMessage = "Error:File:(".$dataFile.") not found";
							}else{
								$this->errorMessage = "Error::Permission denied (".$dataFile.")";
							}
							$commonModel->saveLogger($this->moduleName, "Error", $this->fileName, $this->errorMessage);
						}
						$attributesArr = json_decode($json_data);

						if(count($attributesArr) > 0)
						{
							
							$i=0;
							foreach($attributesArr as $rowAttribute)
							{
								
								$rowAttribute = (array)$rowAttribute;
								$type  = (isset($rowAttribute['type']))?$rowAttribute['type']:'NULL';
								$required  = (isset($rowAttribute['tags']->required))?1:0;	
								$sql_meli_attr = '';
								$sql_meli_attr = "insert into `meli_category_attributes_temp` set category_id='".$category_id."', meli_attribute_id='".$rowAttribute['id']."',meli_attribute_name='".$rowAttribute['name']."',meli_attribute_type='".$type."',required='".$required."'";
							
								$write->query($sql_meli_attr);
								$insertId = $write->lastInsertId();

								if(count($rowAttribute['values']) > 0)
								{
									/** Insert data into meli_category_attribute_values */
									$this->getMLCategoryAttributesValue($insertId,$rowAttribute['values']);
								}

								$i++;
							}
						}
					}
					
					$write->query("TRUNCATE TABLE `meli_category_attributes`");
					$write->query("TRUNCATE TABLE `meli_category_attribute_values`");
					
					$sql_final_dump = "insert into  meli_category_attributes (category_id, meli_attribute_id, meli_attribute_name, meli_attribute_type, required) select category_id, meli_attribute_id, meli_attribute_name, meli_attribute_type, required from meli_category_attributes_temp";						
					$write->query($sql_final_dump);

					$sql_final_dump = "insert into  meli_category_attribute_values (attribute_id, meli_value_id, meli_value_name, meli_value_name_extended) select attribute_id, meli_value_id, meli_value_name, meli_value_name_extended from meli_category_attribute_values_temp";						
					$write->query($sql_final_dump);

					$commonModel->rrmdir($dir);
 
					
				}
			} catch(Exception $e){
					$commonModel->saveLogger($this->moduleName, "Exception", $this->fileName, $e->getMessage());
					$commonModel->sendNotificationMail($this->to, 'Exception::ML Catergories Attributes Cron Finished',$e->getMessage());
			}
			$this->infoMessage = "INFORMATION::Finished getMLCategoryAttributes ";
			$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
			$commonModel->sendNotificationMail($this->to, 'ML Catergories Attributes Cron Finished', $this->infoMessage);	
	}
	
	/**
	* Insert data into meli_category_attribute_values
	*/
	public function getMLCategoryAttributesValue($attributeId, $arrayAttribute = array())
	{
		try{
			$this->to = Mage::getStoreConfig("mlitems/meligeneralsetting/notificationemailid",Mage::app()->getStore());
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			foreach($arrayAttribute as $rowAttriVal){

				$rowAttriVal = (array)$rowAttriVal;
				$sql_meli_attr_vals = "insert into `meli_category_attribute_values_temp` set attribute_id='".$attributeId."', meli_value_id='".$rowAttriVal['id']."',meli_value_name='".$rowAttriVal['name']."',meli_value_name_extended=''";						
				$write->query($sql_meli_attr_vals);
		   }
	   } catch(Exception $e){
			$commonModel->saveLogger($this->moduleName, "Exception", $this->fileName, $e->getMessage());
			$commonModel->sendNotificationMail($this->to, 'Exception::ML Catergories Attributes Value', $e->getMessage());		
		}

	}
	
	public function getMLCatergoriesWithFilter()
    {	
			try{
				$this->to = Mage::getStoreConfig("mlitems/meligeneralsetting/notificationemailid",Mage::app()->getStore());
				$commonModel = Mage::getModel('items/common');
				
				$this->infoMessage ="INFORMATION:: getMLCatergoriesAllDataAction Started";
				$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
				$commonModel->sendNotificationMail($this->to, 'ML Catergories All Data Cron Started', $this->infoMessage);
				$rootCategory = Mage::getStoreConfig("mlitems/categoriesupdateinformation/mlrootcategories",Mage::app()->getStore());
				$rootCategoryArr = split(',',$rootCategory);
				if(count($rootCategoryArr) > 0){
					$write = Mage::getSingleton('core/resource')->getConnection('core_write');
					$write->query("TRUNCATE TABLE `meli_categories_filter`");
					foreach($rootCategoryArr as $key=>$value){
						$this->getMLCategoryRecursive($value);
					}
					$this->infoMessage ="INFORMATION:: Categories (".$rootCategory.") data has been filtered successfully.";
					$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
					$commonModel->sendNotificationMail($this->to, 'Categories data has been filtered successfully.', $this->infoMessage);
				} else {
					$this->infoMessage ="INFORMATION::No Category Selected TO Filter.";
					$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage); 
				}
			}catch(Exception $e){
				$commonModel->saveLogger($this->moduleName, "Exception", $this->fileName, $e->getMessage());	
			}
	}
	
	public function getMLCategoryRecursive($rootId){
			try{
				$commonModel = Mage::getModel('items/common');
				$melicategories = Mage::getModel('items/melicategories')->getCollection()->addFieldToFilter('root_id',$rootId);
				$dataMLcat = $melicategories->getData();
				if(count($dataMLcat) > 0){
					for($i=0; $i<count($dataMLcat); $i++){
						$write = Mage::getSingleton('core/resource')->getConnection('core_write');
						$sql_meli_cate_filter = '';
						$sql_meli_cate_filter = "insert into `meli_categories_filter` set meli_category_id='".$dataMLcat[$i]['meli_category_id']."', meli_category_name='".$dataMLcat[$i]['meli_category_name']."',site_id='".$dataMLcat[$i]['site_id']."',has_attributes='".$dataMLcat[$i]['has_attributes']."',root_id = '".$dataMLcat[$i]['root_id']."',listing_allowed = '".$dataMLcat[$i]['listing_allowed']."', buying_allowed = '".$dataMLcat[$i]['buying_allowed']."'";						
						$write->query($sql_meli_cate_filter);
						$this->getMLCategoryRecursive($dataMLcat[$i]['meli_category_id']);
					}
				} 
			}catch(Exception $e){
				$commonModel->saveLogger($this->moduleName, "Exception", $this->fileName, $e->getMessage());	
			}
	}
		
	public function getMLXContentCreated($service_url)
	{
		try{
			$commonModel = Mage::getModel('items/common');
			$data = $commonModel ->connect1($service_url);
			$dataArr =  explode('X-Content-Created:',$data);
			$ebay_date = substr(trim($dataArr['1']),0,26);
			return Mage::helper('items')->getMLebayDateToDateTime($ebay_date);
		} catch(Exception $e){
			$commonModel->saveLogger($this->moduleName, "Exception", $this->fileName, $e->getMessage());	
		}
	}
	
	
	
}