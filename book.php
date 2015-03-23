<?php
require_once('BookReader.inc.php');	

$dirf = '/mnt/yearbooks/';
$id = $_GET['id'];
$first_letter = $id[0];

	if (file_exists($dirf . $id . '/' . $id . '_meta.xml')) {
		$meta = new SimpleXMLElement($dirf . $id . '/' . $id . '_meta.xml', NULL, TRUE);
		$title = $meta->title;
	} else {
		$title = 'title';
	}
 
	if (file_exists($dirf . $id . "/" . $id . '_abbyy.gz')) {
		$search = 1;
	} else {
		$search = 0;
	}

BookReader::draw('bookreader.library.tamu.edu', 
	$dirf . $id,
	$id,
	'',
	$title,
	$search);	
	
?>
