<html>
    <head><title>Test of form self submission</title></head>
    <body>
        <form>
            <input type="hidden" name="secret" value="Wrong form">
        </form>
        <p>[<?php echo $_GET['visible'] ?? ''; ?>]</p>
        <p>[<?php echo $_GET['secret'] ?? ''; ?>]</p>
        <p>[<?php echo $_GET['again'] ?? ''; ?>]</p>
        <form>
            <input type="text" name="visible">
            <input type="hidden" name="secret" value="Submitted">
            <input type="submit" name="again">
        </form>
        <!-- Bad form closing tag --></form>
    </body>
</html>