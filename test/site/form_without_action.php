<html>
    <head><title>Test of form submission</title></head>
    <body>      
        <p>_GET : [<?php echo $_GET['test'] ?? ''; ?>]</p>
        <p>_POST : [<?php echo $_POST['test'] ?? ''; ?>]</p>
		<form method="post" name="form_project" id="form_project" action="">
			<input type="hidden" value="test" name="test" />
			<input type="submit" value="Submit Post With Empty Action" name="submit" />
		</form>
    </body>
</html>