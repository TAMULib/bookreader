<?php
require_once ('config.php');

set_time_limit(0);
ini_set("memory_limit","1024M");
date_default_timezone_set('US/Central');

$linefeed = "<br />";
$space = "&nbsp;";

echo "Begin:" . $linefeed;

$counter = 0;
$totalcount = 0;

$startNum = 0;  
$endCount = 1;  // Number of directories to process

// this is defined in the config.php
$dirf    = DIRF;
$dir = scandir($dirf);

	foreach($dir as $file) {
		echo "Directory Name:  " . $file . $linefeed;

		//echo "Total is: " . $totalcount . " and Start Number is: " . $startNum . $linefeed;		
		
			if (strrpos( $file, DIRECTORY_IDENTIFIER) === 0) {
				
				if ( $totalcount >= $startNum ) {
					echo "Pass Gate:" . $linefeed;	
					
					echo $space . $space . "Processing Yearbook:  " . $file . $linefeed;
					$dir2 = scandir($dirf . "/" . $file);
					echo $space . $space . $space . $space . "Deleting Previous Files:" . $linefeed;
					
						foreach($dir2 as $file2) {
							if ( ($file2 != $file . ".php") && ($file2 != $file . "_abbyy.gz") && ($file2 != $file . "_abbyy.xml") && ($file2 != ".") && ($file2 != "..") && ($file2 != $file . "_jp2.zip") && ($file2 != $file . "_raw_jp2.zip") ) {
								echo $space . $space . $space . $space . $space . "File is:  " . $file2 . $linefeed;
									if (file_exists($dirf . "/" . $file . "/" . $file2)) {
										unlink($dirf . "/" . $file . "/" . $file2);
										echo $space . $space . $space . $space . $space . $file2 . $space . "Deleted" . $linefeed;
									}
							}
						}
					
					echo $linefeed . $space . $space . "Looking for zip file: " . $dirf . '/' . $file . '/' . $file . "_jp2.zip" . $linefeed;
					
					$zip = new ZipArchive;
					$res = $zip->open($dirf . "/" . $file . "/" . $file . "_jp2.zip");
						if ($res === TRUE) {
							echo $space . $space . $space . $space . "Zip is Open with "; 
							echo $zip->numFiles . " files" . $linefeed;
							$counter = $zip->numFiles;

							$xFile = $file . '_0001.jp2';
								
							$im_string = $zip->getFromName($file . '_jp2/' . $xFile);
						
							$ofp = fopen( $dirf . "/" . $file . '/' . $xFile, 'w' );

							fwrite( $ofp, $im_string );
							fclose($ofp); 
							echo $space . $space . "Cover Image extracted" . $linefeed;
							$zip->close();
						} else {
							echo $space . $space . "failed cover image with code:" . $res . $linefeed;
						}
						
					

					echo $space . $space . "Create XML" . $linefeed;
					$xml = CreateXML($dirf . "/" . $file . "/" . $file . "_abbyy.xml", $file, $counter);
					file_put_contents($dirf . "/" . $file . '/scandata.xml', $xml);
					
					echo $space . $space . "Create FilesXML" . $linefeed;
					$xml = CreateFilesXML($file, $dirf);
					file_put_contents($dirf . "/" . $file . '/' . $file . '_files.xml', $xml);
					
					echo $space . $space . "Create MetadataFilesXML" . $linefeed;
					$xml = CreateMetadataFilesXML($file, $SQLIPAddress, $UserName, $Password, $db);
					file_put_contents($dirf . "/" . $file . '/' . $file . '_meta.xml', $xml);			
					
					echo $space . $space . "Create ImageCMD" . $linefeed;
					CreateImageCMD($file, $dirf, $tmpfolder);
					
//					CreateImage($file, $dirf);
			
					echo $space . $space . "Create Zip" . $linefeed;
					CreateZip($file, $dirf, $genericimageloc);
					
					echo $space . $space . "Create Abbyy.gz" . $linefeed;
					CreateAbbyygz($file, $dirf, $tmpfolder);
					
					echo $linefeed;
				}
				
				$totalcount = $totalcount + 1;
				//echo $linefeed;
					
			} else {
				
			//	$totalcount = $totalcount + 1;
				echo $space . $space . $space . $space . $space . "Skipping Directory" . $linefeed;
			}
		
		$counter = 0;
		
		if ( ($startNum + $endCount) <= $totalcount ) {
			echo $linefeed;
			//echo ($startNum + $endCount) . $linefeed;
			//echo  $totalcount . $linefeed;
			echo "I have done " . ($startNum + $endCount) . " and I should break" . $linefeed;
			break;
		}
		
	}


