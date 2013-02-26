<?php

class MercadoLibre_Items_Block_Adminhtml_Items_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

  public function __construct()
  {
      parent::__construct();
      $this->setId('items_tabs');
      $this->setDestElementId('edit_form');
      $this->setTitle(Mage::helper('items')->__('Item Information'));
  }

  protected function _beforeToHtml()
  {
      $this->addTab('form_section', array(
          'label'     => Mage::helper('items')->__('Item Information'),
          'title'     => Mage::helper('items')->__('Item Information'),
          'content'   => $this->getLayout()->createBlock('items/adminhtml_items_edit_tab_form')->toHtml(),
      ));
     
      return parent::_beforeToHtml();
  }
}