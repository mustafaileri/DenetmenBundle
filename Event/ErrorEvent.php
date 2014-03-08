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

    public function __construct()
    {

    }

    /**
     * @param array $errorRows
     */
    public function setErrorRows($errorRows)
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


} 