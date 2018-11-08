<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors','stdout');

//the tail end of the XML filename + an array of its xml elements to use
$xmlpostfix = array("meta"=>array('title'=>'title','creator'=>'creator','description'=>'description','year'=>'year'),"files"=>array('cover'=>'Book Cover Image'));

$basedir = "/mnt/yearbooks/";

if ($files = scandir($basedir)) {
	$files = array_diff($files, array('..', '.'));
	foreach ($files as $subdir) {
//		echo "<br>";
//		echo "SubDirectory: " . $subdir;
		if (stristr($subdir,'yb') !== false) {
			if (file_exists("{$basedir}{$subdir}/{$subdir}_files.xml")) {
				$xml_content .= '<book>';
				$xml_content .= "<path>{$subdir}</path>";
				foreach ($xmlpostfix as $postfix=>$elements) {
					$xmlfile = "{$basedir}{$subdir}/{$subdir}_{$postfix}.xml";
			//		echo "<br>";
			//		echo "The File: " . $xmlfile . "<br>";
			//		echo "The SubDirectory: " . $subdir . "<br>";
					if (is_file($xmlfile)) {
			//			echo "Here 0" . "<br>";
						if (@simplexml_load_file($xmlfile)) {
			//				echo "Here 1" . "<br>";
							try {
								$xml = simplexml_load_file($xmlfile);
								$ybid = $subdir;
			//					echo "Here " . $postfix . "<br>";
								if ($postfix == 'files') {
									foreach ($elements as $tag=>$item) {
										foreach ($xml->file as $file) {
											if ($file->format == $item) {
												$temp = $file['name'];
												break;
											}
										}
										$xml_content .= "<{$tag}>".$temp."</{$tag}>";
	//									echo "<br>$tag<br>";
										$temp = NULL;
									}
								} else {
	//								echo "Meta<br>";

	//								echo "The ID: " . $ybid . "<br>";
									$SQLIPAddress = 'mssql-prod2';
									$UserName = 'ybeditor';
									$Password = 'hIkhLCu$iHBCwt6c';
									$db = 'ybeditor';
									mssql_connect($SQLIPAddress,$UserName,$Password) or die('MSSQL error: ' . mssql_get_last_message());
									mssql_select_db($db) or die(mssql_error());

									$sqlresources = "SELECT * FROM yb_metadata where yb_id = '" . $ybid  . "'";
			//						echo $sqlresources;
									$rs = mssql_query($sqlresources) or die('MSSQL error: ' . mssql_get_last_message());
									$row = mssql_fetch_array( $rs );

									foreach ($elements as $tag=>$item) {
	//									echo "<br>$tag<br>";
										if ($tag == 'description') {
											$xml_content .= "<{$tag}>" . str_replace('<br>', '<br/>', str_replace('&', '&amp;', $row['description'])) . "</{$tag}>";
										}
										elseif ($tag == 'title') {
											$xml_content .= "<{$tag}>" . str_replace('&', '&amp;', $row['title']) . "</{$tag}>";
										}
										elseif ($tag == 'year') {
											$xml_content .= "<{$tag}>" . str_replace('&', '&amp;', $row['year']) . "</{$tag}>";
										}
										elseif ($tag == 'creator') {
											$xml_content .= "<{$tag}>" . str_replace('&', '&amp;', $row['creator']) . "</{$tag}>";
										}										
										else {
											$xml_content .= "<{$tag}>" . str_replace('&', '&amp;', $xml->$item) . "</{$tag}>";
										}
									}

									$xml_content .= "<showcover>" . str_replace('&', '&amp;', $row['showcover']) . "</showcover>";
									$xml_content .= "<showyearbook>" . str_replace('&', '&amp;', $row['showyearbook']) . "</showyearbook>";
			
								}
								unset ($xml);
							} catch (Exception $e) {
								echo 'Caught: '.$e->getMessage(); 
							}
						} else {
							//echo "Not XML" . $ybid;
							$xml_content .= "<title></title><creator></creator><description></description><year></year><showcover>0</showcover><showyearbook>0</showyearbook>";

						}
					}
				}
				$xml_content .= '</book>';
			} else {
			//	echo "{$basedir}{$subdir}/{$subdir}_files.xml";
			}
		} else {
			//echo $subdir;
			//echo "<br>";
		}
	}
	header("Content-type: text/xml");
	echo '<?xml version="1.0" encoding="UTF-8"?>
			<books>'.$xml_content.'</books>';

}
?>