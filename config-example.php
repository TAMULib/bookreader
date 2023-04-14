<?php

$SQLIPAddress = '';
$UserName = 'ybeditor';
$Password = '';
$db = 'ybeditor';

$mssqlconnection = new PDO("dblib:host=" . $SQLIPAddress . ";dbname=" . $db, $UserName, $Password);			
		
	if (!$mssqlconnection) {
		echo "Error: Unable to connect to Library MSSQL." . PHP_EOL;
		exit;
	}	

define('DIRF', '/mnt/yearbooks'); 
define('BOOKREADER_HOSTNAME', 'dhahn.library.tamu.edu'); 
define('DIRECTORY_IDENTIFIER', 'yellbooks_'); 

?>