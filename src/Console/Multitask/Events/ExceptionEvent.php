<?php
/** $Id: - $ */
/**
 * ---------  Begin Version Control Data----------------------------------------
 * $LastChangedDate: - $
 * $Revision: - $
 * $LastChangedBy: - $
 * $Author: - $
 * ---------  End Version Control Data -----------------------------------------
 */

namespace Console\Multitask\Events;


use System\Events\Event;

class ExceptionEvent extends Event
{
    public $exception;

    public function __construct(\Exception $ex)
    {
        $this->exception = $ex;
    }
}