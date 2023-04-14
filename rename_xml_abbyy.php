<?php
require_once ('config.php');

set_time_limit(0);
ini_set("memory_limit","1024M");
date_default_timezone_set('US/Central');

$counter = 0;
$dirf    = DIRF;
$abbyy_filename = "'0001.xml";

$dir = scandir($dirf);

	foreach($dir as $file) {
		echo "Directory is:  " . $file . "<br>";
		
			if (strrpos( $file, DIRECTORY_IDENTIFIER) === 0) {
				$dir2 = scandir($dirf . "/" . $file);
				
				foreach($dir2 as $file2) {
					if ( ($file2 != ".") && ($file2 != "..") && ($file2 != $file . "_abbyy.xml") && ($file2 != $file . "_jp2.zip") && ($file2 != $file . "_raw_jp2.zip") ) {
						echo "File is:  " . $file2 . "<br>";
						if (strrpos( $file2, $abbyy_filename) > 0) {
							echo "I should rename:  " . $file2 . "<br>";
							echo "New Name: " . $file . "_abbyy" . ".xml" . "<br>";
							rename($dirf . "/" . $file . "/" . $file2, $dirf . "/" . $file . "/" . $file . "_abbyy" . ".xml");
						}
					}
				}
			}
	}

echo "Done";


?>