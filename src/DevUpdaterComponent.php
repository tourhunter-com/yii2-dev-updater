<?php

namespace tourhunter\devUpdater;

use yii\base\Component;

/**
 * Class DevUpdaterComponent
 * @package tourhunter\devUpdater
 */
class DevUpdaterComponent extends Component {

    const INFO_LAST_UPDATE_TIME = 'last-update-time';
    const INFO_LAST_UPDATE_ERRORS = 'last-update-errors';

    /**
     * @var string[]
     */
    public $allow_env = [ 'dev' ];

    /**
     * @var string
     */
    public $controllerId = 'dev-updater';

    /**
     * @var string[]
     */
    public $updaterServices = [
        'tourhunter\devUpdater\services\MigrationUpdaterService',
        'tourhunter\devUpdater\services\ComposerUpdaterService',
    ];

    /**
     * @var string
     */
    public $lastUpdateInfoFilename = '@runtime/devUpdaterInfo.json';

    /**
     * @var string
     */
    public $updatingLockFilename = '@runtime/devUpdater.lock';

    /**
     * @var bool|string
     */
    public $sudoUser = false;

    /**
     * @var string
     */
    public $composerCommand = 'composer';

    /**
     * @var string[]
     */
    protected $_warnings = [];

    /**
     * @var UpdaterService[]
     */
    protected $_updaterServicesObjects = [];

    /**
     * @var null|array
     */
    protected $_lastUpdateInfo = null;

    /**
     * @throws \yii\console\Exception
     * @throws \yii\web\NotFoundHttpException
     */
    public function init()
    {
        if (in_array(YII_ENV, $this->allow_env)) {
            $this->_loadLastUpdateInfo();
            \Yii::$app->controllerMap[$this->controllerId] = DevUpdaterController::className();

            foreach ($this->updaterServices as $updaterServiceClass) {
                $this->_updaterServicesObjects[] = new $updaterServiceClass($this);
            }
            $this->checkAllUpdateNecessity();

            list($route, $params) = \Yii::$app->getRequest()->resolve();
            if ($this->getUpdateNecessity() && 0 !== strpos($route, $this->controllerId)) {
                \Yii::$app->getResponse()->redirect(\Yii::$app->urlManager->createUrl([$this->controllerId.'/index']));
                return;
            }
        }
    }

    /**
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function runAllUpdates() {
        $ret = true;
        $this->acquireLock();
        set_time_limit(0);
        try {
            if ($this->getUpdateNecessity()) {
                $this->skipLastErrors();
                $this->saveLastUpdateInfo();
                foreach ($this->_updaterServicesObjects as $updaterObject) {
                    $ret = $updaterObject->runUpdate();
                    if (!$ret) break;
                }
                if ($ret) {
                    $this->setLastUpdateInfo(self::INFO_LAST_UPDATE_TIME, time());
                }
                $this->saveLastUpdateInfo();
            }
        } catch (\Exception $e) {
            $ret = false;
            $this->addErrorInfo($e->getMessage());
            $this->saveLastUpdateInfo();
        }
        $this->releaseLock();
        return $ret;
    }

    /**
     * Check all warnings in services
     */
    public function checkAllWarnings() {
        if(ini_get('safe_mode')){
            $this->addWarning('Enabled safe_mode in php configuration blocks the ability to change runtime '
                . 'restrictions. This can cause problems with updating process.');
        }
        foreach($this->_updaterServicesObjects as $updaterObject) {
            $updaterObject->checkWarnings();
        }
    }

    /**
     * Check all update necessity in services
     */
    public function checkAllUpdateNecessity() {
        foreach($this->_updaterServicesObjects as $updaterObject) {
            $updaterObject->checkUpdateNecessity();
        }
    }

    /**
     * @return bool
     */
    public function getUpdateNecessity() {
        $status = false;
        foreach ($this->_updaterServicesObjects as $updaterObject) {
            if ($updaterObject->getServiceUpdateNecessity()) {
                $status = true;
                break;
            }
        }
        return $status;
    }

