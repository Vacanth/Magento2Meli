<?php

class MercadoLibre_Items_Model_Observer
{
    public function getMLCatergoriesAllData($observer)
    {
		Mage::getModel('items/melicategories')->getMLCatergoriesAllData();
    }

    public function getMLCategoryAttributes($observer)
	{
	  Mage::getModel('items/melicategories')->getMLCategoryAttributes();

    }
   
   public function getCleanUpLog($observer){
   		 Mage::getModel('items/common')->getCleanUpLog();
   }
   
}