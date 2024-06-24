<?php
$cookie_value = 'not set'; // Default value if cookie is not set

if (isset($_COOKIE['mydomain_cookie'])) {
    $cookie_value = $_COOKIE['mydomain_cookie'];
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Cookie From Subdomain</title>
    </head>
    <body>
        <p>Cookie Value: <?php print \htmlspecialchars($cookie_value, ENT_QUOTES, 'UTF-8'); ?></p>
    </body>
</html>