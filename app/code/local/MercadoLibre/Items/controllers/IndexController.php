<?php  
ini_set('max_execution_time',0);
ini_set('memory_limit','512M');
ini_set('max_input_time',0);
date_default_timezone_set('Asia/Kolkata');


class MercadoLibre_Items_IndexController extends Mage_Core_Controller_Front_Action
{
	
	private $moduleName = "Items";
	private $fileName = "IndexController.php";
	
	//message variable
	private $infoMessage = "";
	private $errorMessage = "";
	private $successMessage = "";
	private $to ='';

	//private $to = 'gupta.p@indiabulls.com';
	
	public function indexAction()
	{	
		$this->to = Mage::getStoreConfig("mlitems/meligeneralsetting/notificationemailid",Mage::app()->getStore());
		$commonModel = Mage::getModel('items/common');
		$commonModel->sendNotificationMail($this->to, 'Cron Jobs', 'Notification Message Success');
		exit;	
		$this->loadLayout();
		$this->renderLayout();
	}

    public function getMLCatergoriesAllDataAction()
    {
		$melicategoriesModel = Mage::getModel('items/melicategories');
		$melicategoriesModel -> getMLCatergoriesAllData();
	}
	
	public function getMLCategoryHasAttributesAction()
	{
		$melicategoriesModel = Mage::getModel('items/melicategories');
		$melicategoriesModel -> getMLCategoryHasAttributes();
	}
	
	public function getMLCategoryAttributesAction()
	{
		$melicategoriesModel = Mage::getModel('items/melicategories');
		$melicategoriesModel -> getMLCategoryAttributes();
	}
	
	public function getCleanUpLogAction(){	
			$commonModel = Mage::getModel('items/common');
			$commonModel->getCleanUpLog();
	}
	
	public function getMLCatergoriesWithFilterAction(){
			$melicategoriesModel = Mage::getModel('items/melicategories');
			$melicategoriesModel -> getMLCatergoriesWithFilter();
	}
}