<?php

class MercadoLibre_Items_Block_Adminhtml_Items_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
  protected function _prepareForm()
  {
      $form = new Varien_Data_Form();
      $this->setForm($form);
      $fieldset = $form->addFieldset('items_form', array('legend'=>Mage::helper('items')->__('Item information')));
     
      $fieldset->addField('title', 'text', array(
          'label'     => Mage::helper('items')->__('Title'),
          'class'     => 'required-entry',
          'required'  => true,
          'name'      => 'title',
      ));

      $fieldset->addField('filename', 'file', array(
          'label'     => Mage::helper('items')->__('File'),
          'required'  => false,
          'name'      => 'filename',
	  ));
		
      $fieldset->addField('status', 'select', array(
          'label'     => Mage::helper('items')->__('Status'),
          'name'      => 'status',
          'values'    => array(
              array(
                  'value'     => 1,
                  'label'     => Mage::helper('items')->__('Enabled'),
              ),

              array(
                  'value'     => 2,
                  'label'     => Mage::helper('items')->__('Disabled'),
              ),
          ),
      ));
     
      $fieldset->addField('content', 'editor', array(
          'name'      => 'content',
          'label'     => Mage::helper('items')->__('Content'),
          'title'     => Mage::helper('items')->__('Content'),
          'style'     => 'width:700px; height:500px;',
          'wysiwyg'   => false,
          'required'  => true,
      ));
     
      if ( Mage::getSingleton('adminhtml/session')->getItemsData() )
      {
          $form->setValues(Mage::getSingleton('adminhtml/session')->getItemsData());
          Mage::getSingleton('adminhtml/session')->setItemsData(null);
      } elseif ( Mage::registry('items_data') ) {
          $form->setValues(Mage::registry('items_data')->getData());
      }
      return parent::_prepareForm();
  }
}