<?php
    // $Id$
    
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', './');
    }
    
    /**
     *    Accessor for global error queue.
     *    @return        List of errors as hashes.
     *    @static
     */
    function &getSimpleTestErrorQueue() {
        $queue = false;
        if (!$queue) {
            $queue = array();
        }
        return $queue;
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
        $queue = &getSimpleTestErrorQueue();
        $queue[] = array($severity, $message, $filename, $line, $super_globals);
    }
?>