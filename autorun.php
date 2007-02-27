<?php
    /**
     *	Autorunner which runs all tests cases found in a file
     *	that includes this module.
     *	@package	SimpleTest
     *	@version	$Id$
     */
    require_once dirname(__FILE__) . '/unit_tester.php';
    require_once dirname(__FILE__) . '/reporter.php';

    $SIMPLE_TEST_AUTORUNNER_INITIAL_CLASSES = get_declared_classes();

    register_shutdown_function('SimpleTestAutoRunner');

    function SimpleTestAutoRunner() {
        global $SIMPLE_TEST_AUTORUNNER_INITIAL_CLASSES;

        $file = reset(get_included_files());
        $classes = array_diff(get_declared_classes(),
                              $SIMPLE_TEST_AUTORUNNER_INITIAL_CLASSES ?
                              $SIMPLE_TEST_AUTORUNNER_INITIAL_CLASSES : array());

        $group = new GroupTest();

        foreach ($classes as $class) {
            if (version_compare(phpversion(), '5') >= 0) {
                $refl = new ReflectionClass($class);
                if ($refl->getFileName() == $file) {
                    $candidate = new $class;
                    if (SimpleTest :: isTestCase($candidate)) {
                        $group->addTestCase($candidate);
                    }
                }
            } else {
                $candidate = new $class;
                if (SimpleTest :: isTestCase($candidate)) {
                    $group->addTestCase($candidate);
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