<?php

require_once dirname(__FILE__) . '/../../../autorun.php';

class CoverageUnitTests extends TestSuite
{
    public function __construct()
    {
        parent::__construct('Coverage Unit Tests');

        $path  = dirname(__FILE__) . '/*_test.php';
        $files = glob($path);

        foreach ($files as $test) {
            $this->addFile($test);
        }
    }
}
