<?php

$transform = "simpletest.org.xslt";
$source_paths = array("../../docs/wiki/", "en" => "../../docs/source/en/", "fr" => "../../docs/source/fr/");
$destination_path = "../../../../simpletest.org/wiki/data/";

foreach ($source_paths as $lang => $source_path) {
	if (!is_int($lang)) {
		$prefix = $lang.'-';		
	}
	$dir = opendir($source_path);
	while (($file = readdir($dir)) !== false) {
		if (! preg_match('/\.xml$/', $file)) {
			continue;
		}
		$source = $source_path.$file;
		$destination = $destination_path.$prefix.preg_replace('/\.xml$/', '.txt', basename($source));

		$xsltProcessor = xslt_create();
		$fileBase = 'file://'.getcwd().'/';
		xslt_set_base($xsltProcessor, $fileBase);
		$result = xslt_process ($xsltProcessor, $source, $transform);
		$result = preg_replace("/((<a href=\")([a-z_]*)(\.php\">))/", "<a href=\"doku.php?id=fr-\\3\">", $result);

		if ( $result ) {
			$handle = fopen($destination, "w+");
			fwrite($handle, $result);
			fclose($handle);
			echo "succès pour ".$destination."<br />";
		} else {
		   echo "erreur pour ".$destination." : ".xslt_error($xh)."<br />";
		}

		xslt_free($xsltProcessor);
	}
	closedir($dir);
}
?>