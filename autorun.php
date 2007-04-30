<?php
    /**
     *	Autorunner which runs all tests cases found in a file
     *	that includes this module.
     *	@package	SimpleTest
     *	@version	$Id$
     */
    require_once dirname(__FILE__) . '/unit_tester.php';
    require_once dirname(__FILE__) . '/mock_objects.php';
    require_once dirname(__FILE__) . '/collector.php';
    require_once dirname(__FILE__) . '/default_reporter.php';

    $GLOBALS['SIMPLETEST_AUTORUNNER_INITIAL_CLASSES'] = get_declared_classes();
    register_shutdown_function('simpletest_autorun');

    function simpletest_autorun() {
        if (tests_have_run()) {
			return;
        }
        $new_classes = capture_new_classes();
        $suite = new TestSuite(basename(initial_file()));
		foreach (classes_defined_in_initial_file() as $candidate) {
			if (SimpleTest::isTestCase($candidate)) {
				if (in_array(strtolower($candidate), $new_classes)) {
					$suite->addTestCase(new $candidate);
				}
			}
		}
        $result = $suite->run(SimpleTest::preferred(
				array('SimpleReporter', 'SimpleReporterDecorator')));
        if (SimpleReporter::inCli()) {
            exit($result ? 0 : 1);
        }
    }

	function tests_have_run() {
        if ($context = SimpleTest::getContext()) {
			if ($context->getTest()) {
				return true;
			}
		}
		return false;
	}
	
	function initial_file() {
		static $file = false;
		if (! $file) {
			$file = reset(get_included_files());
		}
		return $file;
	}
	
	function classes_defined_in_initial_file() {
        if (! preg_match_all('~class\s+(\w+)~', file_get_contents(initial_file()), $matches)) {
			return array();
		}
		return $matches[1];
	}
	
	function capture_new_classes() {
        global $SIMPLETEST_AUTORUNNER_INITIAL_CLASSES;
        return array_map('strtolower', array_diff(get_declared_classes(),
                              $SIMPLETEST_AUTORUNNER_INITIAL_CLASSES ?
                              $SIMPLETEST_AUTORUNNER_INITIAL_CLASSES : array()));
	}
?>