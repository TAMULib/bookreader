<?php
require_once ('config.php');

require_once('BookReader.inc.php');	

$dirf = DIRF;
$id = $_GET['id'];
$first_letter = $id[0];

	if (file_exists($dirf . "/" . $id . '/' . $id . '_meta.xml')) {
		$meta = new SimpleXMLElement($dirf . "/" . $id . '/' . $id . '_meta.xml', NULL, TRUE);
		
		if (strlen($meta->year) == 0) {
			$title = $meta->title;
		} else {
			$title = $meta->title . " - " . $meta->year;
		}
	} else {
		$title = 'title';
	}
 
	if (file_exists($dirf . "/" . $id . "/" . $id . '_abbyy.gz')) {
		$search = 1;
	} else {
		$search = 0;
	}

BookReader::draw(BOOKREADER_HOSTNAME, 
	$dirf . "/" . $id,
	$id,
	'',
	$title,
	$search);	
	
?>
