<?php

namespace tourhunter\devUpdater\services;

use tourhunter\devUpdater\UpdaterService;
use tourhunter\devUpdater\DevUpdaterComponent;

/**
 * Class ComposerUpdaterService
 * @package tourhunter\devUpdater\services
 */
class ComposerUpdaterService extends UpdaterService {

    /**
     * @var string
     */
    public $title = 'composer';

    /**
     * @inheritdoc
     */
    public function checkUpdateNecessity() {
        $currentRef = $this->_updateComponent->getGitHelper()->getHead();
        $infoKey = DevUpdaterComponent::INFO_LAST_UPDATE_TIME . ':' . $this->title . ':' . $currentRef;

        $lastCheckedTime = $this->_updateComponent->getLastUpdateInfo($infoKey, 0);
        $lastCommitTime = $this->_updateComponent->getGitHelper()->getLastCommitTime();

        if ($lastCommitTime > $lastCheckedTime) {
            $retCode = $this->_updateComponent->runShellCommand('composer validate');
            $this->_serviceUpdateIsNeeded = (2 === $retCode);
            if (1 === $retCode) {
                $this->_updateComponent->addWarning('The composer.json file seems to have errors.');
            }
            if (!$this->_serviceUpdateIsNeeded) {
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
        $retCode = $this->_updateComponent->runShellCommand($this->getComposerCommand() . ' install');
        if (0 === $retCode) {
            $status = true;
            $infoKey = DevUpdaterComponent::INFO_LAST_UPDATE_TIME . ':' . $this->title . ':'
                . $this->_updateComponent->getGitHelper()->getHead();
            $this->_updateComponent->setLastUpdateInfo($infoKey, time());
            $this->_updateComponent->saveLastUpdateInfo();
        } else {
            $this->_updateComponent->addErrorInfo('Composer update has been failed! Please check the composer update manually.');
            $this->_updateComponent->saveLastUpdateInfo();
        }
        return $status;
    }

    /**
     * @return string
     */
    protected function getComposerCommand() {
        return $this->_updateComponent->composerCommand;
    }
}