<?php

namespace tourhunter\devUpdater\services;

use tourhunter\devUpdater\DevUpdaterComponent;
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
     * @inheritdoc
     */
    public function checkUpdateNecessity()
    {
        $lastCheckedTime = $this->_updateComponent->getInfoStorage()->getLastUpdateInfo($this->getInfoKey(), 0);
        $lastCommitTime = $this->_updateComponent->getGitHelper()->getLastCommitTime();

        if ($lastCommitTime > $lastCheckedTime) {

            $output = [];
            $this->_updateComponent->runShellCommand('./yii migrate/new', $output);

            $output = implode(' ', $output);
            if (preg_match('/Found [0-9]+ new migration/', $output)) {
                $this->_serviceUpdateIsNeeded = true;
            } else {
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
        $output = '';
        $retCode = $this->_updateComponent->runShellCommand('./yii migrate/up --interactive=0', $output);
        if (0 === $retCode) {
            $status = true;

            $this->_updateComponent->getInfoStorage()->setLastUpdateInfo($this->getInfoKey(), time());
            $this->_updateComponent->getInfoStorage()->saveLastUpdateInfo();
        } else {
            $this->_updateComponent->getInfoStorage()
                ->addErrorInfo('Migrations update has been failed! Please check the migration update manually.');
            $this->_updateComponent->getInfoStorage()->saveLastUpdateInfo();
        }

        return $status;
    }
}