<?php
class MercadoLibre_Items_Model_System_Config_Source_RootCategories
{
    protected $_options;

    public function toOptionArray($isMultiselect)
    {
        if (!$this->_options) {
			$melicategories = Mage::getModel('items/melicategories')->getCollection()->addFieldToFilter('root_id','0');
			$dataMLRootCat = $melicategories->getData();
            $this->_options = array();
            foreach( $dataMLRootCat as $row ) {
					$this->_options[] = array(
						'label' => $row['meli_category_name'],
						'value' => $row['meli_category_id'],
					);
				}
        }

        $options = $this->_options;
        return $options;
    }

}
