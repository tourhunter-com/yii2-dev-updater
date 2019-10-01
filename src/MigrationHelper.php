<?php

namespace tourhunter\devUpdater;

use yii\base\Action;
use yii\console\controllers\MigrateController;
use yii\di\Instance;
use yii\db\Connection;

/**
 * Class MigrationHelper
 *
 * @package tourhunter\devUpdater
 */
class MigrationHelper extends MigrateController
{
    /**
     * @var null|array
     */
    protected $_newMigrations = null;

    /**
     * @var array
     */
    protected $_output = [];

    /**
     * init state
     */
    public function init()
    {
        $this->beforeAction(new Action('init', $this));
    }

    /**
     * @param string $string
     */
    public function stdout($string)
    {
        $this->_output[] = $string;
    }

    /**
     * @return array
     */
    public function getOutput()
    {
        return $this->_output;
    }

    /**
     * @return array|null
     */
    public function getNewMigrations()
    {
        if (is_null($this->_newMigrations)) {
            $this->_newMigrations = parent::getNewMigrations();
        }

        return $this->_newMigrations;
    }

    /**
     * @return bool
     */
    public function runUpdate()
    {
        $status = true;
        $migrations = $this->getNewMigrations();
        foreach ($migrations as $migration) {
            if (!$this->migrateUp($migration)) {
                $status = false;
                break;
            }
        }

        return $status;
    }


}