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
    
    /**
     * Parses the command line arguments. The usual formats
     * are supported:
     * -f value
     * -f=value
     * --flag=value
     * --flag value
     * -f           (true)
     * --flag       (true)
     * @param array $arguments      Normally the PHP $argv.
     */
    function __construct($arguments) {
        array_shift($arguments);
        while (count($arguments) > 0) {
            list($key, $value) = $this->parseArgument($arguments);
            $this->assign($key, $value);
        }
    }
    
    /**
     * Sets the value in the argments object. If multiple
     * values are added under the same key, the key will
     * give an array value in the order they were added.
     * @param string $key    The variable to assign to.
     * @param string value   The value that would norally
     *                       be colected on the command line.
     */
    function assign($key, $value) {
        if ($this->$key === false) {
            $this->all[$key] = $value;
        } elseif (! is_array($this->$key)) {
            $this->all[$key] = array($this->$key, $value);
        } else {
            $this->all[$key][] = $value;
        }
    }
    
    /**
     * Extracts the next key and value from the argument list.
     * @param array $arguments      The remaining arguments to be parsed.
     *                              The argument list will be reduced.
     * @return array                Two item array of key and value.
     *                              If no value can be found it will
     *                              have the value true assigned instead.
     */
    private function parseArgument(&$arguments) {
        $argument = array_shift($arguments);
        if (preg_match('/^-(\w)=(.+)$/', $argument, $matches)) {
            return array($matches[1], $matches[2]);
        } elseif (preg_match('/^-(\w)$/', $argument, $matches)) {
            return array($matches[1], $this->nextNonFlagElseTrue($arguments));
        } elseif (preg_match('/^--(\w+)=(.+)$/', $argument, $matches)) {
            return array($matches[1], $matches[2]);
        } elseif (preg_match('/^--(\w+)$/', $argument, $matches)) {
            return array($matches[1], $this->nextNonFlagElseTrue($arguments));
        }
    }
    
    /**
     * Attempts to use the next argument as a value. It
     * won't use what it thinks is a flag.
     * @param array $arguments    Remaining arguments to be parsed.
     *                            This variable is modified if there
     *                            is a value to be extracted.
     * @return string/boolean     The next value unless it's a flag.
     */
    private function nextNonFlagElseTrue(&$arguments) {
        return $this->valueIsNext($arguments) ? array_shift($arguments) : true;
    }
    
    /**
     * Test to see if the next available argument is a valid value.
     * If it starts with "-" or "--" it's a flag and doesn't count.
     * @param array $arguments    Remaining arguments to be parsed.
     *                            Not affected by this call.
     * boolean                    True if valid value.
     */
    function valueIsNext($arguments) {
        return isset($arguments[0]) && ! $this->isFlag($arguments[0]);
    }
    
    /**
     * It's a flag if it starts with "-" or "--".
     * @param string $argument       Value to be tested.
     * @return boolean               True if it's a flag.
     */
    function isFlag($argument) {
        return strncmp($argument, '-', 1) == 0;
    }
    
    /**
     * The arguments are available as individual member
     * variables on the object.
     * @param string $key              Argument name.
     * @return string/array/boolean    Either false for no value,
     *                                 the value as a string or
     *                                 a list of multiple values if
     *                                 the flag had been specified more
     *                                 than once.
     */
    function __get($key) {
        if (isset($this->all[$key])) {
            return $this->all[$key];
        }
        return false;
    }
    
    /**
     * The entire argument set as a hash.
     * @return hash         Each argument and it's value(s).
     */
    function all() {
        return $this->all;
    }
}

class SimpleHelp {
    private $banner;
    
    function __construct($banner) {
        $this->banner = $banner;
    }
    
    function render() {
        return $this->banner . "\n";
    }
}
?>