<?php

set_time_limit(0);
ini_set("memory_limit","1024M");
date_default_timezone_set('US/Central');


$linefeed = PHP_EOL;
$space = " ";

echo "Begin$linefeed";
$total = 0;
$bad = 0;

$dirf    = 'C:/zz/yearbooks/';
$dir = scandir($dirf);
$tryandrecreate = false;

foreach($dir as $file) {
	if(($file!='..') && ($file!='.') && ($file!='.snapshot')) {
		echo "Directory " . $file . "$linefeed";
		$files = array();
			foreach (scandir($dirf . $file) as $singlefile) {
				$mystring = $singlefile;
				$findme   = '_jp2.zip';
				$pos = strpos($mystring, $findme);
					if ($pos === false) {
						
					} else {
						echo $singlefile . "$linefeed";
						$zip = new ZipArchive;
						echo "Going to try and open " . $dirf . $file . '/' . $mystring . "$linefeed";
						$res = $zip->open($dirf . $file . '/' . $mystring);
						if ($res === TRUE) {
							echo 'ok$linefeed$linefeed';
							
								for( $i = 0; $i < $zip->numFiles; $i++ ){ 
									$stat = $zip->statIndex( $i );
										if ($stat['size'] < 100) {
											//echo $dirf . "$linefeed";
											//echo $file . "$linefeed";											
											
											echo "The Zip file is " . $dirf . $file . '/' . $mystring . "$linefeed";
											$tifdir = str_replace("_jp2", "", $mystring);
											$tifdir = str_replace(".zip", "", $tifdir);
											$tifdir = str_replace("yb", "", $tifdir);
											//echo "TIF directory " . $tifdir . "$linefeed";
											echo "The file in the zip is " . $stat['name'] . "$linefeed";
											
											$origfilename = $stat['name'];
											//$origfilename = str_replace("_jp2/", "", $origfilename);
											$origfilename = str_replace("yb" . $tifdir . "_jp2", "", $origfilename);
											
											$newfile = str_replace(".zip", "", $singlefile);
											$newfile = str_replace($newfile . "/", "", $stat['name']);
											$newfile = str_replace(".jp2", ".tif", $newfile);
											$newfile = str_replace("_0", "_", $newfile);											
											$newfile = str_replace("yb", "", $newfile);	

											$origtif = "c:/zz/" . $tifdir . "/" . $newfile;
											
//											echo "The original TIF Should be at Location: " . $origtif . "$linefeed";
											
											$newjp2file = "c:/zz" . $origfilename;
											$bad = $bad + 1;
											
											
												if (file_exists($origtif)) {
													echo "The original file is found $origtif$linefeed$linefeed";
													
													echo "New JP2 File will be saved to: " . $newjp2file . "$linefeed$linefeed";
													echo "The size is " . $stat['size'];
													CreateImageCMD($origtif, $newjp2file);
													
												} else {
													echo "The file $origtif does not exist$linefeed$linefeed";
												}
											
											
											
											
											
										} else {
											echo "Good$linefeed";
											$total = $total + 1;
										}
								}
						} else {
							echo "failed to open " . $res . "$linefeed";
						}
						
					}
			}	

			$zip->close();
			echo "$linefeed";
		} else {
			echo 'failed, code:' . $res . '$linefeed';
		}
					
}


echo $total . "$linefeed";
echo $bad . "$linefeed";
echo "end$linefeed";

function CreateImageCMD($input, $output)
{
	try {
		
		$thecmd = 'convert ' . $input . ' ' . $output;
		exec($thecmd, $info);
		
		echo "$linefeed";
		echo "Converted tiff to jp2" . "$linefeed";
		return;
	}
	catch(Exception $e) {
		die('Error: ' . $e->getMessage());
	}	
}


?>