echo "Done";

function CreateXML($file, $bookid, $counter)
{
global $linefeed;
global $space;

	$xml = '<book xmlns="http://archive.org/scribe/xml">' . "\r\n";
	$xml = $xml . '<bookData>' . "\r\n";
    $xml = $xml . '<bookId>' . $bookid . '</bookId>' . "\r\n";
	$xml = $xml . '</bookData>' . "\r\n";
	$xml = $xml . '<pageData>' . "\r\n";
	
	echo "Looking for file: " .  $file . $linefeed;
	
		if (file_exists($file)) {
			$backupplan = false;
			echo $space . $space . $space . $space . "Found Abbyy XML" . $linefeed;

				try {
				$abbyy = new SimpleXMLElement($file, NULL, TRUE);

					$xmlpage = 1;
					
					foreach ($abbyy->page as $page) {
						$xml = $xml . '<page leafNum="' . $xmlpage . '">' . "\r\n";

							if ($xmlpage % 2 == 0) {
								$xml = $xml . '<handSide>LEFT</handSide>' . "\r\n";
							} else {
								$xml = $xml . '<handSide>RIGHT</handSide>' . "\r\n";
							}
					
						$xml = $xml .  '<pageNumber/>' . "\r\n";
						
							if ($xmlpage == 1) {
								$xml = $xml .  '<pageType>' . 'Cover' . '</pageType>' . "\r\n";
							} else {
								$xml = $xml .  '<pageType>' . 'Normal' . '</pageType>' . "\r\n";
							}
						
						$xml = $xml .  '<addToAccessFormats>true</addToAccessFormats>' . "\r\n";
						$xml = $xml .  '<cropBox>' . "\r\n";
						$xml = $xml .  '<x>1</x>' . "\r\n";
						$xml = $xml .  '<y>1</y>' . "\r\n";

						$xml = $xml .  '<w>' . $page['width'] . '</w>' . "\r\n";
						$xml = $xml .  '<h>' . $page['height'] . '</h>' . "\r\n";

	//					$xml = $xml .  '<w>' . '1764' . '</w>' . "\r\n";
	//					$xml = $xml .  '<h>' . '2460' . '</h>' . "\r\n";

						
						$xml = $xml .  '</cropBox>' . "\r\n";
						$xml = $xml .  '</page>' . "\r\n";			

						$xmlpage = $xmlpage + 1;
					}
					echo $space . $space . $space . $space . "Total XML Page elements processed for Abbyy: " . ($xmlpage - 1) . $linefeed;
				}	catch(Exception $e) {
				
					echo "Error Loading Abbyy";
					$backupplan = true;
				}	
			
			} else {
				$backupplan = true;
			}
			
			if ($backupplan) {
				echo $space . $space . $space . $space . "Abbyy xml not found or corrupt going to do it the hard way." . $linefeed;
			
				for ($i=1; $i<=$counter; $i++)
					  {
					  
						$xml = $xml . '<page leafNum="' . $i . '">' . "\r\n";

							if ($i % 2 == 0) {
								$xml = $xml . '<handSide>RIGHT</handSide>' . "\r\n";
							} else {
								$xml = $xml . '<handSide>LEFT</handSide>' . "\r\n";
							}
							
						$xml = $xml .  '<pageNumber/>' . "\r\n";
							if ($i == 1) {
								$xml = $xml .  '<pageType>' . 'Cover' . '</pageType>' . "\r\n";
							} else {
								$xml = $xml .  '<pageType>' . 'Normal' . '</pageType>' . "\r\n";
							}						
						$xml = $xml .  '<pageType>Normal</pageType>' . "\r\n";
						$xml = $xml .  '<addToAccessFormats>true</addToAccessFormats>' . "\r\n";
						$xml = $xml .  '<cropBox>' . "\r\n";
						$xml = $xml .  '<x>1</x>' . "\r\n";
						$xml = $xml .  '<y>1</y>' . "\r\n";
						$xml = $xml .  '<w>2370.0</w>' . "\r\n";
						$xml = $xml .  '<h>3146.0</h>' . "\r\n";
						$xml = $xml .  '</cropBox>' . "\r\n";
						$xml = $xml .  '</page>' . "\r\n";

					}	
			}
	
	$xml = $xml . '</pageData>' . "\r\n";
	$xml = $xml . '</book>' . "\r\n";
	
	return $xml;
}

