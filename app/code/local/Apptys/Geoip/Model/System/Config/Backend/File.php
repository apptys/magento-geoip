<?php
/**
 * Data helper for Geoip
 *
 * @author      Brandon Johnson <brandon@apptys.com>
 * @category    Apptys
 * @package     Geoip
 */

class Apptys_Geoip_Model_System_Config_Backend_File extends Mage_Adminhtml_Model_System_Config_Backend_File
{
    /**
     * Getter for allowed extensions of uploaded files
     *
     * @return array
     */
    protected function _getAllowedExtensions()
    {
        return array('dat');
    }

    /**
     * Makes a decision about whether to add info about the scope.
     *
     * @return boolean
     */
    protected function _addWhetherScopeInfo()
    {
        return false;
    }

    /**
     * Return the root part of directory path for uploading
     *
     * @var string
     * @return string
     */
    protected function _getUploadRoot($token)
    {
        return Mage::getBaseDir('var');
    }
}
