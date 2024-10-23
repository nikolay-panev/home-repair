<?php
class Image {
	private $width = 0;
	private $height = 0;
	private $type = null;
	private $gd = null;
	
	public function __construct($path) {
		list($this->width, $this->height, $this->type) = getimagesize($path);
		
		switch($this->type) {
			case IMAGETYPE_JPEG:
				$this->gd = imagecreatefromjpeg($path);
				break;
			case IMAGETYPE_PNG:
				$this->gd = imagecreatefrompng($path);
				break;
			case IMAGETYPE_GIF:
				$this->gd = imagecreatefromgif($path);
				break;
			case IMAGETYPE_BMP:
				$this->gd = imagecreatefrombmp($path);
				break;
			case IMAGETYPE_WEBP:
				$this->gd = imagecreatefromwebp($path);
				break;
			default:
				break;
		}
	}
	
	public function toJPEG($outputPath) {
		if($this->gd) {
			imagejpeg($this->gd, $outputPath, 90);
			
			if(file_exists($outputPath)) return true;
		}
		
		return false;
	}
	
	public function resize($max_size = 1200) {
		if($this->gd) {
			$aspect_ratio = max($this->width, $this->height)/min($this->width, $this->height);
			
			if($this->width > $this->height) {
				// Landscape image
				if($this->width > $max_size) {
					// scale down
					$new_width = $max_size;
					$new_height = (int)($max_size/$aspect_ratio);
				}
				else {
					// keep original size
					$new_width = $this->width;
					$new_height = $this->height;
				}
			}
			else {
				// Portrait image
				if($this->height > $max_size) {
					$new_width = (int)($max_size/$aspect_ratio);
					$new_height = $max_size;
				}
				else {
					// keep original size
					$new_width = $this->width;
					$new_height = $this->height;
				}
			}

			// Resize the image into a temporary GD image
			$resized = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($resized, $this->gd, 0, 0, 0, 0, $new_width, $new_height, $this->width, $this->height);
			$this->gd = $resized;
			imagedestroy($resized);
		}
	}

	public function save($path) {
		if($this->gd) {
			switch($this->type) {
				case IMAGETYPE_JPEG:
					imagejpeg($this->gd, $path, 95);
					break;
				case IMAGETYPE_PNG:
					imagepng($this->gd, $path, 3);
					break;
				case IMAGETYPE_GIF:
					imagegif($this->gd, $path);
					break;
				case IMAGETYPE_BMP:
					imagebmp($this->gd, $path);
					break;
				case IMAGETYPE_WEBP:
					imagewebp($this->gd, $path, 95);
					break;
				default:
					break;
			}
		}
	}
	
	public function destroy() {
		imagedestroy($this->gd);
		
		$this->gd = null;
	}
}