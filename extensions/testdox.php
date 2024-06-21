<?php

require_once __DIR__ . '/../src/reporter.php';

/**
 *	Extension for a TestDox reporter
 */
class TestDoxReporter extends SimpleReporter
{
    public $_test_case_pattern = '/^TestOf(.*)$/';

    public function __construct($test_case_pattern = '/^TestOf(.*)$/')
    {
        parent::__construct();
        $this->_test_case_pattern = empty($test_case_pattern) ? '/^(.*)$/' : $test_case_pattern;
    }

    public function paintCaseStart($test_name): void
    {
        preg_match($this->_test_case_pattern, $test_name, $matches);
        if (!empty($matches[1])) {
            echo $matches[1] . "\n";
        } else {
            echo $test_name . "\n";
        }
    }

    public function paintCaseEnd($test_name): void
    {
        echo "\n";
    }

    public function paintMethodStart($test_name): void
    {
        if (!preg_match('/^test(.*)$/i', $test_name, $matches)) {
            return;
        }
        $test_name = $matches[1];
        $test_name = preg_replace('/([A-Z])([A-Z])/', '$1 $2', $test_name);
        echo '- ' . strtolower(preg_replace('/([a-zA-Z])([A-Z0-9])/', '$1 $2', $test_name));
    }

    public function paintMethodEnd($test_name): void
    {
        echo "\n";
    }

    public function paintFail($message): void
    {
        echo ' [FAILED]';
    }
}
