<?php

require_once('BookReader.inc.php');
 
//assuming your book path is /data/b/bookid
 
$id = $_GET['id'];
$first_letter = $id[0];
	
BookReader::draw('osd20.library.tamu.edu',
    '/mnt/www/library/1/index'.'/'.$id,
    $id,
    '',
    'title');	
	
?>

