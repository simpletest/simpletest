<?php
    // $Id$
    
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', './');
    }
    
    /**
     *    Singleton error queue used to record trapped
     *    errors.
     */
    class SimpleErrorQueue {
        var $_queue;
        
        /**
         *    Starts with an empty queue.
         *    @public
         */
        function SimpleErrorQueue() {
            $this->clear();
        }
        
        /**
         *    Adds an error to the front of the queue.
         *    @param $severity        PHP error code.
         *    @param $message         Text of error.
         *    @param $filename        File error occoured in.
         *    @param $line            Line number of error.
         *    @param $super_globals   Hash of PHP super global arrays.
         *    @public
         */
        function add($severity, $message, $filename, $line, $super_globals) {
            array_push(
                    $this->_queue,
                    array($severity, $message, $filename, $line, $super_globals));
        }
        
        /**
         *    Pulls the earliest error from the queue.
         *    @return     False if none, or a list of error
         *                information. Elements are: severity
         *                as the PHP error code, the error message,
         *                the file with the error, the line number
         *                and a list of PHP super global arrays.
         *    @public
         */
        function extract() {
            if (count($this->_queue)) {
                return array_shift($this->_queue);
            }
            return false;
        }
        
        /**
         *    Discards the contents of the error queue.
         *    @public
         */
        function clear() {
            $this->_queue = array();
        }
        
        /**
         *    Global access to a single error queue.
         *    @return        Global error queue object.
         *    @public
         *    @static
         */
        function &instance() {
            static $queue = false;
            if (!$queue) {
                $queue = new SimpleErrorQueue();
            }
            return $queue;
        }
    }
    
    /**
     *    Error handler that simply stashes any
     *    errors into the global error queue.
     *    @param $severity        PHP error code.
     *    @param $message         Text of error.
     *    @param $filename        File error occoured in.
     *    @param $line            Line number of error.
     *    @param $super_globals   Hash of PHP super global arrays.
     *    @static
     */
    function simpleTestErrorHandler($severity, $message, $filename, $line, $super_globals) {
        $queue = &SimpleErrorQueue::instance();
        $queue->add($severity, $message, $filename, $line, $super_globals);
    }
?>