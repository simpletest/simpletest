<?php
    // $Id$
    
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', './');
    }
    
    class SimpleErrorQueue {
        var $_queue;
        
        function SimpleErrorQueue() {
            $this->clear();
        }
        function add($severity, $message, $filename, $line, $super_globals) {
            array_push(
                    $this->_queue,
                    array($severity, $message, $filename, $line, $super_globals));
        }
        function extract() {
            if (count($this->_queue)) {
                return array_shift($this->_queue);
            }
            return false;
        }
        function clear() {
            $this->_queue = array();
        }
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