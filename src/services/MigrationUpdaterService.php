<?php

namespace tourhunter\devUpdater\services;

use tourhunter\devUpdater\MigrationHelper;
use tourhunter\devUpdater\UpdaterService;

/**
 * Class MigrationUpdaterService
 *
 * @package tourhunter\devUpdater\services
 */
class MigrationUpdaterService extends UpdaterService
{

    /**
     * @var string
     */
    public $title = 'migrations';

    /**
     * @var null|MigrationHelper
     */
    protected $_migrationHelper = null;

    /**
     * @return null|object|MigrationHelper
     * @throws \yii\base\InvalidConfigException
     */
    public function getMigrationHelper()
    {
        if (is_null($this->_migrationHelper)) {
            $this->_migrationHelper = \Yii::createObject(MigrationHelper::className(), ['MigrationHelper', \Yii::$app]);
        }

        return $this->_migrationHelper;
    }


    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function checkUpdateNecessity()
    {
        $lastCheckedTime = $this->_updateComponent->getInfoStorage()->getLastUpdateInfo($this->getInfoKey(), 0);
        $lastCommitTime = $this->_updateComponent->getGitHelper()->getLastCommitTime();

        if ($lastCommitTime > $lastCheckedTime) {
            $newMigrations = $this->getMigrationHelper()->getNewMigrations();
            $this->_serviceUpdateIsNeeded = !empty($newMigrations);
            if (!$this->_serviceUpdateIsNeeded) {
                $this->_updateComponent->getInfoStorage()->setLastUpdateInfo($this->getInfoKey(), time());
                $this->_updateComponent->getInfoStorage()->saveLastUpdateInfo();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function runUpdate()
    {
        $status = false;
        if ($this->getMigrationHelper()->runUpdate()) {
            $status = true;
            $this->_updateComponent->getInfoStorage()->setLastUpdateInfo($this->getInfoKey(), time());
            $this->_updateComponent->getInfoStorage()->saveLastUpdateInfo();
        } else {
            $output = trim(implode(' ', $this->getMigrationHelper()->getOutput()));
            if (!empty($output)) {
                $this->_updateComponent->getInfoStorage()
                    ->addErrorInfo($output);
                $this->_updateComponent->getInfoStorage()->saveLastUpdateInfo();
            }
        }

        return $status;
    }
}