<?php

namespace tourhunter\devUpdater;

/**
 * Class UpdaterService
 * @package tourhunter\devUpdater
 */
class UpdaterService {

    /**
     * @var string
     */
    public $title;

    /**
     * @var DevUpdaterComponent
     */
    protected $_updateComponent;

    /**
     * @var bool
     */
    protected $_serviceUpdateIsNeeded = false;

    /**
     * @return bool
     */
    public function getServiceUpdateNecessity() {
        return $this->_serviceUpdateIsNeeded;
    }

    /**
     * UpdaterService constructor.
     * @param DevUpdaterComponent $component
     */
    public function __construct(DevUpdaterComponent $component)
    {
        $this->_updateComponent = $component;
    }

    /**
     * Service update process logic
     *
     * @return bool
     */
    public function runUpdate() {
        return true;
    }

    /**
     * Check environment warnings
     * And add that via $this->_updaterComponent->addWarning(...)
     */
    public function checkWarnings() {}

    /**
     * Check service update necessity
     */
    public function checkUpdateNecessity() {}
}