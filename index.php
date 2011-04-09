<?php
	require_once 'lib/limonade.php';
	
	function auth() {
		$username = 'tomek'; $password = 'gofry';
		$success = (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && $_SERVER['PHP_AUTH_USER'] == $username && $_SERVER['PHP_AUTH_PW'] == $password);
		if (!$success) {
			header('WWW-Authenticate: Basic realm="Wymagane logowanie"');
			header('HTTP/1.0 401 Unauthorized');
		}
		return $success;
	}

	function configure() {
		option('env', ENV_DEVELOPMENT);
		option('base_uri', '/');
		if (gethostname() == 'satan') { option('base_uri', '/~maciek/photo'); }
	}

	dispatch('/', 'viewer');
	function viewer() {
		return render('html_viewer');
	}

	dispatch('/admin', 'show');
	function show() {
		if (!auth()) return 'niewłaściwe hasło';
	
		set('error_message', check_permissions());
		set('max_size', ini_get('post_max_size') .'B');
		
		return render('html_basics');
	}
	
	dispatch_post('/order', 'save_order');
	function save_order() {
		if (!auth()) return 'niewłaściwe hasło';
			
		if(!empty($_POST) && !empty($_POST['order'])) {
			file_put_contents('photo/meta/order', $_POST['order']);
			return '{success: true}';
		}
		else return '{success: false}';
	}
	
	dispatch('/order', 'load_order');
	function load_order() {
		return file_get_contents('photo/meta/order');
	}
	
	dispatch('/dimensions', 'get_dimensions');
	function get_dimensions() {
		$place = 'http://photo.appload.pl/';
		if (gethostname() == 'satan') { $place = 'http://localhost/~maciek/'; }
		$arr = array();
		
		$zdjecia = 'photo';
		$miniatury = $place .'thumbnail';
		if (gethostname() == 'satan') $miniatury = $place .'photo/thumbnail';

		$i = 0; foreach (new DirectoryIterator($zdjecia) as $fileInfo) {
			if($fileInfo->isDot()) continue;
			if($fileInfo->getType() == 'file') {
				list($width, $height, $type, $attr) = getimagesize($zdjecia .'/'. $fileInfo->getFilename());
				list($width_tb, $height_tb, $type_tb, $attr_tb) = getimagesize($miniatury .'/'. $fileInfo->getFilename() .'-100x60');
				$arr[] = '"'. $fileInfo->getFilename() .'" : ['. $width .', '. $height .', '. $width_tb .', '. $height_tb .']';
			}
		}
		
		return '{'. implode(", ", $arr) .'}';
	}
	
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
	
	run();

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

function html_basics($vars) { extract($vars); ?>
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
	<title>Photo Organizer</title>
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

<div id="dropzone" class="clear"><img src="lib/img/arrow.right.png" /> tutaj możesz upuszczać pliki (Firefox 3.6.13) <img src="lib/img/arrow.left.png" /></div>

<div id="help">
	<div>
		<p>Zdjęcia można sortować metodą złap i upuść.</p></div>
	<div>
		<p>Aby połączyć klika zdjęć, należy przytrzymać shift i kilkać. Na koniec puścić shift.</p>
		<p>Aby rozdzielić zdjęcia należy przytrzymać shift, kliknąć na połączone zdjęcia i puścić shift.</p></div>
	<div>
		<p>Aby usunąć zdjęcie, należy na nie kliknąć dwukrotnie.</p>
		<p>Przesyłane zdjęcie może mieć maksymalnie <?php echo $max_size; ?>.</p></div>
</div>

</body>
</html><?php
}

function html_viewer($vars) { extract($vars); ?>
<!doctype html>
<html lang="pl">
<head>
	<script src="lib/jquery.js" type="text/javascript"></script>
	<script src="dump.js" type="text/javascript"></script>
	
	<link href="lib/dump.css" rel="stylesheet" />
	<link href="dump.css" rel="stylesheet" />
	<link href="lib/img/favico.png" rel="shortcut icon" type="image/png" />
	
	<meta charset="utf-8" />
	<title>PhotoDump</title>
</head>

<body>

<div id="login"><a href="admin"><img src="lib/img/key.png" /></a></div>

<div id="top"></div>

<div id="bottom">
	<div id="miniatury"></div>
</div>

<div id="left">Lewo</div>

<div id="right">Prawo</div>

<div id="pokaz"><img src="" alt="" /></div>

<div id="przelaczniki">
	<div id="przelacznik_gory"></div>
	<div id="przelacznik_prawego"></div>
	<div id="przelacznik_dolu"></div>
	<div id="przelacznik_lewego"></div>
</div>

</body>
</html><?php
} ?>
