<?php

namespace tourhunter\devUpdater\services;

use tourhunter\devUpdater\DevUpdaterComponent;
use tourhunter\devUpdater\UpdaterService;

/**
 * Class MigrationUpdaterService
 * @package tourhunter\devUpdater\services
 */
class MigrationUpdaterService extends UpdaterService {

    /**
     * @var string
     */
    public $title = 'migrations';

    /**
     * @inheritdoc
     */
    public function checkUpdateNecessity() {
        $currentRef = $this->_updateComponent->getGitHelper()->getHead();
        $infoKey = DevUpdaterComponent::INFO_LAST_UPDATE_TIME . ':' . $this->title . ':' . $currentRef;

        $lastCheckedTime = $this->_updateComponent->getLastUpdateInfo($infoKey, 0);
        $lastCommitTime = $this->_updateComponent->getGitHelper()->getLastCommitTime();

        if ($lastCommitTime > $lastCheckedTime) {

            $output = [];
            $this->_updateComponent->runShellCommand('./yii migrate/new', $output);

            $output = implode(' ', $output);
            if(preg_match('/Found [0-9]+ new migration/', $output)) {
                $this->_serviceUpdateIsNeeded = true;
            } else {
                $this->_updateComponent->setLastUpdateInfo($infoKey, time());
                $this->_updateComponent->saveLastUpdateInfo();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function runUpdate() {
        $status = false;
        $output = '';
        $retCode = $this->_updateComponent->runShellCommand('./yii migrate/up --interactive=0', $output);
        if (0 === $retCode) {
            $status = true;
            $infoKey = DevUpdaterComponent::INFO_LAST_UPDATE_TIME . ':' . $this->title . ':'
                . $this->_updateComponent->getGitHelper()->getHead();
            $this->_updateComponent->setLastUpdateInfo($infoKey, time());
            $this->_updateComponent->saveLastUpdateInfo();
        } else {
            $this->_updateComponent->addErrorInfo('Migrations update has been failed! Please check the migration update manually.');
            $this->_updateComponent->saveLastUpdateInfo();
        }
        return $status;
    }
}