<?php

namespace Bundle\SchedulerBundle\Exception;

class InvalidJobException extends \RuntimeException
{
    protected $extraInformation;

    public function __construct($message, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
