<?php

	require 'lib/imaging.php';

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
					$img->clear_cache();
					echo '{"success": true, "hash": "'. $sha .'"}';
				}
				else echo '{"success": false}';
			}
		}
	} else echo '{"success": false, "message": "no-file"}'

?>
