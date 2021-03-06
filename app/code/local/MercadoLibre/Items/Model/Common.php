<?php  
/** includes meli.php for get api data */
$meliClass = Mage::getBaseDir('code').DS. "local/mercadolibre/items/helper/meli.php";
if (file_exists($meliClass)) {
    include_once $meliClass;
}

class MercadoLibre_Items_Model_Common extends Mage_Core_Model_Abstract
{
	private $moduleName = "Items";
	private $fileName = "Common.php";
	private $loggerFileName =  'meli_logger';
	
	//message variable
	private $infoMessage = "";
	private $errorMessage = "";
	private $successMessage = "";

	const LOGGERESYSTEM_ID = 'Yes'; 

	
    public function _construct()
    {
        parent::_construct();
        $this->_init('items/common');
    }
	
	/**
	* connect ML API
	* Return json data
	* this can be use 
	* 1-custom code
	* 2-ML SDK
	* 3-Zend_Rest_Client of magento
	*/
    public function connect($service_url)
    {   
		try{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_ENCODING, 1);
			$data = curl_exec($curl);
			$dataArr = (array) json_decode($data);
			if(isset($dataArr['error']) && trim($dataArr['error'])!=''){
				$this->errorMessage = 'Error :: '.$dataArr['status'].' '.$dataArr['message'];
				$this->saveLogger($this->moduleName, "Error", $this->fileName, $this->errorMessage);
				$this->sendNotificationMail($to='', 'Connection Error Report', $this->errorMessage);
			}
			return $data;
			curl_close($curl);
		}catch(Exception $e){		
			$this->errorMessage = $e->getErrorTrace()."Exception:: Could not connect to API server";
			$this->saveLogger($this->moduleName, "Exception", $this->fileName, $this->errorMessage);
		}
    }
    
    public function connect1($service_url)
    {
		try{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_HEADER, 1);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_ENCODING, 1);
			curl_setopt($curl, CURLOPT_NOBODY, true); 
			return curl_exec($curl);
			curl_close($curl);
		}catch(Exception $e){
			$this->errorMessage = $e->getErrorTrace()."Exception:: Could not connect to API server";
			$this->saveLogger($this->moduleName, "Exception", $this->fileName, $this->errorMessage);
		}
    }
	
	/**
	* meliConnect is use meli library to connect server & return data
	* we can get data in json, array 
	* $outputdata is used to return outputdata 
	* here by default this return json data  
	* $key is uri  eg. '/categories/MLB108707/attributes';  
	* Note that $key does not include domain name
	*/
	public function meliConnect($key, $outputdata='json'){
		// Create our Application instance (replace this with your appId and secret).
		try{
			$meli = new Meli(array(
			'appId'  	=> getenv('MeliPHPAppId'),
			'secret' 	=> getenv('MeliPHPSecret'),
			));
			$item = $meli -> get($key);
			if(isset($item['json']['error']) && trim($item['json']['error'])!=''){
				$this->errorMessage = 'Error :: '.$item['json']['status'].' '.$item['json']['message'];
				$this->saveLogger($this->moduleName, "Error", $this->fileName, $this->errorMessage);
				$this->sendNotificationMail($to='', 'Connection Error Report', $this->errorMessage);
			}
			if($outputdata == 'json'){
				$data = $item['body'];
			}
			if($outputdata == 'array'){
				$data = $item['json'];
			}
			return $data;
		}catch(Exception $e){
			$this->errorMessage = $e->getErrorTrace()."Exception:: Could not connect to API server";
			$this->saveLogger($this->moduleName, "Exception", $this->fileName, $this->errorMessage);
		}
	}
	
	/*
		This function will be called by any module in magento to register their log on custom
		log table
	*/
	public function saveLogger($moduleKey, $status, $fileName, $description)
	{
	
		//Check logger setting enable/disable
		$enableLoggerMethod = Mage::getStoreConfig('mlitems/meligeneralsetting/enablelogging',Mage::app()->getStore());;
		if($enableLoggerMethod){
			$description = htmlspecialchars($description, ENT_QUOTES);
			//magento date time
			$currentTime =Mage::helper('items');
			$time = $currentTime->getCurrentDateTime('Y-m-d H:i:s');
			//Check logger setting for files system or database
			$loggerMethod = self::LOGGERESYSTEM_ID;
			
			if($loggerMethod){
				//Mage::log($time." | ".$moduleKey." | ".$status." | ".$fileName." | ".$description, Zend_Log::DEBUG, $this->loggerFileName);
				Mage::log($description, Zend_Log::DEBUG, $this->loggerFileName);
			}
		}
		
		
	}
	
	/*
		sendNotificationMail function will send mail to the admin for any log or cron update
	*/
	public function sendNotificationMail($to='', $mailSubject, $message)
	{
	   
	   if(trim($to) == ''){ 
	   		/* Admin email id */
			$to = Mage::getModel('admin/user')->load('1')->getEmail();
		}
		$toName = "";
		$mailTemplate = Mage::getModel('core/email_template');
        $translate  = Mage::getSingleton('core/translate');

		//magento template_code for email notification
		$emailTemplate  = Mage::getModel('core/email_template')->loadDefault('meli_notification-template');  

		$from_email = Mage::getStoreConfig('trans_email/ident_general/email',Mage::app()->getStore()); //fetch sender email
        $from_name = Mage::getStoreConfig('trans_email/ident_general/name',Mage::app()->getStore()); //fetch sender name
		
		$emailTemplate->setSenderName($from_name);
		$emailTemplate->setSenderEmail($from_email);
		$emailTemplate->setTemplateSubject($mailSubject);

		$emailTemplateVariables = array('message'=>$message);
		$processedTemplate = $emailTemplate->getProcessedTemplate($emailTemplateVariables);	
		if($emailTemplate) {
			try {
				$emailTemplate->send($to,$toName, $emailTemplateVariables);
				Mage::getSingleton('adminhtml/session')->addSuccess('Notification email sent.');
			}catch(Exception $e){
				return false;
			}
			return true;
		}
                
	}
	
	function rrmdir($dir) {
	
		 foreach(glob($dir . '/*') as $file) {
			 if(is_dir($file))
				 rrmdir($file);
			 else
				 unlink($file);
		 }
		 rmdir($dir);
	 
   }
   
   public function getCleanUpLog(){
   		try{
			$commonModel = Mage::getModel('items/common');
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$res = $write->fetchAll("select DATEDIFF(CURDATE(),DATE_FORMAT(run_datetime,'%Y-%m-%d')) as days from meli_category_update");	
			if(trim($res['0']['days']) == Mage::getStoreConfig("mlitems/meligeneralsetting/logcleanup",Mage::app()->getStore())){
				$dir = Mage::getBaseDir('var').DS.'log'.DS.$this->loggerFileName;
				if(is_file($dir)){
					@unlink($dir);
				}
			}
		} catch(Exception $e){
				 $commonModel->saveLogger($this->moduleName, "Exception", $this->fileName, $e->getMessage());
		}
	}	
}