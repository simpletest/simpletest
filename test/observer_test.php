<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'observer.php');
?>
<html>
    <head>
        <title>Test of observer classes used in SimpleTest</title>
    </head>
    <body>
        <h1>Simple test print of six tests.</h1>
        <ol>
            <?php
                class TestOfTestObserver extends TestObserver {
                    function TestOfTestObserver() {
                        $this->TestObserver();
                    }
                    function notify($message) {
                        print $message;
                    }
                }
                $observable = new TestObservable();
                $observer1 = new TestOfTestObserver();
                $observable->attachObserver($observer1);
                $observer2 = new TestOfTestObserver();
                $observable->attachObserver($observer2);
                print "<li>Expecting from observers [hellohello] got [";
                $message = "hello";
                $observable->notify($message);
                print "]</li>\n";
                
                $event = new TestEvent("fred");
                print "<li>Expecting event label [fred] got [" . $event->getLabel() . "]</li>\n";
                
                class TestOfTestReporter extends TestReporter {
                    function TestOfTestReporter() {
                        $this->TestReporter();
                    }
                    function paintStart($test_name) {
                        print $test_name;
                    }
                    function paintEnd($test_name) {
                        print $test_name;
                    }
                    function paintPass($message) {
                        print "pass " . $message;
                    }
                    function paintFail($message) {
                        print "fail " . $message;
                    }
                }
                $reporter = new TestOfTestReporter();
                $event = new TestStart("1");
                print "<li>Expecting event label [1] got [";
                $event->paint($reporter);
                print "]</li>\n";
                $event = new TestEnd("2");
                print "<li>Expecting event label [2] got [";
                $event->paint($reporter);
                print "]</li>\n";
                $event = new TestResult(true, "3");
                print "<li>Expecting event label [pass 3] got [";
                $event->paint($reporter);
                print "]</li>\n";
                $event = new TestResult(false, "4");
                print "<li>Expecting event label [fail 4] got [";
                $event->paint($reporter);
                print "]</li>\n";
            ?>
        </ol>
    </body>
</html>
