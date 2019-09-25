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
        $lastLockTime = 0;

        $composerLockPath = \Yii::getAlias('@app/composer.lock');

        if (file_exists($composerLockPath)) {
            $lastLockTime = filemtime($composerLockPath);
        }

        $composerJsonPath = \Yii::getAlias('@app/composer.json');
        if (file_exists($composerJsonPath)) {
            $lastJsonTime = filemtime($composerJsonPath);

            if ($lastJsonTime >= $lastLockTime
                && $lastJsonTime >= $this->_updateComponent->getLastUpdateInfo(DevUpdaterComponent::INFO_LAST_UPDATE_TIME, 0)
            ) {
                $retCode = $this->_updateComponent->runShellCommand('composer validate');
                $this->_serviceUpdateIsNeeded = (2 === $retCode);
                if (1 === $retCode) {
                    $this->_updateComponent->addWarning('The composer.json file seems to have errors.');
                }
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
        } else {
            $this->_updateComponent->addErrorInfo('Composer update has been failed! Please check composer update manually.');
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