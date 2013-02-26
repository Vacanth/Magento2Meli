<?php

class MercadoLibre_Items_Model_MeliCategoryAttributeValues extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('items/melicategoryattributevalues');
    }
}