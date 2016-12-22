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

class ChildProcessEvent extends Event
{
    public $pid;

    public function __construct($pid)
    {
        $this->pid = $pid;
    }
}