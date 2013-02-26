<?php
class MercadoLibre_Items_Block_Items extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getItems()     
     { 
        if (!$this->hasData('items')) {
            $this->setData('items', Mage::registry('items'));
        }
        return $this->getData('items');
        
    }
}