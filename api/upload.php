<?php
set_time_limit(0);
require_once(__DIR__ . "/config.php");

$operation = @$_POST['operation'];

if(empty($_FILES)) {
	die(json_encode(['status'=>'fail', 'errors' => ["No file(s) selected."]]));
}
if(!$operation) {
	die(json_encode(['status'=>'fail', 'errors' => ["Missing upload parameter."]]));
}

if($operation == "imageUpload") {
	$errors = [];
	$subfolder = date('Ymd');
	$target_dir = __DIR__ . "/storage/images/" . $subfolder;
	
	if(!is_dir($target_dir)) mkdir($target_dir, 0775, true);
	
	$uploaded = [];
	for($i=0; $i<count($_FILES['file']['name']); $i++) {
		$file_bname = basename($_FILES['file']['name'][$i]);
		$file_size = $_FILES['file']['size'][$i];
		$file_mime = strtolower($_FILES['file']['type'][$i]);
		$file_parts = explode(".", $file_bname);
		$file_ext = array_pop($file_parts);
		
		// Check image mime/type
		if(!in_array($file_mime, ["image/png", "image/jpeg", "image/gif", "image/bmp", "image/tiff", "image/webp", "image/heic", "image/heif", "image/svg+xml", "application/octet-stream"])) {
			if(!in_array("File type not allowed. ".$file_mime, $errors)) {
				$errors[] = "File type not allowed: ".$file_mime;
			}
			continue;
		}
		// Check image size 8MB max
		if($file_size > 8388608) {
			continue;
		}
		// Rename file based on its mime/type
		switch($file_mime) {
			case 'image/jpeg':
				$file_ext = '.jpg';
				break;
			case 'image/png':
				$file_ext = '.png';
				break;
			case 'image/gif':
				$file_ext = '.gif';
				break;
			case 'image/webp':
				$file_ext = '.webp';
				break;
			case 'image/tiff':
				$file_ext = '.tiff';
				break;
			case 'image/bmp':
				$file_ext = '.bmp';
				break;
			case 'image/heic':
			case 'application/octet-stream':
				$file_ext = '.heic';
				break;
			case 'image/svg+xml':
				$file_ext = '.svg';
				break;
			default:
				break;
		}
		
		$file_name = md5(time() . implode(".", $file_parts)) . $file_ext;
		
		move_uploaded_file($_FILES['file']['tmp_name'][$i], $target_dir . "/" . $file_name);
		
		if(file_exists($target_dir . "/" . $file_name)) {
			$uploaded[] = "storage/images/" . $subfolder . "/" . $file_name;
		}
		else {
			if(!in_array("Could not upload some file(s).", $errors)) {
				$errors[] = "Could not upload some file(s).";
			}
		}
	}
	
	die(json_encode(['errors' => $errors, 'items' => $uploaded, 'status'=>'success']));
}