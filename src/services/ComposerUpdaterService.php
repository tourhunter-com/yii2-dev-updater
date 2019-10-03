<?php

namespace tourhunter\devUpdater\services;

use tourhunter\devUpdater\UpdaterService;
use tourhunter\devUpdater\DevUpdaterComponent;

/**
 * Class ComposerUpdaterService
 *
 * @package tourhunter\devUpdater\services
 */
class ComposerUpdaterService extends UpdaterService
{

    /**
     * @var string
     */
    public $title = 'composer';

    /**
     * @inheritdoc
     */
    public function checkUpdateNecessity()
    {
        $lastCheckedTime = $this->_updateComponent->getInfoStorage()->getLastUpdateInfo($this->getInfoKey(), 0);
        $lastCommitTime = $this->_updateComponent->getGitHelper()->getLastCommitTime();

        if ($lastCommitTime > $lastCheckedTime) {
            $this->_fullCheckUpdateNecessity();
        }
    }

    /**
     * Full check composer updates necessity
     */
    protected function _fullCheckUpdateNecessity()
    {
        $appPath = \Yii::getAlias('@app/');
        $vendorPath = $appPath . 'vendor';
        $lockFilename = $appPath . 'composer.lock';
        $jsonFilename = $appPath . 'composer.json';
        $installedFilename = $vendorPath . '/composer/installed.json';

        if (file_exists($jsonFilename) && !is_dir($vendorPath)) {
            $this->_serviceUpdateIsNeeded = true;
        } elseif (file_exists($lockFilename) && file_exists($installedFilename)) {
            $lockedPackages = $this->_mapPackages(json_decode(file_get_contents($lockFilename), true));
            $installedPackages = $this->_mapPackages(json_decode(file_get_contents($installedFilename), true));

            foreach ($lockedPackages as $name => $version) {
                if (!isset($installedPackages[$name]) || $installedPackages[$name] != $version) {
                    $this->_serviceUpdateIsNeeded = true;
                    break;
                }
            }
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
        $output = [];
        $retCode = $this->_updateComponent->runShellCommand($this->_getComposerCommand() . ' install', $output);
        if (0 === $retCode) {
            $status = true;
            $infoKey = DevUpdaterComponent::INFO_LAST_UPDATE_TIME . ':' . $this->title . ':'
                . $this->_updateComponent->getGitHelper()->getHead();
            $this->_updateComponent->getInfoStorage()->setLastUpdateInfo($infoKey, time());
            $this->_updateComponent->getInfoStorage()->saveLastUpdateInfo();
        } else {
            $errorMessage = 'Composer update has been failed! Please check the composer update manually.';
            if (!empty($output)) {
                $errorMessage .= '<br/> ' . implode('<br/>', $output);
            }
            $this->_updateComponent->getInfoStorage()->addErrorInfo($errorMessage);
            $this->_updateComponent->getInfoStorage()->saveLastUpdateInfo();
        }

        return $status;
    }

    /**
     * @return string
     */
    protected function _getComposerCommand()
    {
        return $this->_updateComponent->composerCommand;
    }

    /**
     * @param $packagesArray
     *
     * @return array
     */
    protected function _mapPackages($packagesArray)
    {
        $map = [];
        if (isset($packagesArray['packages'])) {
            $packagesArray = $packagesArray['packages'];
        }
        foreach ($packagesArray as $packageItem) {
            $map[$packageItem['name']] = $packageItem['version'];
        }

        return $map;
    }

    /**
     * @inheritdoc
     */
    public function checkWarnings()
    {
        $appPath = \Yii::getAlias('@app/');
        $vendorPath = $appPath . 'vendor';
        $lockFilename = $appPath . 'composer.lock';
        $jsonFilename = $appPath . 'composer.json';
        $installedFilename = $vendorPath . '/composer/installed.json';

        if (!file_exists($jsonFilename)) {
            $this->_updateComponent->addWarning('The composer.json file is missing.');
        }
        if (!file_exists($lockFilename)) {
            $this->_updateComponent->addWarning('The composer.lock file is missing. Please run \'composer update\' manually.');
        }
        if (!file_exists($installedFilename)) {
            $this->_updateComponent->addWarning('The installed.json file is missing. Please run \'composer update\' manually.');
        }

        $output = [];
        $this->_updateComponent->runShellCommand('composer validate', $output);
        $outputString = implode('<br/>', $output);

        if (false !== strpos($outputString, 'does not contain valid JSON')) {
            $this->_updateComponent->addWarning('The composer.json file have errors.');
        } elseif (false !== strpos($outputString, 'The lock file is not up to date')) {
            $this->_updateComponent->addWarning('The composer.lock file is not up to date. Please run \'composer update\' manually.');
        }
    }
}