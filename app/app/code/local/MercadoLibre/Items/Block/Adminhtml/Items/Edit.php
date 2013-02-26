<?php

class MercadoLibre_Items_Block_Adminhtml_Items_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
                 
        $this->_objectId = 'id';
        $this->_blockGroup = 'items';
        $this->_controller = 'adminhtml_items';
        
        $this->_updateButton('save', 'label', Mage::helper('items')->__('Save Item'));
        $this->_updateButton('delete', 'label', Mage::helper('items')->__('Delete Item'));
		
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('items_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'items_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'items_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    public function getHeaderText()
    {
        if( Mage::registry('items_data') && Mage::registry('items_data')->getId() ) {
            return Mage::helper('items')->__("Edit Item '%s'", $this->htmlEscape(Mage::registry('items_data')->getTitle()));
        } else {
            return Mage::helper('items')->__('Add Item');
        }
    }
}