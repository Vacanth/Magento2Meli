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
			//Initilize logger model
			$commonModel = Mage::getModel('items/common');
			$this->to = Mage::getStoreConfig("mlitems/meligeneralsetting/notificationemailid",Mage::app()->getStore());
		
			$this->infoMessage ="INFORMATION:: getMLCatergoriesAllDataAction Started";
			$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
			$commonModel->sendNotificationMail($this->to, 'ML Catergories All Data Cron Started', $this->infoMessage);

			$resCheckUpdate = array();
			$service_url = 'https://api.mercadolibre.com/sites/MLB/categories/all';
			$x_content_created = $this->getMLXContentCreated($service_url);
			$resMelicategoryupdate = Mage::getModel('items/melicategoryupdate')->getCollection()->addFieldToFilter('created_datetime',$x_content_created);
			$resCheckUpdate = $resMelicategoryupdate->getData();
			
			if(empty($resCheckUpdate)){
				$resCheckUpdate['0']['update_id'] = 0;
			}
			
			try{
			  
			   if(trim($resCheckUpdate['0']['update_id']) == '' || trim($resCheckUpdate['0']['update_id']) == '0'){
			 
				$fileName = 'allCategoryJsonData';
				$dir = Mage::getBaseDir('code').DS.'local\MercadoLibre\dump\category';
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
					if (!@file_put_contents($dir . DS . $fileName . '.txt',  $data)) {
						return false;
					}
				}catch(Exception $e){
						$this->errorMessage = "Error::Unable to write data in file(".$dir . DS . $fileName ."txt)";
						$commonModel->saveLogger($this->moduleName, "Error", $this->fileName, $this->errorMessage);
				}
				try{
					$dataFile = $dir . DS . $fileName . '.txt';
					if (file_exists($dataFile) && is_readable($dataFile)) {
						$dataFileData  = file_get_contents($dataFile);
					}
				}catch(Exception $e){
						$this->errorMessage = "Error::Unable to read data in file(".$dir . DS . $fileName ."txt)";
						$commonModel->saveLogger($this->moduleName, "Error", $this->fileName, $this->errorMessage);
				}
				/* Get Json data to array*/
			    $dataArr = json_decode($dataFileData);
			    $i=0;
				$catList = array();
				if(count($dataArr) > 0){
					foreach($dataArr as $row)
					{
						$site_id = 'NULL';
						$site_id = substr($row->id,0,3);
						$root_id = (Mage::helper('items')->getMLRootId($row->path_from_root)) ? Mage::helper('items')->getMLRootId($row->path_from_root):0;
						$has_attributes = 0;	
						$listing_allowed = (isset($row->settings->listing_allowed) && $row->settings->listing_allowed == true)?$row->settings->listing_allowed:0;	
						$buying_allowed = (isset($row->settings->buying_allowed) && $row->settings->buying_allowed == true)?$row->settings->buying_allowed:0;						
						$catList[] = array('NULL', $row->id,$row->name, $site_id, $has_attributes,$root_id,$listing_allowed,$buying_allowed);
						$i++;
					}
					/* get category data into meli_categories.csv*/
					$fp = fopen($dir . DS.'meli_categories.csv', 'w');
					foreach ($catList as $fields) {
						fputcsv($fp, $fields);
					}
					fclose($fp);  
					
					try{				

						/** TRUNCATE TABLE `meli_categories` to add new data again */
						$write = Mage::getSingleton('core/resource')->getConnection('core_write');
						$write->query("TRUNCATE TABLE `meli_categories`");

						/* import category data into meli_categories*/
						$db = Mage::getSingleton('core/resource')->getConnection('core_write');
						$filename = implode('/',split('[\]',Mage::getBaseDir('code').DS.'local\MercadoLibre\dump\category\meli_categories.csv'));
						$sql = "LOAD DATA  INFILE '".$filename."' INTO TABLE `meli_categories` FIELDS TERMINATED BY ',' lines terminated by '\n'";
						$db->query($sql);
						
						
						
					} catch(PDOException $e){
						$this->errorMessage = $e->getTrace()."::".$e->getMessage();
						$commonModel->saveLogger($this->moduleName, "PDOException", $this->fileName, $this->errorMessage);
					}
					
				}
				$runDateTime = date('Y-m-d h:i:s', time());
				$melicategoryupdate = Mage::getModel('items/melicategoryupdate');
				$melicategoryupdate->setCreatedDatetime($x_content_created);
				$melicategoryupdate->setRunDatetime($runDateTime);
				$melicategoryupdate->save();
				$write->query("UPDATE core_config_data  set value = '".$x_content_created."' where path='mlitems/categoriesupdateinformation/contentcreationdate'"); 
				$write->query("UPDATE core_config_data  set value = '".$runDateTime."' where path='mlitems/categoriesupdateinformation/lastrundata'"); 

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
						
						$dir = Mage::getBaseDir('code').DS.'local\MercadoLibre\dump\category-attributes';
						
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
					$dir =Mage::getBaseDir('code').DS.'local\MercadoLibre\dump\category-attributes';
					$write = Mage::getSingleton('core/resource')->getConnection('core_write');
					
					$write->query("TRUNCATE TABLE `meli_category_attributes_temp`");
					$write->query("TRUNCATE TABLE `meli_category_attribute_values_temp`");
					
					$last_attribute_id = 0;	 			
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
							
							
							$sql_meli_attr = '';
							foreach($attributesArr as $rowAttribute)
							{
								$rowAttribute = (array)$rowAttribute;
								if(isset($rowAttribute['name']) && trim($rowAttribute['name'])!=''){
									$last_attribute_id++;
									$type  = (isset($rowAttribute['type']))?$rowAttribute['type']:'NULL';
									$required  = (isset($rowAttribute['tags']->required))?1:0;	
									$sql_meli_attr .= "insert into `meli_category_attributes_temp` set attribute_id ='".$last_attribute_id."', category_id='".$category_id."', meli_attribute_id='".$rowAttribute['id']."',meli_attribute_name='".$rowAttribute['name']."',meli_attribute_type='".$type."',required='".$required."'".";";	
									/* Last inserted id for this meli_category_attributes_temp */							
									$insertId = $last_attribute_id;
									if(is_array($rowAttribute['values']) && count($rowAttribute['values']) > 0)
									{
										/** Insert data into meli_category_attribute_values */
										$sql_meli_attr .= $this->getMLCategoryAttributesValue($insertId,$rowAttribute['values']);
									}
								}
							}
							$write->multiQuery($sql_meli_attr);
						}
					}
					
					$write->query("TRUNCATE TABLE `meli_category_attributes`");
					$write->query("TRUNCATE TABLE `meli_category_attribute_values`");
					
					$sql_final_dump = "insert into  meli_category_attributes (category_id, meli_attribute_id, meli_attribute_name, meli_attribute_type, required) select category_id, meli_attribute_id, meli_attribute_name, meli_attribute_type, required from meli_category_attributes_temp";						
					$write->query($sql_final_dump);

					$sql_final_dump = "insert into  meli_category_attribute_values (attribute_id, meli_value_id, meli_value_name, meli_value_name_extended) select attribute_id, meli_value_id, meli_value_name, meli_value_name_extended from meli_category_attribute_values_temp";						
					$write->query($sql_final_dump);

					//$commonModel->rrmdir($dir);
 
					
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
			$commonModel = Mage::getModel('items/common');
			$this->to = Mage::getStoreConfig("mlitems/meligeneralsetting/notificationemailid",Mage::app()->getStore());
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$sql_meli_attr_vals = '';
			foreach($arrayAttribute as $rowAttriVal){
				$rowAttriVal = (array)$rowAttriVal;
				if(isset($rowAttriVal['name']) && trim($rowAttriVal['name'])!=''){
					$sql_meli_attr_vals .= "insert into `meli_category_attribute_values_temp` set attribute_id='".$attributeId."', meli_value_id='".$rowAttriVal['id']."',meli_value_name='".$rowAttriVal['name']."',meli_value_name_extended=''".";";		
				}				
		   }
		   return $sql_meli_attr_vals;
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
				
				$this->infoMessage ="INFORMATION:: getMLCatergoriesWithFilter Started";
				$commonModel->saveLogger($this->moduleName, "Information", $this->fileName, $this->infoMessage);
				$commonModel->sendNotificationMail($this->to, 'ML Catergories All Data Cron Started', $this->infoMessage);
				/* Get Root Category To Be Filter */
				$rootCategory = Mage::getStoreConfig("mlitems/categoriesupdateinformation/mlrootcategories",Mage::app()->getStore());
				$rootCategoryArr = split(',',$rootCategory);
				/* Category To Be Filter Start */
				if(count($rootCategoryArr) > 0){
					$write = Mage::getSingleton('core/resource')->getConnection('core_write');
					$write->query("TRUNCATE TABLE `meli_categories_filter`");
					foreach($rootCategoryArr as $key=>$value){
						$insert_root_categories = '';
						/* Save all child for this categoty */
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
				 if(isset($rootId) && trim($rootId)!=''){
					$commonModel = Mage::getModel('items/common');
					$write = Mage::getSingleton('core/resource')->getConnection('core_write');
					$sql_meli_cate_filter = '';
					$sql_meli_cate_filter = "insert into  meli_categories_filter (meli_category_id, meli_category_name, site_id, 	has_attributes, root_id, listing_allowed, 	buying_allowed) select meli_category_id, meli_category_name, site_id, 	has_attributes, root_id, listing_allowed, 	buying_allowed from meli_categories where meli_category_id ='".$rootId."'".";";	
					$write->query($sql_meli_cate_filter);
					$melicategories = Mage::getModel('items/melicategories')->getCollection()->addFieldToFilter('root_id',$rootId);
					
					$dataMLcat = $melicategories->getData();
					if(count($dataMLcat) > 0){
						for($i=0; $i<count($dataMLcat); $i++){
							 $this->getMLCategoryRecursive($dataMLcat[$i]['meli_category_id']);
						}
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