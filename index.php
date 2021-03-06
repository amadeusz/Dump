<?php
	require_once 'lib/limonade.php';
	
	// Limonade : Mandatory setup

	function configure() {
		option('env', ENV_DEVELOPMENT);
		option('base_uri', '/');
	}


	// Helper : Basic authentication state
	
	function auth() {
		$username = 'tomek'; $password = 'gofry';
		$success = (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && $_SERVER['PHP_AUTH_USER'] == $username && $_SERVER['PHP_AUTH_PW'] == $password);
		if (!$success) {
			header('WWW-Authenticate: Basic realm="Wymagane logowanie"');
			header('HTTP/1.0 401 Unauthorized');
		}
		return $success;
	}

	// Helper : Amazon S3 adapter

	function get_amazon() {
		$request_url = "http://fotowrocek.s3.amazonaws.com/";
		$xml = simplexml_load_file($request_url) or die("Cannot load XML data.");

		$file_list = array();
		foreach($xml->Contents as $item) $file_list[] = "". $item->Key;
		return $file_list;
	}
	
	// Helper : Enviromental check
	
	function check_permissions() {
		$folder = 'photo';
		$folder_upload = 'photo/upload';
		$folder_thumbnails = 'photo/thumbnails';
		$folder_meta = 'photo/meta';

		if (!file_exists($folder)) mkdir($folder);
		if (!file_exists($folder_upload)) mkdir($folder_upload);
		if (!file_exists($folder_thumbnails)) mkdir($folder_thumbnails);
		if (!file_exists($folder_meta)) mkdir($folder_meta);

		$error_message = '';
		if (!is_writable($folder) || !is_writable($folder_upload) || !is_writable($folder_thumbnails) || !is_writable($folder .'/meta/order'))
			$error_message = 'Błąd! Nie można przesyłać lub segregować zdjęć!';
	
		return $error_message;
	}
	
	
	// Controller : Viewer

	dispatch('/', 'viewer');
	function viewer() {
		return render('viewer_html');
	}

	// Controller : Customizer

	dispatch('/admin', 'customizer');
	function customizer() {
		if (!auth()) return 'niewłaściwe hasło';
	
		set('error_message', check_permissions());
		set('max_size', ini_get('post_max_size') .'B');
		
		if (gethostname() == 'appload')
			shell_exec('mpg123 /home/appload/sounds/ten_alert_ktorego_nie_slycahc.mp3 > /dev/null &');
		
		return render('customizer_html');
	}
	
	// Controller : Thumbnail
	
	dispatch('^/thumbnail/([a-z0-9]+)-(\d+)x(\d+)', 'aquire_thumbnail');
	function aquire_thumbnail() {
		$sha = sha1_file('photo/'. params(0));
		$thumbnail_path = 'photo/thumbnails/'. $sha ."-". params(1) ."x". params(2);
		if(!file_exists($thumbnail_path)) {
			require_once 'lib/imaging.php';
			$img = new imaging;
			$img->set_img('photo/'. params(0));
			$img->set_quality(85);
			$img->set_size(params(1), params(2));
			$img->save_img($thumbnail_path);
			$img->clear_cache();
		}
	
		if (file_exists($thumbnail_path)) {
			$fp = fopen($thumbnail_path, 'rb');

			header("Content-Type: image/png");
			header("Content-Length: ". filesize($thumbnail_path));

			return fpassthru($fp);
			exit;
		}
	}
	
	// Controller : Upload file
	
	dispatch_post('/upload', 'upload_file');
	function upload_file() {
		require_once 'lib/imaging.php';
	
		$folder = 'photo';
		$folder_zapasowy = 'photo/upload';	
	
		if(!empty($_FILES)) {
			foreach($_FILES as $plik) {
				if(!empty($plik['name'])) {
					if($plik['error'] === 0) {
						$sha = sha1_file($plik['tmp_name']);
						if(file_exists($folder_zapasowy .'/'. $sha))
							if(md5_file($folder_zapasowy .'/'. $sha) != md5_file($plik['tmp_name'])) {
								while(file_exists($folder_zapasowy .'/'. $sha))
									$sha .= 'X';
							}
						move_uploaded_file($plik['tmp_name'], $folder_zapasowy .'/'. $sha);
						
						$img = new imaging;
						$img->set_img($folder_zapasowy .'/'. $sha);
						$img->set_quality(85);
						$img->set_size(1024, 1024);
						$img->save_img($folder .'/'. $sha);
						//$img->clear_cache();
						echo '{"success": true, "hash": "'. $sha .'"}';
					}
					else echo '{"success": false}';
				}
			}
		} else echo '{"success": false, "message": "no-file"}';
	}
	
	
	// JSON API : Photo order
	
	dispatch('/order', 'load_order');
	function load_order() {
		return file_get_contents('photo/meta/order');
	}
	
	dispatch_post('/order', 'save_order');
	function save_order() {
		if (!auth()) return '{success: false}';
			
		if(!empty($_POST) && !empty($_POST['order'])) {
			file_put_contents('photo/meta/order', $_POST['order']);
			return '{success: true}';
		}
		else return '{success: false}';
	}
	
	// JSON API : Photo dimensions cache
	
	dispatch('/dimensions', 'get_dimensions');
	function get_dimensions() {
		$location = 'http://photo.appload.pl';
		if (gethostname() == 'satan') { $location = 'http://photo'; }
		
		$thumbnails_folder = $location .'/thumbnail';
		$photos_folder = 'photo';
		$dimensions_cache_file = $photos_folder .'/meta/dimensions';
		
		$dimensions = array();
		if (file_exists($dimensions_cache_file))
			$dimensions = unserialize(file_get_contents($dimensions_cache_file));
			
		$remembered_photos = array();
		foreach ($dimensions as $key => $value)
			$remembered_photos[] = $key;
		
		$found_photos = array();
		foreach (new DirectoryIterator($photos_folder) as $fileInfo) {
			if($fileInfo->isDot()) continue;
			if($fileInfo->getType() == 'file')
				$found_photos[] = $fileInfo->getFilename();
		}

		$unexisting_photos = array_diff($remembered_photos, $found_photos);
		$remembered_photos = array_diff($remembered_photos, $unexisting_photos);
		$new_photos = array_diff($found_photos, $remembered_photos);
		
		foreach ($unexisting_photos as $value)
			unset($dimensions[$value]);

		foreach ($new_photos as $value) {
			list($width, $height, $type, $attr) = getimagesize($photos_folder .'/'. $value);
			list($thumbnail_width, $thumbnail_height, $thumbnail_type, $thumbnail_attr) = getimagesize($thumbnails_folder .'/'. $value .'-100x60');
			$dimensions[$value] = array("width" => $width, "height" => $height, "thumbnail_width" => $thumbnail_width, "thumbnail_height" => $thumbnail_height);
		}
		
		if (count($new_photos) > 0 || count($unexisting_photos) > 0)
			file_put_contents($dimensions_cache_file, serialize($dimensions));
		
		$arr = array();	
		foreach ($dimensions as $key => $value)
			$arr[] = '"'. $key .'" : ['. $value["width"] .', '. $value["height"] .', '. $value["thumbnail_width"] .', '. $value["thumbnail_height"] .']';
		
		return '{'. implode(", ", $arr) .'}';
	}
	
	
	// Limonade : Mandatory initialisation
	
	run();
	

	// View : Viewer

	function viewer_html($vars) { extract($vars); ?>
<!doctype html>
<html lang="pl">
<head>
	<script src="lib/jquery.js" type="text/javascript"></script>
	<script src="dump.js" type="text/javascript"></script>
	
	<script type="text/javascript">  // Google Analytics
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', 'UA-24484590-2']);
		_gaq.push(['_trackPageview']);

		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
	</script>
	
	<link href="dump.css" rel="stylesheet" />
	
	<link href="lib/img/favico.png" rel="shortcut icon" type="image/png" />
	
	<meta charset="utf-8" />
	<title>Fotowrocek</title>
</head>

<body>
	<div id="login"><a href="admin"><img src="lib/img/key.png" /></a></div>

	<div id="top"></div>

	<div id="bottom">
		<div id="thumbnails"></div>
	</div>

	<div id="left">&nbsp;</div>
	<div id="right">&nbsp;</div>

	<div id="magnifier"><img src="" alt="" /></div>

	<div id="previous">&nbsp;</div>
	<div id="next">&nbsp;</div>

	<div id="switches">
		<div id="switch_top"></div>
		<div id="switch_right"></div>
		<div id="switch_bottom"></div>
		<div id="switch_left"></div>
	</div>
</body>
</html><?php
	}

	// View : Customizer

	function customizer_html($vars) { extract($vars); ?>
<!doctype html>
<html lang="pl">
<head>
	<script src="lib/jquery.js"></script>
	<script src="lib/jquery-ui.js"></script>
	<script src="lib/jquery.filedrop.js"></script>
	<script src="photo.js"></script>

	<link href="lib/hack.css" rel="stylesheet" />
	<link href="photo.css" rel="stylesheet" />
	
	<link href="lib/img/favico.png" rel="shortcut icon" type="image/png" />
	
	<meta charset="utf-8" />
	<title>Appload Photodump</title>
</head>
<body>

<div id="login"><a href="."><img src="lib/img/arrow_undo.png" /></a></div>

<div id="buttons">
	Album <select></select>
	<input id="new" value="Nowy album" type="button" />
	<input id="rename" value="Zmień nazwę" type="button" />
	<input id="delete" value="Usuń" type="button" />
</div>

<?php echo $error_message; ?>

<ul id="container" class="clear"></ul>

<div id="dropzone" class="clear"><img src="lib/img/arrow.right.png" /> tutaj możesz upuszczać pliki (Firefox 3.6.13+) <img src="lib/img/arrow.left.png" /></div>

<div id="help">
	<div>
		<p>Zdjęcia można sortować metodą złap i upuść.</p></div>
	<div>
		<p>Aby połączyć zdjęć, należy przytrzymać shift i kilkać. Na koniec puścić shift.</p>
		<p>Aby rozdzielić zdjęcia należy przytrzymać shift, kliknąć na połączone zdjęcia i puścić shift.</p></div>
	<div>
		<p>Aby usunąć zdjęcie, należy na nie kliknąć dwukrotnie.</p>
		<p>Przesyłane zdjęcie może mieć maksymalnie <?php echo $max_size; ?>.</p></div>
</div>

</body>
</html><?php
	} ?>
