<?php

class MercadoLibre_Items_Helper_Data extends Mage_Core_Helper_Abstract
{
	public $datetimeformat = 'Y-m-d H:i:s';

 	 /*
	 * Return the current date in format provided
	 * 
	 * @params string
	 * @return string
	 */
	
	public function getCurrentDateTime($format="")
	{
		if (empty($format)) {
			$format = $this->datetimeformat;
		}
		
		
		$store_id = Mage::app()->getStore()->getId(); 
		$storeTimestamp = Mage::app()->getLocale()->storeTimeStamp($store_id);
		
		$dt = date($format,$storeTimestamp);
		//print $dt = Mage::getModel('core/date')->date($format);die;
		
		return $dt;
	}
  
  /* Get category root_id */		
   public function getMLRootId($path_from_root){
   		if(count($path_from_root) > 1){
   			return $path_from_root[count($path_from_root)-2]->id;
		} else {
			return false;
		}
   }
   	
   public function getMLebayDateToDateTime($ebay_date)
   {
   		return trim(str_replace(array("T", "Z"), array(" ", ""), $ebay_date));
   }
	
    public function getMlXmlToArray($fileName = 'test')
    {
        $dir = Mage::getBaseDir('var').DS.'xml';	
        $xmlFile = $dir . DS . $fileName . '.xml';	
        if (file_exists($xmlFile) && is_readable($xmlFile)) {
            $xml  = simplexml_load_file($xmlFile);
            $data = $this->xmlToAssoc($xml);
            if (!empty($data)) {
                return $data;
            }
        }

    }
	
    public function getMlArrayToXml($array,$fileName = 'test', $rootName='config')
    {
        $xml = $this->assocToXml($array,$rootName);
        $dir = Mage::getBaseDir('var').DS.'xml';

        // prepare dir to save
        $parts = explode(DS, $fileName);
        array_pop($parts);
        $newDir = implode(DS, $parts);
        if ((!empty($newDir)) && (!is_dir($dir . DS . $newDir))) {
            if (!@mkdir($dir . DS . $newDir, 0777, true)) {
                return false;
            }
        }
        if (!@file_put_contents($dir . DS . $fileName . '.xml', $xml->asXML())) {
            return false;
        }
        return true;
    }

     /**
     * Transform an assoc array to SimpleXMLElement object
     * Array has some limitations. Appropriate exceptions will be thrown
     * @param array $array
     * @param string $rootName
     * @return SimpleXMLElement
     * @throws Exception
     */
    public function assocToXml(array $array, $rootName = '_')
    {
        if (empty($rootName) || is_numeric($rootName)) {
            throw new Exception('Root element must not be empty or numeric');
        }

$xmlstr = <<<XML
<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<$rootName></$rootName>
XML;
        $xml = new SimpleXMLElement($xmlstr);
        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                throw new Exception('Array root keys must not be numeric.');
            }
        }
        return self::_assocToXml($array, $rootName, $xml);
    }

    /**
     * Function, that actually recursively transforms array to xml
     *
     * @param array $array
     * @param string $rootName
     * @param SimpleXMLElement $xml
     * @return SimpleXMLElement
     * @throws Exception
     */
    private function _assocToXml(array $array, $rootName, SimpleXMLElement &$xml)
    {
        $hasNumericKey = false;
        $hasStringKey  = false;
        foreach ($array as $key => $value) {
            if (!is_array($value)) {
                if (is_string($key)) {
                    if ($key === $rootName) {
                        throw new Exception('Associative key must not be the same as its parent associative key.');
                    }
                    $hasStringKey = true;
                    $xml->$key = $value;
                }
                elseif (is_int($key)) {
                    $hasNumericKey = true;
                    $xml->{$rootName}[$key] = $value;
                }
            }
            else {
                self::_assocToXml($value, $key, $xml->$key);
            }
        }
        if ($hasNumericKey && $hasStringKey) {
            throw new Exception('Associative and numeric keys must not be mixed at one level.');
        }
        return $xml;
    }
	
    public function xmlToAssoc(SimpleXMLElement $xml)
    {
        $array = array();
        foreach ($xml as $key => $value) {
            if (isset($value->$key)) {
                $i = 0;
                foreach ($value->$key as $v) {
                    $array[$key][$i++] = (string)$v;
                }
            }
            else {
                // try to transform it into string value, trimming spaces between elements
                $array[$key] = trim((string)$value);
                if (empty($array[$key]) && !empty($value)) {
                    $array[$key] = self::xmlToAssoc($value);
                }
                // untrim strings values
                else {
                    $array[$key] = (string)$value;
                }
            }
        }
        return $array;
    }
	
	

}