    /**
     * @return string[]
     */
    public function getNonUpdatedServiceTitles() {
        $titles = [];
        foreach($this->_updaterServicesObjects as $servicesObject) {
            if ($servicesObject->getServiceUpdateNecessity()) {
                $titles[] = $servicesObject->title;
            }
        }
        return $titles;
    }

    /**
     * @param $warning
     */
    public function addWarning($warning) {
        $this->_warnings[] = $warning;
    }

    /**
     * @return string[]
     */
    public function getWarnings() {
        return $this->_warnings;
    }

    /**
     * @return bool
     */
    public function hasWarnings() {
        return (0 < count($this->_warnings));
    }

    /**
     * returns registered in application component id
     *
     * @return bool|int|string
     */
    public static function getRegisteredComponentId() {
        $id = false;
        $components = \Yii::$app->getComponents(true);
        foreach ($components as $componentId => $component) {
            $componentClassName = is_object($component) ? get_class($component) : $component['class'];
            if ($componentClassName === self::className()) {
                $id = $componentId;
                break;
            }
        }
        return $id;
    }

    /**
     * get update time info
     *
     * @param $key
     * @param null $default
     * @return null
     */
    public function getLastUpdateInfo($key, $default = null) {
        return isset($this->_lastUpdateInfo[$key]) ? $this->_lastUpdateInfo[$key] : $default;
    }

    /**
     * set update time info
     *
     * @param $key
     * @param $value
     */
    public function setLastUpdateInfo($key, $value) {
        $this->_lastUpdateInfo[$key] = $value;
    }

    /**
     * save update times data to lock file
     */
    public function saveLastUpdateInfo() {
        file_put_contents(\Yii::getAlias($this->lastUpdateInfoFilename), json_encode($this->_lastUpdateInfo));
    }

    /**
     * @param $error
     */
    public function addErrorInfo($error) {
        if (!isset($this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS])) {
            $this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS] = [];
        }
        $this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS][] = $error;
    }

    /**
     * @return string[]
     */
    public function getLastErrorsInfo() {
        if (!isset($this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS])) {
            $this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS] = [];
        }
        return $this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS];
    }

    /**
     *
     */
    public function skipLastErrors() {
        if (isset($this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS])) {
            unset($this->_lastUpdateInfo[self::INFO_LAST_UPDATE_ERRORS]);
        }
    }

    /**
     * load update times data from file
     */
    protected function _loadLastUpdateInfo() {
        $lastUpdateInfoFilename = \Yii::getAlias($this->lastUpdateInfoFilename);
        if (file_exists($lastUpdateInfoFilename)) {
            $lastUpdateInfoFileContent = file_get_contents(\Yii::getAlias($lastUpdateInfoFilename));
            $this->_lastUpdateInfo = json_decode($lastUpdateInfoFileContent, true);
        }
        if (is_null($this->_lastUpdateInfo)) {
            $this->_lastUpdateInfo = [];
        }
    }

    /**
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function isRunningUpdate() {
        $status = true;
        if ($this->acquireLock()) {
            $status = false;
            $this->releaseLock();
        }
        return $status;
    }

    /**
     * @param $command
     * @param null $output
     * @return null
     */
    public function runShellCommand($command, &$output = null) {
        $retCode = null;
        $currentPath = getcwd();
        chdir(\Yii::getAlias('@app'));
        if (!empty($this->sudoUser)) {
            $command = 'sudo -u '. $this->sudoUser . ' ' . $command;
        }
        exec($command . ' 2>&1', $output, $retCode);
        chdir($currentPath);
        return $retCode;
    }

    /**
     * Acquires current updating process lock.
     * @return boolean lock acquiring result.
     * @throws \yii\base\InvalidConfigException
     */
    protected function acquireLock()
    {
        $filename = \Yii::getAlias($this->updatingLockFilename);
        $status = false;
        if (!file_exists($filename)) {
            $status = touch($filename);
        }
        return $status;
    }

    /**
     * Release current updating process lock.
     * @return boolean lock release result.
     */
    protected function releaseLock()
    {
        $filename = \Yii::getAlias($this->updatingLockFilename);
        $status = false;
        if (file_exists($filename)) {
            $status = unlink($filename);
        }
        return $status;
    }
}

