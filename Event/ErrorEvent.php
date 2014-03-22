<?php
/**
 * Class ErrorEvent
 * @package Hezarfen\DenetmenBundle\Event
 */

namespace Hezarfen\DenetmenBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ErrorEvent extends Event
{
    private $errorRows = array();
    private $commandOptions = array();

    public function __construct($options)
    {
        $this->commandOptions = $options;
    }

    /**
     * @param array $errorRows
     */
    public function setErrorRows(array $errorRows)
    {
        $this->errorRows = $errorRows;
    }

    /**
     * @return array
     */
    public function getErrorRows()
    {
        return $this->errorRows;
    }

    /**
     * @param array $commandOptions
     */
    public function setCommandOptions(array $commandOptions)
    {
        $this->commandOptions = $commandOptions;
    }

    /**
     * @return array
     */
    public function getCommandOptions()
    {
        return $this->commandOptions;
    }
}
