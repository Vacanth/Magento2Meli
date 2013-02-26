<?php

class MercadoLibre_Items_Model_Comman extends Varien_Object
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('items/comman');
    }
	
	/*
		This function will be called by any module in magento to register their log on custom
		log table
	*/
	public function saveLogger($moduleKey, $status, $fileName, $description)
	{
	
		//Check logger setting enable/disable
		$enableLoggerMethod = Mage::getStoreConfig("logger/loggersystem/loggeresystem_enable_disable",Mage::app()->getStore());
		if($enableLoggerMethod){
			$description = htmlspecialchars($description, ENT_QUOTES);
			//magento date time
			$currentTime =Mage::helper('fulfillment');
			$time = $currentTime->getCurrentDateTime('Y-m-d H:i:s');
			//Check logger setting for files system or database
			$loggerMethod = Mage::getStoreConfig("logger/loggersystem/loggeresystem_id",Mage::app()->getStore());
			if($loggerMethod){
				Mage::log($time." | ".$moduleKey." | ".$status." | ".$fileName." | ".$description, Zend_Log::DEBUG, 'fulfillment_logger');
			}else{
				$logger = Mage::getModel('logger/logger')
						->setLogTime($time)
						->setStatus($status)
						->setModuleName($moduleKey)
						->setDescription($description)
						->setFilename($fileName)
						->save();
			}
		}
		
		
	}
	
	/*
		sendNotificationMail function will send mail to the admin for any log or cron update
	*/
	public function sendNotificationMail($to='', $mailSubject, $message)
	{
	    $toName = "";
		$mailTemplate = Mage::getModel('core/email_template');
        $translate  = Mage::getSingleton('core/translate');
		
		//magento template id for email notification
		$templateId = Mage::getStoreConfig("logger/loggeremail/loggeremail_id",Mage::app()->getStore());
		if(empty($templateId)){
			$templateId = 6; 
		}
	
		$emailTemplate  = Mage::getModel('core/email_template')->load($templateId);  
		
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
	
	
}