<?php

namespace tourhunter\devUpdater;

use yii\base\Component;
use yii\web\Application;

/**
 * Class DevUpdaterComponent
 *
 * @package tourhunter\devUpdater
 */
class DevUpdaterComponent extends Component
{

    const INFO_LAST_UPDATE_TIME = 'last-update-time';

    /**
     * @var string[]
     */
    public $allow_env = ['dev'];

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
     * @var null|InfoStorage
     */
    protected $_infoStorage = null;

    /**
     * @var null|GitHelper
     */
    protected $_gitHelper = null;

    /**
     * @throws \yii\console\Exception
     * @throws \yii\web\NotFoundHttpException
     */
    public function init()
    {
        $this->_gitHelper = new GitHelper();
        if (in_array(YII_ENV, $this->allow_env) && \Yii::$app instanceof Application
            && false === $this->_gitHelper->getErrors()) {

            \Yii::$app->controllerMap[$this->controllerId] = DevUpdaterController::className();
            $requestData = \Yii::$app->getRequest()->resolve();
            $route = $requestData[0];

            if (!\Yii::$app->request->isAjax || 0 == strpos($route, $this->controllerId)) {

                foreach ($this->updaterServices as $updaterServiceClass) {
                    $this->_updaterServicesObjects[] = new $updaterServiceClass($this);
                }
                $this->checkAllUpdateNecessity();


                if (($this->getUpdateNecessity() || $this->hasWarnings()) && 0 !== strpos($route,
                        $this->controllerId)) {
                    \Yii::$app->getResponse()
                        ->redirect(\Yii::$app->urlManager->createUrl([$this->controllerId . '/index']));
                }
            }
        }
    }

    /**
     * @return null|InfoStorage
     */
    public function getInfoStorage()
    {
        if (is_null($this->_infoStorage)) {
            $this->_infoStorage = new InfoStorage($this->lastUpdateInfoFilename);
        }

        return $this->_infoStorage;
    }

    /**
     * @return GitHelper
     */
    public function getGitHelper()
    {
        return $this->_gitHelper;
    }

    /**
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function runAllUpdates()
    {
        $ret = true;
        if ($this->acquireLock()) {
            set_time_limit(0);
            try {
                if ($this->getUpdateNecessity()) {
                    $this->getInfoStorage()->skipLastErrors();
                    $this->getInfoStorage()->saveLastUpdateInfo();
                    foreach ($this->_updaterServicesObjects as $updaterObject) {
                        $ret = $updaterObject->runUpdate();
                        if (!$ret) {
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                $ret = false;
                $this->getInfoStorage()->addErrorInfo($e->getMessage());
                $this->getInfoStorage()->saveLastUpdateInfo();
            }
            $this->releaseLock();
        } else {
            $ret = false;
        }

        return $ret;
    }

    public function discardAllUpdates()
    {
        foreach ($this->_updaterServicesObjects as $updaterObject) {
            $this->getInfoStorage()->setLastUpdateInfo($updaterObject->getInfoKey(), time());
        }
        $this->getInfoStorage()->saveLastUpdateInfo();
    }

    /**
     * Check all warnings in services
     */
    public function checkAllWarnings()
    {
        if (ini_get('safe_mode')) {
            $this->addWarning('The enabled \'safe_mode\' in php configuration blocks the ability to change  '
                . 'the runtime duration. This might cause problems with the update process.');
        }
        foreach ($this->_updaterServicesObjects as $updaterObject) {
            $updaterObject->checkWarnings();
        }
    }

    /**
     * Check all update necessity in services
     */
    public function checkAllUpdateNecessity()
    {
        if ($this->acquireLock()) {
            foreach ($this->_updaterServicesObjects as $updaterObject) {
                $updaterObject->checkUpdateNecessity();
            }
            $this->releaseLock();
        }
    }

    /**
     * @return bool
     */
    public function getUpdateNecessity()
    {
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
    public function getNonUpdatedServiceTitles()
    {
        $titles = [];
        foreach ($this->_updaterServicesObjects as $servicesObject) {
            if ($servicesObject->getServiceUpdateNecessity()) {
                $titles[] = $servicesObject->title;
            }
        }

        return $titles;
    }

    /**
     * @param $warning
     */
    public function addWarning($warning)
    {
        $this->_warnings[] = $warning;
    }

    /**
     * @return string[]
     */
    public function getWarnings()
    {
        return $this->_warnings;
    }

    /**
     * @return bool
     */
    public function hasWarnings()
    {
        return (0 < count($this->_warnings));
    }

    /**
     * returns registered in application component id
     *
     * @return bool|int|string
     */
    public static function getRegisteredComponentId()
    {
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
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    public function isRunningUpdate()
    {
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
     *
     * @return null
     */
    public function runShellCommand($command, &$output = null)
    {
        $retCode = null;
        $currentPath = getcwd();
        chdir(\Yii::getAlias('@app'));
        if (!empty($this->sudoUser)) {
            $command = 'sudo -u ' . $this->sudoUser . ' ' . $command;
        }
        exec($command . ' 2>&1', $output, $retCode);
        chdir($currentPath);

        return $retCode;
    }

    /**
     * Acquires current updating process lock.
     *
     * @return boolean lock acquiring result.
     * @throws \yii\base\InvalidConfigException
     */
    protected function acquireLock()
    {
        $filename = \Yii::getAlias($this->updatingLockFilename);
        $status = false;
        if (!file_exists($filename)) {
            $status = (false !== file_put_contents($filename, getmypid()));
        }

        return $status;
    }

    /**
     * Release current updating process lock.
     *
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

