<?php
    // $Id$

    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'remote.php');
    require_once(SIMPLE_TEST . 'reporter.php');
    
    $test = &new RemoteTestCase('http://uno/simple/test/visual_test.php?xml=1');
    if (TextReporter::inCli() || isset($_GET['xml'])) {
        exit ($test->run(new XmlReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());
?>