function CreateFilesXML($bookid, $dir)
{
global $linefeed;
global $space;

	$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
	$xml = $xml . '<files>' . "\r\n";
	
		if (file_exists($dir . '/' . $bookid . '/' . $bookid . '_raw_jp2.zip')) {
			$xml = $xml . '<file name="' . $bookid . '_raw_jp2.zip" source="original">' . "\r\n";
			$xml = $xml . '<format>Single Page Raw JP2 ZIP</format>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
	
		if (file_exists($dir . '/' . $bookid . '/' . $bookid . '_jp2.zip')) {
			$xml = $xml . '<file name="' . $bookid . '_jp2.zip" source="derivative">' . "\r\n";
			$xml = $xml . '<format>Single Page Processed JP2 ZIP</format>' . "\r\n";
			$xml = $xml . '<original>' . $bookid . '_raw_jp2.zip</original>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
		if (file_exists($dir . '/' . $bookid . '/' . $bookid . '_meta.xml')) {
			$xml = $xml . '<file name="' . $bookid . '_meta.xml" source="metadata">' . "\r\n";
			$xml = $xml . '<format>Metadata</format>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
		if (file_exists($dir . '/' . $bookid . '/' . $bookid . '_files.xml')) {
			$xml = $xml . '<file name="' . $bookid . '_files.xml" source="metadata">' . "\r\n";
			$xml = $xml . '<format>Metadata</format>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
		if (file_exists($dir . '/' . $bookid . '/' . $bookid . '_cover_image.jpg')) {
			$xml = $xml . '<file name="' . $bookid . '_cover_image.jpg" source="original">' . "\r\n";
			$xml = $xml . '<format>Book Cover Image</format>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
		if (file_exists($dir . '/' . $bookid . '/' . $bookid . '_abbyy.gz')) {
			$xml = $xml . '<file name="' . $bookid . '_abbyy.gz" source="original">' . "\r\n";
			$xml = $xml . '<format>OCR</format>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
		if (file_exists($dir . '/' . $bookid . '/' . $bookid . '_abbyy.xml')) {
			$xml = $xml . '<file name="' . $bookid . '_abbyy.xml" source="original">' . "\r\n";
			$xml = $xml . '<format>OCR</format>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
		
	$xml = $xml . '</files>' . "\r\n";

	return $xml;
}

function CreateMetadataFilesXML($bookid, $SQLIPAddress, $UserName, $Password, $db)
{
global $linefeed;
global $space;

	$link = new PDO("dblib:host=" . $SQLIPAddress . ";dbname=" . $db, $UserName, $Password) or 
		die("Couldn't connect to SQL Server on $SQLIPAddress");

	$query = "SELECT id, yb_id, title, year, creator, [description] as descrip, bookrecord, users_id, last_update, status FROM yb_metadata where yb_id = '" . $bookid . "'";

	echo $query;

	$row_count = 0;

		foreach ($link->query($query) as $row) {
			echo "<br />" . "I found a record" . "<br />";
			$row_count = $row_count + 1;
			
			$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
			$xml = $xml . '<metadata>' . "\r\n";
			$xml = $xml . '<identifier>' . trim($row['yb_id']) . '</identifier>' . "\r\n";
			$xml = $xml . '<title>' . trim($row['title']) . '</title>' . "\r\n";
			$xml = $xml . '<creator>' . trim($row['creator']) . '</creator>' . "\r\n";
			$xml = $xml . '<description>' . trim(str_replace('<br>', '<br />', $row['descrip'])) . '</description>' . "\r\n";    
			$xml = $xml . '<bookrecord>' . trim($row['bookrecord']) . '</bookrecord>' . "\r\n";   
			$xml = $xml . '<year>' . trim($row['year']) . '</year>' . "\r\n";   	
			$xml = $xml . '</metadata>' . "\r\n";
			
		} 
	
	if ($row_count == 0) {
		echo "<br />";
		echo "I don't have one: " . $row_count . "<br />";

		$query = "INSERT into yb_metadata (yb_id, showcover, showyearbook) values ('" . $bookid . "', 0, 0)";
		$link->query($query);

			$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
			$xml = $xml . '<metadata>' . "\r\n";
			$xml = $xml . '<identifier>' . trim($bookid) . '</identifier>' . "\r\n";
			$xml = $xml . '<title></title>' . "\r\n";
			$xml = $xml . '<creator></creator>' . "\r\n";
			$xml = $xml . '<description></description>' . "\r\n";    
			$xml = $xml . '<bookrecord></bookrecord>' . "\r\n";   
			$xml = $xml . '<year></year>' . "\r\n";   	
			$xml = $xml . '</metadata>' . "\r\n";
		
	}
	
	return $xml;
	
}

function CreateImage($bookid, $dir, $tmpfolder)
{
global $linefeed;
global $space;

	try {
//		echo $dir . $bookid . '/' . $bookid . '_0001.jp2' . $linefeed;
//		echo $dir . $bookid . '/' . $bookid . '_cover_image.jpg'. $linefeed;

		echo $dir . '/' . $bookid . '/' . $bookid . '_0001.jp2' . $linefeed;
		echo $dir . '/' . $bookid . '/' . $bookid . '_cover_image.jpg'. $linefeed;
		
		$image = new Imagick();
		$image->readImage( $dir . '/' . $bookid . '/' . $bookid . '_0001.jp2' );
		$image->setImageFormat('jpg');
		$image->adaptiveResizeImage(720,965);
		$image->writeImage($dir . '/' . $bookid . '/' . $bookid . '_cover_image.jpg');	
		echo "Cover Created";
		return;
	}
	catch(Exception $e) {
		die('Error: ' . $e->getMessage());
	}	
}

function CreateImageCMD($bookid, $dir, $tmpfolder)
{
global $linefeed;
global $space;

	try {
		$input = $dir . '/' . $bookid . '/' . $bookid . '_0001.jp2';
		$output = $dir . '/' . $bookid . '/' . $bookid . '_cover_image.jpg';

		$thecmd = 'convert -resize 15% ' . $input . ' ' . $output;
//		echo $thecmd;
		exec($thecmd, $info);
		
		//echo $linefeed;
		echo $space . $space . $space . "Cover Created" . $linefeed;
		return;
	}
	catch(Exception $e) {
		die('Error: ' . $e->getMessage());
	}	
}

function CreateZip($bookid, $dir, $imageloc)
{
global $linefeed;
global $space;

	$zip = new ZipArchive();
	
	$filename = $dir . '/' . $bookid . '/scandata.zip';
	
	echo $space . $space .$space .$space .$space .$space . $dir . '/' . $bookid . '/scandata.zip' . $linefeed;
	
		if ($zip->open($filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)!==TRUE) {
			exit("cannot open <$filename>\n");
		}

//	echo $imageloc . "l.jpg" . $linefeed;	
//	echo $imageloc . "r.jpg" . $linefeed;	
	
	$zip->addFile($imageloc . "l.jpg", "l.jpg");
	$zip->addFile($imageloc . "r.jpg", "r.jpg");
	$zip->addFile($dir . '/' . $bookid . '/' . $bookid . '_cover_image.jpg', '' . $bookid . '_cover_image.jpg');
	$zip->addFile($dir . '/' . $bookid . '/' . 'scandata.xml', 'scandata.xml');

	echo $space . $space .$space .$space .$space .$space . "Number of Files added to zip: " . $zip->numFiles . $linefeed;
	//echo "status:" . $zip->status . $linefeed;
	echo $linefeed;
	$zip->close();
		
	return;
}

function CreateAbbyygz($bookid, $dir, $tmpfolder)
{
global $linefeed;
global $space;

	$filename = $dir . '/' . $bookid . '/' . $bookid . '_abbyy.gz';	
	$filename2 = $dir . '/' . $bookid . '/' . $bookid . '_abbyy.gz';	

	// Creating the .gz with the double extension saves the extension inside of the .gz
	
	$abbyxmlfile = $dir . '/' . $bookid . '/' . $bookid . '_abbyy.xml';
	
	echo $space . $space .$space .$space . "gz Location " . $filename . $linefeed;
	echo $space . $space .$space .$space . "_abbyy.gz location " . $filename2 . $linefeed;
	echo $space . $space .$space .$space . "XML Location " . $abbyxmlfile . $linefeed;

	if (file_exists($abbyxmlfile)) {
	
		try
		{
			
			$thecmd = 'gzip -c ' . $dir . '/' . $bookid . '/' . $bookid . '_abbyy.xml' . ' > ' . $filename2;
			echo $thecmd;
			exec($thecmd, $info);
			
		
		} 
		catch (Exception $e) 
		{
			echo "Exception : " . $e;
		}
		
	} else {
		echo $space . $space .$space .$space . "No _Abbyy.XML found" . $linefeed;
	}
	
	return;
}

?>