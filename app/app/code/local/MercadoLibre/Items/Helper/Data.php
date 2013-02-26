<?php
	
/**
* includes meli.php for get api data
*/
$meliClass = Mage::getBaseDir('code').DS. "local/mercadolibre/items/helper/meli.php";
if (file_exists($meliClass)) {
    include_once $meliClass;
}


class MercadoLibre_Items_Helper_Data extends Mage_Core_Helper_Abstract
{
	

	
   public function getMLRootId($path_from_root){
   		return $path_from_root[count($path_from_root)-1]->id;
   }	
   public function getMLebayDateToDateTime($ebay_date)
   {
   		return trim(str_replace(array("T", "Z"), array(" ", ""), $ebay_date));
   }
   
    public function getMlJsonToArray($json)
    {
        return $this->jsonDecode($json);
    }
	
    public function getMlArrayToJson($array)
    {
        return $this->jsonEncode($array);
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
        * connect ML API
        * Return json data
        * this can be use 
        * 1-custom code
        * 2-ML SDK
        * 3-Zend_Rest_Client of magento
        */
    public function connect($service_url)
    {   
		try{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_ENCODING, 1);
			return curl_exec($curl);
			curl_close($curl);
		}catch(Exception $e){
			$messgae = $e->getErrorTrace()."Exception:: Could not connect to API server";
			Mage::log($messgae,null,"meli_backup_jobs.txt");
			Mage::log($e->getMessage(),null,"meli_backup_jobs.txt");
		}
    }
    
    public function connect1($service_url)
    {
		try{
			$curl = curl_init($service_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_HEADER, 1);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl, CURLOPT_ENCODING, 1);
			curl_setopt($curl, CURLOPT_NOBODY, true); 
			return curl_exec($curl);
			curl_close($curl);
		}catch(Exception $e){
			$messgae = $e->getErrorTrace()."Exception:: Could not connect to API server";
			Mage::log($messgae,null,"meli_backup_jobs.txt");
			Mage::log($e->getMessage(),null,"meli_backup_jobs.txt");
		}
    }
	
	/**
	* meliConnect is use meli library to connect server & return data
	* we can get data in json, array 
	* $outputdata is used to return outputdata 
	* here by default this return json data  
	* $key is uri  eg. '/categories/MLB108707/attributes';  
	* Note that $key does not include domain name
	*/
	public function meliConnect($key, $outputdata='json'){
		// Create our Application instance (replace this with your appId and secret).
		try{
			$meli = new Meli(array(
			'appId'  	=> getenv('MeliPHPAppId'),
			'secret' 	=> getenv('MeliPHPSecret'),
			));
			$item = $meli -> get($key);
			if($outputdata == 'json'){
				$data = $item['body'];
			}
			if($outputdata == 'array'){
				$data = $item['json'];
			}
			return $data;
		}catch(Exception $e){
			$messgae = $e->getErrorTrace()."Exception:: Could not connect to API server";
			Mage::log($messgae,null,"meli_backup_jobs.txt");
			Mage::log($e->getMessage(),null,"meli_backup_jobs.txt");
		}
	}
	
    /**
     * Encode the mixed $valueToEncode into the JSON format
     *
     * @param mixed $valueToEncode
     * @param  boolean $cycleCheck Optional; whether or not to check for object recursion; off by default
     * @param  array $options Additional options used during encoding
     * @return string
     */

    public function jsonEncode($valueToEncode, $cycleCheck = false, $options = array())
    {
        return Zend_Json::encode($valueToEncode, $cycleCheck, $options);
    }
	
     /**
     * Decodes the given $encodedValue string which is
     * encoded in the JSON format
     *
     * @param string $encodedValue
     * @return mixed
     */
    public function jsonDecode($encodedValue, $objectDecodeType = Zend_Json::TYPE_ARRAY)
    {
        return Zend_Json::decode($encodedValue, $objectDecodeType);
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