<?php

class MercadoLibre_Items_Model_Mysql4_MeliCategoryAttributeValues_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('items/melicategoryattributevalues');
    }
}