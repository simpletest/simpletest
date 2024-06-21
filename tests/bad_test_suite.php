<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

class BadTestCases extends TestSuite
{
    public function __construct()
    {
        parent::__construct('Two bad test cases');
        $this->addFile(__DIR__ . '/support/empty_test_file.php');
    }
}
