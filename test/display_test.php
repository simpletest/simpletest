<?php
    // $Id$
    
    // The following tests are a bit hacky. Whilst Kent Beck tried to
    // build a unit tester with a unit tester I am not that brave.
    // Instead I have just hacked together odd test scripts until
    // I have enough of a tester to procede more formally.
    //
    // The proper tests start in all_tests.php
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'observer.php');
    require_once(SIMPLE_TEST . 'simple_html_test.php');

    // To be inspected visually.
    //
    $reporter = new TestHTMLDisplay();
    $reporter->notify(new TestStart("One", 2));
    $reporter->notify(new TestStart("Two", 1));
    $reporter->notify(new TestResult(true, "True"));
    $reporter->notify(new TestResult(false, "A big long failure message"));
    $reporter->notify(new TestEnd("Two", 1));
    $reporter->notify(new TestEnd("One", 2));
?>