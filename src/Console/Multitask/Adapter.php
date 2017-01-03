<?php

namespace Console\Multitask;
/**
 * Interface Engine
 *
 * Adapter interface that every adapter should implement
 *
 * @category Multitasking
 * @package  Multitasking
 * @author   peter.georgiev <peter.georgiev@concatel.com>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link     none
 */
interface Adapter
{
    /**
     * Init method to execute initialization stuff
     * @return mixed
     */
    public function init();

    /**
     * Returns items list count
     *
     * @return integer
     */
    public function getCount();

    /**
     * Returns reference to the items list. Items list should be iterable
     * @return mixed
     */
    public function &items();

    /**
     * The bunch of commands to be executed over each item.
     *
     * This method is executed inside child processes
     *
     * @param mixed $item
     * @return mixed
     */
    public function run($item);

    /**
     * This method sets the running engine to the adapter
     *
     * @param Engine $engine
     * @return mixed
     */
    public function setEngine(Engine $engine);
}