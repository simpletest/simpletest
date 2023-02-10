<?php

require_once __DIR__ . '/package.php';

$source_path = realpath(__DIR__ . '/../docs/source') .'/';

$destination_path = realpath(__DIR__ . '/../../out/simpletest.org') .'/';

#var_dump($source_path, $destination_path);

$dir = opendir($source_path);

while (($file = readdir($dir)) !== false) {

    if (is_file($source_path . $file) and preg_match("/\.xml$/", $file)) {
        $source = simplexml_load_file($source_path . $file, "SimpleTestXMLElement");
        $destination = $source->destination(__DIR__ . '/map.xml');

        if (!empty($destination)) {
            $page = file_get_contents(__DIR__ . '/template.html');

            $page = str_replace('KEYWORDS', $source->keywords(), $page);
            $page = str_replace('TITLE', $source->title(), $page);
            $page = str_replace('CONTENT', $source->content(), $page);
            $page = str_replace('INTERNAL', $source->internal(), $page);
            $page = str_replace('EXTERNAL', $source->external(), $page);

            $links = $source->links(__DIR__ . '/map.xml');
            foreach ($links as $category => $link) {
                $page = str_replace("LINKS_" . strtoupper($category), $link, $page);
            }

            $destination_dir = dirname($destination_path . $destination);
            if (!is_dir($destination_dir)) {
                mkdir($destination_dir, 0777, true);
            }

            $ok = file_put_contents($destination_path . $destination, $page);
            touch($destination_path . $destination, filemtime($source_path . $file));

            if ($ok) {
                $result = "OK";
            } else {
                $result = "FAIL";
            }

            $synchronisation = new PackagingSynchronisation($source_path . $file);
            $result .= " " . $synchronisation->result();

            echo $result . " : " . $destination . "\n";
        }
    }
}

closedir($dir);
