<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'observer.php');
    require_once(SIMPLE_TEST . 'simple_html_test.php');

    // To be inspected visually.
    //
    $reporter = new TestHTMLDisplay();
    $reporter->notify(new TestStart("One"));
    $reporter->notify(new TestStart("Two"));
    $reporter->notify(new TestResult(true, "True"));
    $reporter->notify(new TestResult(false, "A big long failure message"));
    $reporter->notify(new TestEnd("Two"));
    $reporter->notify(new TestEnd("One"));
?>