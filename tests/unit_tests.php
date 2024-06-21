<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/unit_tester.php';

require_once __DIR__ . '/../src/shell_tester.php';

require_once __DIR__ . '/../src/mock_objects.php';

require_once __DIR__ . '/../src/web_tester.php';

require_once __DIR__ . '/../extensions/phpunit/tests/adapter_test.php';

class UnitTests extends TestSuite
{
    public function __construct()
    {
        parent::__construct('Unit tests');
        $path = __DIR__;
        $this->addFile($path . '/errors_test.php');
        $this->addFile($path . '/exceptions_test.php');
        $this->addFile($path . '/arguments_test.php');
        $this->addFile($path . '/autorun_test.php');
        $this->addFile($path . '/compatibility_test.php');
        $this->addFile($path . '/simpletest_test.php');
        $this->addFile($path . '/dumper_test.php');
        $this->addFile($path . '/expectation_test.php');
        $this->addFile($path . '/unit_tester_test.php');
        $this->addFile($path . '/reflection_test.php');
        $this->addFile($path . '/mock_objects_test.php');
        $this->addFile($path . '/interfaces_test.php');
        $this->addFile($path . '/collector_test.php');
        $this->addFile($path . '/recorder_test.php');
        $this->addFile($path . '/socket_test.php');
        $this->addFile($path . '/encoding_test.php');
        $this->addFile($path . '/url_test.php');
        $this->addFile($path . '/cookies_test.php');
        $this->addFile($path . '/http_test.php');
        $this->addFile($path . '/authentication_test.php');
        $this->addFile($path . '/user_agent_test.php');
        $this->addFile($path . '/php_parser_test.php');
        $this->addFile($path . '/parsing_test.php');
        $this->addFile($path . '/tag_test.php');
        $this->addFile($path . '/form_test.php');
        $this->addFile($path . '/page_test.php');
        $this->addFile($path . '/frames_test.php');
        $this->addFile($path . '/browser_test.php');
        $this->addFile($path . '/web_tester_test.php');
        $this->addFile($path . '/shell_tester_test.php');
        $this->addFile($path . '/xml_test.php');

        $this->addFile($path . '/../extensions/phpunit/tests/adapter_test.php');
        $this->addFile($path . '/../extensions/testdox/test.php');
    }
}
