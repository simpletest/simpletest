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
            <li><a href="<?php print my_path(); ?>network_confirm.php">Absolute</a></li>
            <li><a href="network_confirm.php">Relative</a></li>
            <li><a href="network_confirm.php" id="1">Id</a></li>
            <li><a href="network_confirm.php">m&auml;rc&ecirc;l kiek&#039;eboe</a></li>
        </ul>
    </body>
</html>
