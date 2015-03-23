<?php

set_time_limit(0);
ini_set("memory_limit","1024M");
date_default_timezone_set('US/Central');

$linefeed = PHP_EOL;
$space = " ";

echo "Begin:" . $linefeed;

$counter = 0;
$totalcount = 0;

$startNum = 0;  //71 = 1962
$endCount = 40;  // Number of yearbooks to process

//$dirf    			= 'c:/zz/';
//$genericimageloc    = 'c:/zz/';
//$tmpfolder 			= 'c:/tmp/';

$dirf    			= '/mnt/yearbooks/';
$genericimageloc    = '/tmp/';
$tmpfolder 			= '/tmp/';

$dir = scandir($dirf);

	foreach($dir as $file) {
		echo "Directory Name:  " . $file . $linefeed;

		//echo "Total is: " . $totalcount . " and Start Number is: " . $startNum . $linefeed;		
		
			if (strrpos( $file, 'yb') === 0) {
				
				if ( $totalcount >= $startNum ) {
					echo "Pass Gate:" . $linefeed;	
					
					mkdir($tmpfolder . $file, 0700);
				
					echo "$space$spaceProcessing Yearbook:  " . $file . $linefeed;
					$dir2 = scandir($dirf . "/" . $file);
					echo "$space$space$spaceDeleting Previous Files:" . $linefeed;
						foreach($dir2 as $file2) {
							if ( ($file2 != $file . "_abbyy.gz") && ($file2 != $file . "_abbyy.xml") && ($file2 != ".") && ($file2 != "..") && ($file2 != $file . "_jp2.zip") && ($file2 != $file . "_raw_jp2.zip") ) {

								echo "$space$space$space$spaceFile is:  " . $file2 . $linefeed;

								if (file_exists($dirf . "/" . $file . "/" . $file2)) {
									unlink($dirf . "/" . $file . "/" . $file2);
									echo "$space$space$space$space$space$file2$spaceDeleted" . $linefeed;
								}					
							}
						}
					
					echo "$linefeed$space$spaceLooking for zip file: " . $dirf . $file . '/' . $file . "_jp2.zip" . $linefeed;
					
					$zip = new ZipArchive;
					$res = $zip->open($dirf . "/" . $file . "/" . $file . "_jp2.zip");
						if ($res === TRUE) {
							echo "$space$space$space$spaceZip is Open with "; 
							echo $zip->numFiles . " files" . $linefeed;
							$counter = $zip->numFiles;

							$xFile = $file . '_0001.jp2';
								
							$im_string = $zip->getFromName($file . '_jp2/' . $xFile);
						
							$ofp = fopen( $tmpfolder . $file . '/' . $xFile, 'w' );
							//$ofp = fopen( $dirf . $file . '/' . $xFile, 'w' );

							fwrite( $ofp, $im_string );
							fclose($ofp); 
							echo "$space$spaceCover Image extracted" . $linefeed;
							$zip->close();
						} else {
							echo 'failed cover image with code:' . $res . $linefeed;
						}
						
					

					echo "$space$spaceCreate XML" . $linefeed;
					$xml = CreateXML($dirf . $file . "/" . $file . "_abbyy.xml", $file, $counter);
			//		file_put_contents($dirf . $file . '/scandata.xml', $xml);
					file_put_contents($tmpfolder . $file . '/scandata.xml', $xml);
					
					echo "$space$spaceCreate CreateFilesXML" . $linefeed;
					$xml = CreateFilesXML($file, $dirf);
			//		file_put_contents($dirf . $file . '/' . $file . '_files.xml', $xml);
					file_put_contents($tmpfolder . $file . '/' . $file . '_files.xml', $xml);
					
					echo "$space$spaceCreate CreateMetadataFilesXML" . $linefeed;
					$xml = CreateMetadataFilesXML($file);
			//		file_put_contents($dirf . $file . '/' . $file . '_meta.xml', $xml);			
					file_put_contents($tmpfolder . $file . '/' . $file . '_meta.xml', $xml);	
					
					echo "$space$spaceCreate CreateImageCMD" . $linefeed;
					CreateImageCMD($file, $dirf, $tmpfolder);
					
//					CreateImage($file, $dirf);
			
					echo "$space$spaceCreate CreateZip" . $linefeed;
					CreateZip($file, $dirf, $genericimageloc);
					
					echo "$space$spaceCreate CreateAbbyygz" . $linefeed;
					CreateAbbyygz($file, $dirf, $tmpfolder);
					
					echo $linefeed;
				}
				
				$totalcount = $totalcount + 1;
				//echo $linefeed;
					
			} else {
				$totalcount = $totalcount + 1;
			//	echo "$space$space$space$space$spaceSkipping Directory" . $linefeed;
			}
		
		$counter = 0;
		
		if ( ($startNum + $endCount) <= $totalcount ) {
			echo ($startNum + $endCount) . $linefeed;
			echo  $totalcount . $linefeed;
			echo "break";
			break;
		}
	}


