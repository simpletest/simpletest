<?php
    /**
     *	Autorunner which runs all tests cases found in a file
     *	that includes this module.
     *	@package	SimpleTest
     *	@version	$Id$
     */
    require_once dirname(__FILE__) . '/unit_tester.php';
    require_once dirname(__FILE__) . '/reporter.php';

    $GLOBALS['SIMPLE_TEST_AUTORUNNER_INITIAL_CLASSES'] = get_declared_classes();

    register_shutdown_function('SimpleTestAutoRunner');

    function SimpleTestAutoRunner() {
        global $SIMPLE_TEST_AUTORUNNER_INITIAL_CLASSES;

        $file = reset(get_included_files());
        $diff_classes = array_diff(get_declared_classes(),
                              $SIMPLE_TEST_AUTORUNNER_INITIAL_CLASSES ?
                              $SIMPLE_TEST_AUTORUNNER_INITIAL_CLASSES : array());
        //this is done for PHP4 compatibility
        $diff_classes = array_map('strtolower', $diff_classes);

        $group = new GroupTest();

        if (preg_match_all('~class\s+(\w+)~', file_get_contents($file), $matches)) {
            foreach($matches[1] as $candidate) {
                if(SimpleTest :: isTestCase($candidate) &&
                   in_array(strtolower($candidate), $diff_classes)) {
                    $group->addTestCase(new $candidate);
                }
            }
        }

        if ($reporter = &SimpleTest :: preferred('SimpleReporter')) {
            $res = $group->run($reporter);
        } else {
            if (SimpleReporter::inCli()) {
                $res = $group->run(new TextReporter());
            } else {
                $res = $group->run(new HtmlReporter());
            }
        }

        if (SimpleReporter::inCli()) {
            exit($res ? 0 : 1);
        }
    }

?>
