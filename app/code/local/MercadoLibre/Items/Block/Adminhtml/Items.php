<?php
class MercadoLibre_Items_Block_Adminhtml_Items extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {
    $this->_controller = 'adminhtml_items';
    $this->_blockGroup = 'items';
    $this->_headerText = Mage::helper('items')->__('Item Manager');
    $this->_addButtonLabel = Mage::helper('items')->__('Add Item');
    parent::__construct();
  }
}