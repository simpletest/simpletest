<?php
    // $Id$

    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'remote.php');
    require_once(SIMPLE_TEST . 'reporter.php');
    
    // The following URL will depend on your own installation.
    $base_url = 'http://uno/simple/';
    
    $test = &new GroupTest('Remote tests');
    $test->addTestCase(new RemoteTestCase(
            $base_url . 'test/visual_test.php?xml=yes',
            $base_url . 'test/visual_test.php?xml=yes&dry=yes'));
    if (SimpleReporter::inCli()) {
        exit ($test->run(new XmlReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());
?>