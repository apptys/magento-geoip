<?php
/**
 * Data helper for Geoip
 *
 * @author      Brandon Johnson <brandon@apptys.com>
 * @category    Apptys
 * @package     Geoip
 *
 * @method string getCity()
 * @method int|string getRegion()
 * @method string getPostalCode()
 * @method string getCountryCode()
 */

class Apptys_Geoip_Helper_Data extends Mage_Core_Helper_Data
{
    /** @var string server path to file directory */
    protected $_fileDirectory;

    /** @var array of address records by ip */
    protected $_records = array();

    /**
     * Define path to this module files dir
     */
    public function __construct()
    {
        $this->_fileDirectory = Mage::getModuleDir('includes', 'Apptys_Geoip') . DS . 'files' . DS;
    }

    /**
     * Sets country, region and post code by Geo IP
     *
     * @param Mage_Sales_Model_Quote_Address $address
     */
    public function updateAddressByGeoIp(Mage_Sales_Model_Quote_Address $address)
    {
        $address->setRegionId($this->getRegion($address->getRegionId()))
            ->setPostcode($this->getPostalCode($address->getPostcode()))
            ->setCountryId($this->getCountryCode($address->getCountryId()));
    }

    /**
     * Get the address record for the current ip
     *
     * @param string $ip
     * @return bool
     */
    protected function _init($ip)
    {
        if ($ip == '127.0.0.1') {
            $this->_records[$ip] = new stdClass();
            return;
        }

        if (!@include($this->_fileDirectory . Apptys_Geoip_Model_Geoip::FILE_GEOIP_CITY)) {
            Mage::logException('Failed to include GeoIP city location script');
            return;
        } else {
            $geoip = geoip_open($this->_getGeoipData(), GEOIP_STANDARD);
            $record = geoip_record_by_addr($geoip, $ip);

            if (is_null($record)) {
                $record = new stdClass();
            }

            $this->_records[$ip] = $record;
            geoip_close($geoip);
            return;
        }
    }

    /**
     * Get the uploaded geoip data file or default
     *
     * @return string
     */
    protected function _getGeoipData()
    {
        $default = $this->_fileDirectory . Apptys_Geoip_Model_Geoip::FILE_GEOIP_DATA;

        $file = Mage::getStoreConfig('apptys_geoip/upload/datafile');
        if (!empty($file)) {
            $filePath = Mage::getBaseDir('var') . DS . 'geoip' . DS . $file;
            if (file_exists($filePath)) {
                return $filePath;
            }
        }

        return $default;
    }

    /**
     * Get the ip and pass it to the get record function
     *
     * @return stdClass object
     */
    protected function _getRecord()
    {
        $ip = $this->_getIp();
        //override ip detection here for local testing; uncomment next line
        //$ip = '8.26.83.254';

        if (empty($this->_records[$ip])) {
            $this->_init($ip);
        }

        return $this->_records[$ip];
    }

    /**
     * Get the user ip address
     *
     * @return string
     */
    protected function _getIp()
    {
        $ip = Mage::helper('core/http')->getRemoteAddr();

        return $ip;
    }

    /**
     * Magic method for getting record values
     *
     * @param $method
     * @param $args
     * @return param value if exists
     */
    public function __call($method, $args)
    {
        $value = isset($args[0]) ? $args[0] : null;
        if (!empty($value)) {
            return $value;
        }

        switch (substr($method, 0, 3)) {
            case 'get' :
                $param = substr($method, 3);
                $param = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $param));
                break;
            default:
                return '';
        }

        $record = $this->_getRecord();

        if ($param == 'region') {
            return $this->_getRegion($record);
        }

        if ($param == 'country_code') {
            return $this->_getCountryCode($record);
        }

        return isset($record->$param) ? Mage::helper('core')->escapeHtml((string)$record->$param) : '';
    }

    /**
     * Get region id by code and country
     *
     * @param stdClass object $record
     * @return int|string
     */
    protected function _getRegion($record)
    {
        if (empty($record->region)) {
            return '';
        }

        /** @var $regions Mage_Directory_Model_Resource_Region_Collection */
        $regions = Mage::getModel('directory/region')
            ->getCollection()
            ->addFieldToFilter('code', array('eq' => (string)$record->region))
            ->addFieldToFilter('country_id', array('eq' => (string)$record->country_code))
            ->getColumnValues('region_id');

        if (count($regions)) {
            return $regions[0];
        }

        return (string)$record->region;
    }

    /**
     * Get country code or default country
     *
     * @param stdClass object $record
     * @return int|string
     */
    protected function _getCountryCode($record)
    {
        if (empty($record->country_code)) {
            $default = Mage::getStoreConfig('general/country/default');
            return $default;
        }

        return $record->country_code;
    }
}
