<?php
	header('Content-Type: application/xhtml+xml;charset=UTF-8');
	header('Vary: Accept');
	
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">' . "\n";
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8"/>
	
	<script src="lib/jquery.js" type="text/javascript"></script>
	<script src="photodump.js.php" type="text/javascript"></script>
	<link href="photodump.css" rel="stylesheet" />

	<title>PhotoDump</title>
</head>

<body onload="load()">

<div id="gora"><select></select></div>

<div id="prawo">Prawo</div>

<div id="dol">
	<div id="miniatury"></div>
</div>

<div id="lewo">Lewo</div>

<div id="pokaz"><img src="" alt="" /></div>

<div id="przelaczniki">
	<div id="przelacznik_gory"></div>
	<div id="przelacznik_prawego"></div>
	<div id="przelacznik_dolu"></div>
	<div id="przelacznik_lewego"></div>
</div>

</body>
</html>
