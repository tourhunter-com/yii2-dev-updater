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

        $lastMigrationTime = 0;

        $migrationsPath = \Yii::getAlias('@app/migrations/.');
        if (file_exists($migrationsPath)) {
            $lastMigrationModTime = filemtime($migrationsPath);

            try {
                $lastMigrationTime = (int)\Yii::$app->db
                    ->createCommand('SELECT apply_time FROM migration ORDER BY apply_time desc LIMIT 1')->queryScalar();
            } catch (\Exception $e) {

            }

            $this->_serviceUpdateIsNeeded = (
                $lastMigrationModTime >= $lastMigrationTime
                && $lastMigrationModTime >= $this->_updateComponent->getLastUpdateInfo(DevUpdaterComponent::INFO_LAST_UPDATE_TIME, 0)
            );
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
        } else {
            $this->_updateComponent->addErrorInfo('Migrations update has been failed! Please check migration update manually.');
        }
        return $status;
    }
}