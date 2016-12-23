<?php
/**
 * Console import multithread engine
 *
 * PHP version 5
 *
 * @category Products
 * @package  Santaeulalia
 * @author   peter.georgiev <peter.georgiev@concatel.com>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link     none
 */

namespace Console\Multitask;
use Console\Multitask\Events\ChildProcessEvent;
use Console\Multitask\Events\EngineEvent;
use Console\Multitask\Events\ExceptionEvent;
use System\Events\Dispatcher;

/**
 * Class Engine
 *
 * Encapsulates the multi-threading
 *
 * @category Multitasking
 * @package  Multitasking
 * @author   peter.georgiev <peter.georgiev@concatel.com>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link     none
 */
class Engine extends Dispatcher
{
    const EVENT_EXECUTION_START = 'exec_start';
    const EVENT_EXECUTION_END = 'exec_end';

    const EVENT_CHILD_START = 'child_start';
    const EVENT_CHILD_END = 'child_end';

    const EVENT_CHILD_FORKED = 'child_forked';

    const EVENT_CHILD_END_EXCEPTION = 'child_end_exception';

    /** @var int maxForks - maximum number of concurent threads */
    protected $maxForks;
    /** @var array $pids - running process ids */
    protected $pids = array();

    /** @var Adapter $adapter */
    protected $adapter;

    /**
     * ImportMultiTasking constructor.
     *
     * Inicializing the Magento application for console. Setting developer mode and disabling automatic index.
     *
     * This stuff is inherited from already existing import operations that are considered working.
     * @param Adapter $adapter
     * @param int $forksNum
     */
    public function __construct(Adapter $adapter, $forksNum = 5)
    {
        $this->adapter = $adapter;
        $this->adapter->init();

        $this->maxForks = (int)$forksNum;
    }

    /**
     * Encapsulates the loop for controlling the forks number and the fork start
     *
     */
    private function startForks()
    {
        $items = &$this->adapter->items();
        foreach ($items as $k => $item) {
            if (count($this->pids) < $this->maxForks) {
                //echo "Starting child for product secuence $k id $id\n";
                $p = $this->importChild($item);
                $this->pids[$p] = $p;
                unset($items[$k]);
            } else {
                break;
            }
        }
    }

    /**
     * Starts the execution and checking each second for if a child process has finished so it can run a new one.
     *
     * @return $this
     */
    public function execute()
    {
        $this->trigger(self::EVENT_EXECUTION_START, new EngineEvent());
        $count = $this->adapter->getCount();
        if ($count) {
            $this->startForks();
            do {
                $pid = pcntl_waitpid(-1, $status, WNOHANG);
                if ($pid == -1) {
                    break;
                } else if ($pid) {
                    if (isset($this->pids[$pid])) {
                        unset($this->pids[$pid]);
                        $this->startForks();
                    }
                }
            } while ($this->adapter->getCount() && count($this->pids));
        }
        $this->trigger(self::EVENT_EXECUTION_END, new EngineEvent());
        return $this;
    }

    /**
     *
     * The actual fork method. Returns the child process id in the root process and is killed in the child so it
     * can be catched by the waitpid.
     *
     * We open new db connections for each child execution and close then in the end so we do not exceed the connections
     * limit.
     *
     * @param mixed $item
     * @return int
     * @internal param $id
     */
    protected function importChild($item)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            die("cannot fork");
        } else if ($pid) {
            $this->trigger(self::EVENT_CHILD_FORKED, new ChildProcessEvent($pid));
            return $pid;
        } else {
            try {
                $this->trigger(self::EVENT_CHILD_START);
                $this->adapter->run($item);
                $this->trigger(self::EVENT_CHILD_END);
            } catch (\Exception $ex) {
                $this->trigger(self::EVENT_CHILD_END_EXCEPTION, new ExceptionEvent($ex));
            }
            die();
        }
    }

}