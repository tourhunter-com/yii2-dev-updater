<?php

namespace tourhunter\devUpdater;

/**
 * Class InfoStorage
 *
 * @package tourhunter\devUpdater
 */
class InfoStorage
{

    const INFO_LAST_UPDATE_ERRORS = 'last-update-errors';

    /**
     * @var null|array
     */
    protected $_lastUpdateInfo = null;

    /**
     * @var string
     */
    protected $_infoFilename;

    /**
     * InfoStorage constructor.
     *
     * @param $infoFilename
     */
    public function __construct($infoFilename)
    {
        $this->_infoFilename = $infoFilename;
        $this->_loadLastUpdateInfo();
    }

    /**
     * get update time info
     *
     * @param $key
     * @param null $default
     *
     * @return null
     */
    public function getLastUpdateInfo($key, $default = null)
    {
        return isset($this->_lastUpdateInfo[$key]) ? $this->_lastUpdateInfo[$key] : $default;
    }

    /**
     * set update time info
     *
     * @param $key
     * @param $value
     */
    public function setLastUpdateInfo($key, $value)
    {
        $this->_lastUpdateInfo[$key] = $value;
    }

    /**
     * save update times data to lock file
     */
    public function saveLastUpdateInfo()
    {
        file_put_contents(\Yii::getAlias($this->_infoFilename), json_encode($this->_lastUpdateInfo));
    }

    /**
     * @param $error
     */
    public function addErrorInfo($error)
    {
        if (!isset($this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS])) {
            $this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS] = [];
        }
        $this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS][] = $error;
    }

    /**
     * @return string[]
     */
    public function getLastErrorsInfo()
    {
        if (!isset($this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS])) {
            $this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS] = [];
        }

        return $this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS];
    }

    /**
     * Erase all errors from state
     */
    public function skipLastErrors()
    {
        if (isset($this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS])) {
            unset($this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS]);
        }
    }

    /**
     * load update times data from file
     */
    protected function _loadLastUpdateInfo()
    {
        $infoFilename = \Yii::getAlias($this->_infoFilename);
        if (file_exists($infoFilename)) {
            $lastUpdateInfoFileContent = file_get_contents(\Yii::getAlias($infoFilename));
            $this->_lastUpdateInfo = json_decode($lastUpdateInfoFileContent, true);
        }
        if (is_null($this->_lastUpdateInfo)) {
            $this->_lastUpdateInfo = [];
        }
    }

}