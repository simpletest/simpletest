<?php
/**
 *  base include file for SimpleTest
 *  @package    SimpleTest
 *  @subpackage UnitTester
 *  @version    $Id: dumper.php 1909 2009-07-29 15:58:11Z dgheath $
 */

/**
 *    Parses the command line arguments.
 *    @package  SimpleTest
 *    @subpackage   UnitTester
 */
class SimpleArguments {
    private $all = array();
    
    function __construct($arguments) {
        array_shift($arguments);
        while (count($arguments) > 0) {
            list($key, $value) = $this->parseArgument($arguments);
            $this->assign($key, $value);
        }
    }
    
    function assign($key, $value) {
        if ($this->$key === false) {
            $this->all[$key] = $value;
        } elseif (! is_array($this->$key)) {
            $this->all[$key] = array($this->$key, $value);
        } else {
            $this->all[$key][] = $value;
        }
    }
    
    function parseArgument(&$arguments) {
        $argument = array_shift($arguments);
        if (preg_match('/^-(\w)=(.+)$/', $argument, $matches)) {
            return array($matches[1], $matches[2]);
        } elseif (preg_match('/^-(\w)$/', $argument, $matches)) {
            return array($matches[1], $this->nextNonFlagElseTrue($arguments));
        }
    }
    
    function nextNonFlagElseTrue(&$arguments) {
        return $this->valueIsNext($arguments) ? array_shift($arguments) : true;
    }
    
    function valueIsNext($arguments) {
        return isset($arguments[0]) && ! $this->isFlag($arguments[0]);
    }
    
    function isFlag($argument) {
        return strncmp($argument, '-', 1) == 0;
    }
    
    function __get($key) {
        if (isset($this->all[$key])) {
            return $this->all[$key];
        }
        return false;
    }
    
    function all() {
        return $this->all;
    }
}
?>