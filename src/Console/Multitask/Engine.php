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
class Engine
{
    /** @var int maxForks - maximum number of concurent threads */
    protected $maxForks = 5;
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
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->adapter->init();
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
        $start = microtime(true);
        $startTime = time();
        $count = $this->adapter->getCount();
        if ($count) {
            $this->startForks();
            do {
                //echo "Checking child pids\n";
                $pid = pcntl_waitpid(-1, $status, WNOHANG);
                if ($pid == -1) {
                    break;
                } else if ($pid) {
                    if (isset($this->pids[$pid])) {
                        //echo "Child pid with id $pid ended\n";
                        unset($this->pids[$pid]);
                        $this->startForks();
                    }
                }
            } while ($this->adapter->getCount() && count($this->pids));
        }
        printf(
            "Execution started at %s and ended at %s with duration of %s processing total of %s items\n",
            date("H:i d/m/Y", $startTime),
            date("H:i d/m/Y"),
            microtime(true) - $start,
            $count
        );
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
            echo "Child process with $pid running\n";
            return $pid;
        } else {
            $this->adapter->run($item);
            die();
        }
    }

}