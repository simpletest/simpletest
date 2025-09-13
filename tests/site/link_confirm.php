<?php
require_once __DIR__ . '/self.php';
?>
<html>
    <head><title>SimpleTest testing links</title></head>
    <body>
        <p>
            A target for the
            <a href="http://localhost:8080/simple_test.php">SimpleTest</a>
            test suite.
        </p>
        <ul>
            <li><a href="<?php print my_path(); ?>request_mirror.php">Absolute</a></li>
            <li><a href="request_mirror.php">Relative</a></li>
            <li><a href="request_mirror.php" id="1">Id</a></li>
            <li><a href="request_mirror.php">m&auml;rc&ecirc;l kiek&#039;eboe</a></li>
        </ul>
    </body>
</html>