echo "Done";

function CreateXML($file, $bookid, $counter)
{

	$xml = '<book xmlns="http://archive.org/scribe/xml">' . "\r\n";
	$xml = $xml . '<bookData>' . "\r\n";
    $xml = $xml . '<bookId>' . $bookid . '</bookId>' . "\r\n";
	$xml = $xml . '</bookData>' . "\r\n";
	$xml = $xml . '<pageData>' . "\r\n";
	
	echo "Looking for file: " .  $file;
	
		if (file_exists($file)) {
			$backupplan = false;
			echo "$space$space$space$spaceFound Abbyy XML" . $linefeed;
			
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
					echo "$space$space$space$spaceTotal XML Page elements processed for Abbyy: " . ($xmlpage - 1) . $linefeed;
				}	catch(Exception $e) {
				
					echo "Error Loading Abbyy";
					$backupplan = true;
				}	
			
			} else {
				$backupplan = true;
			}
			
			if ($backupplan) {
				echo "$space$space$space$spaceAbbyy xml not found or corrupt going to do it the hard way." . $linefeed;
			
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

	$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n";
	$xml = $xml . '<files>' . "\r\n";
	
		if (file_exists($dir . $bookid . '/' . $bookid . '_raw_jp2.zip')) {
			$xml = $xml . '<file name="' . $bookid . '_raw_jp2.zip" source="original">' . "\r\n";
			$xml = $xml . '<format>Single Page Raw JP2 ZIP</format>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
	
		if (file_exists($dir . $bookid . '/' . $bookid . '_jp2.zip')) {
			$xml = $xml . '<file name="' . $bookid . '_jp2.zip" source="derivative">' . "\r\n";
			$xml = $xml . '<format>Single Page Processed JP2 ZIP</format>' . "\r\n";
			$xml = $xml . '<original>' . $bookid . '_raw_jp2.zip</original>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
		if (file_exists($dir . $bookid . '/' . $bookid . '_meta.xml')) {
			$xml = $xml . '<file name="' . $bookid . '_meta.xml" source="metadata">' . "\r\n";
			$xml = $xml . '<format>Metadata</format>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
		if (file_exists($dir . $bookid . '/' . $bookid . '_files.xml')) {
			$xml = $xml . '<file name="' . $bookid . '_files.xml" source="metadata">' . "\r\n";
			$xml = $xml . '<format>Metadata</format>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
		if (file_exists($dir . $bookid . '/' . $bookid . '_cover_image.jpg')) {
			$xml = $xml . '<file name="' . $bookid . '_cover_image.jpg" source="original">' . "\r\n";
			$xml = $xml . '<format>Book Cover Image</format>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
		if (file_exists($dir . $bookid . '/' . $bookid . '_abbyy.gz')) {
			$xml = $xml . '<file name="' . $bookid . '_abbyy.gz" source="original">' . "\r\n";
			$xml = $xml . '<format>OCR</format>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
		if (file_exists($dir . $bookid . '/' . $bookid . '_abbyy.xml')) {
			$xml = $xml . '<file name="' . $bookid . '_abbyy.xml" source="original">' . "\r\n";
			$xml = $xml . '<format>OCR</format>' . "\r\n";
			$xml = $xml . '</file>' . "\r\n";
		}
		
	$xml = $xml . '</files>' . "\r\n";

	return $xml;
}


function CreateMetadataFilesXML($bookid)
{

	$SQLIPAddress = 'mssql-dev3';
	$UserName = 'ezpeditor';
	$Password = 'eZpEd1t0r';
	$db = 'ybeditor';

	$link = mssql_connect($SQLIPAddress,$UserName,$Password) or 
		die("Couldn't connect to SQL Server on $SQLIPAddress"); 

	mssql_select_db($db, $link) or 
		die("Couldn't connect to database on $db"); 
		
	$query = "SELECT id, yb_id, title, year, creator, [description] as descrip, bookrecord, users_id, last_update, status FROM yb_metadata where yb_id = '" . $bookid . "'";

	$rs = mssql_query($query) or die("SQL errror");

	 while($row = mssql_fetch_array( $rs )) { 
			
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
	
	mssql_close($link);		
	
	return $xml;
}

function CreateImage($bookid, $dir, $tmpfolder)
{
	try {
//		echo $dir . $bookid . '/' . $bookid . '_0001.jp2' . $linefeed;
//		echo $dir . $bookid . '/' . $bookid . '_cover_image.jpg'. $linefeed;

		echo $tmpfolder . $bookid . '/' . $bookid . '_0001.jp2' . $linefeed;
		echo $tmpfolder . $bookid . '/' . $bookid . '_cover_image.jpg'. $linefeed;
		
		echo "x";
		$image = new Imagick();
		echo "y";
		//$image->readImage( $dir . '/' . $bookid . '/' . $bookid . '_0001.jp2' );
		$image->readImage( $tmpfolder . $bookid . '/' . $bookid . '_0001.jp2' );		
		$image->setImageFormat('jpg');
		$image->adaptiveResizeImage(720,965);
//		$image->writeImage($dir . '/' . $bookid . '/' . $bookid . '_cover_image.jpg');	
		$image->writeImage($tmpfolder . $bookid . '/' . $bookid . '_cover_image.jpg');			
		echo "Cover Created";
		return;
	}
	catch(Exception $e) {
		die('Error: ' . $e->getMessage());
	}	
}

function CreateImageCMD($bookid, $dir, $tmpfolder)
{
	try {
		//$input = $dir . '/' . $bookid . '/' . $bookid . '_0001.jp2';
		//$output = $dir . '/' . $bookid . '/' . $bookid . '_cover_image.jpg';

		$input = $tmpfolder . $bookid . '/' . $bookid . '_0001.jp2';
		$output = $tmpfolder . $bookid . '/' . $bookid . '_cover_image.jpg';
		
		$thecmd = 'convert -resize 15% ' . $input . ' ' . $output;
//		echo $thecmd;
		exec($thecmd, $info);
		
		//echo $linefeed;
		echo "$space$space$spaceCover Created" . $linefeed;
		return;
	}
	catch(Exception $e) {
		die('Error: ' . $e->getMessage());
	}	
}

function CreateZip($bookid, $dir, $imageloc)
{
	$zip = new ZipArchive();
	
	//$filename = $dir . '/' . $bookid . '/scandata.zip';
	$filename = '/tmp' . '/' . $bookid . '/scandata.zip';

	echo "$space$space$space$space$space$space" . $dir . $bookid . '/scandata.zip' . $linefeed;
	
		if ($zip->open($filename, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)!==TRUE) {
			exit("cannot open <$filename>\n");
		}

//	echo $imageloc . "l.jpg" . $linefeed;	
//	echo $imageloc . "r.jpg" . $linefeed;	
	
	$zip->addFile($imageloc . "l.jpg", "l.jpg");
	$zip->addFile($imageloc . "r.jpg", "r.jpg");
	$zip->addFile($dir . $bookid . '/' . $bookid . '_cover_image.jpg', '' . $bookid . '_cover_image.jpg');
	$zip->addFile($dir . $bookid . '/scandata.xml', 'scandata.xml');

	echo "$space$space$space$space$space$spaceNumber of Files added to zip: " . $zip->numFiles . $linefeed;
	//echo "status:" . $zip->status . $linefeed;
	echo $linefeed;
	$zip->close();
		
	return;
}

function CreateAbbyygz($bookid, $dir, $tmpfolder)
{
	//$filename = $dir . '/' . $bookid . '_abbyy.gz' . $linefeed;
	
	$filename = $tmpfolder . $bookid . '/' . $bookid . '_abbyy.tar';
	$filename2 = $tmpfolder . $bookid . '/' . $bookid . '_abbyy.gz';	
	//echo $dir; = /mnt/yearbooks/
	// Creating the .gz with the double extension saves the extension inside of the .gz
	
	$abbyxmlfile = $dir . $bookid . '/' . $bookid . '_abbyy.xml';
	
	echo "$space$space$space$spacegz Location " . $filename . $linefeed;
	echo "$space$space$space$spaceXML Location " . $abbyxmlfile . $linefeed;

	if (file_exists($abbyxmlfile)) {
	
		try
		{
		
			//$thecmd = 'tar -cvf ' . $filename . ' -C ' . $dir . $bookid . '/ ' . $bookid . '_abbyy.xml';
			//echo $thecmd;
			//exec($thecmd, $info);
			
			//$thecmd = 'gzip ' . $filename . ' ' . $filename2;
			//echo $thecmd;
			//exec($thecmd, $info);
			
			//echo $dir . $bookid . '/' . $bookid . '_abbyy.xml';
			
			$thecmd = 'gzip -c ' . $dir . $bookid . '/' . $bookid . '_abbyy.xml' . ' > ' . $filename2;
			echo $thecmd;
			exec($thecmd, $info);
			
			
		
		} 
		catch (Exception $e) 
		{
			echo "Exception : " . $e;
		}
		
	} else {
		echo "$space$space$space$spaceNo _Abbyy.XML found" . $linefeed;
	}
	
	return;
}

?>