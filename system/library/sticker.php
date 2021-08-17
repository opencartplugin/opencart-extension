<?php
class Sticker {
	private $scale = 30;
	private $cache = false;
	private $newimage;
	private $oldimage;
	private $storeid = 0;

	public function __construct($baseImage, $sticker, $width, $height, $storeId = 0) {
		if (!is_file(DIR_IMAGE . $baseImage)) {
			return;
		}
		$extension = pathinfo($baseImage, PATHINFO_EXTENSION);
		$this->oldimage = $baseImage;
		$this->newimage = 'cache/' . utf8_substr($baseImage, 0, utf8_strrpos($baseImage, '.')) . '-' . $storeId. '-' . $width . 'x' . $height . '.' . $extension;

		$this->topleft = $sticker['topleft_png'];
		$this->topright = $sticker['topright_png'];
		$this->bottomleft = $sticker['bottomleft_png'];
		$this->bottomright = $sticker['bottomright_png'];
		$this->height = $height;
		$this->width = $width;

	}

	public function getScale() {
		return $this->scale;
	}

	public function setCacheImage($sts = false) {
		$this->cache = $sts;
	}

	public function setScale($s = 30) {
		$this->scale = $s;
	}

	public function merge() {
		if (!is_file(DIR_IMAGE . $this->newimage) || (filectime(DIR_IMAGE . $this->oldimage) > filectime(DIR_IMAGE . $this->newimage))) {
			$path = '';

			$directories = explode('/', dirname(str_replace('../', '', $this->newimage)));

			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;

				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}
			$image = new Tinyimage(DIR_IMAGE . $this->oldimage);
			$image->resize ($this->width, $this->height); //dikasih spasi menghindari ocmodnya journal
			$final_img = imagecreatetruecolor($this->width, $this->height);
			imagealphablending($final_img, true);
			imagesavealpha($final_img, true);

			$bg = imagecreatetruecolor($this->width, $this->height);

			imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
			imagealphablending($bg, TRUE);
			imagecopy($bg, $image->getImage(), 0, 0, 0, 0, $this->width, $this->height);
			imagedestroy($image->getImage());
			$this->__imagecopymerge_alpha($final_img, $bg, 0, 0, 0, 0, $this->width, $this->height, 100);
      if (!empty($this->topleft) && file_exists(DIR_IMAGE . $this->topleft)) {
					$sticker = $this->__smart_resize_image(DIR_IMAGE . $this->topleft, $this->width * ($this->scale / 100), $this->height * ($this->scale / 100), true);
          $this->__imagecopymerge_alpha($final_img, $sticker, 0, 0, 0, 0, imagesx($sticker), imagesy($sticker), 100);
					imagedestroy($sticker);
      }
      if (!empty($this->topright) && file_exists(DIR_IMAGE . $this->topright)) {
					$sticker = $this->__smart_resize_image(DIR_IMAGE . $this->topright, $this->width * ($this->scale / 100), $this->height * ($this->scale / 100), true);
    			$this->__imagecopymerge_alpha($final_img, $sticker, imagesx($final_img) - imagesx($sticker), 0, 0, 0, imagesx($sticker), imagesy($sticker), 100);
					imagedestroy($sticker);
      }
      if (!empty($this->bottomleft) && file_exists(DIR_IMAGE . $this->bottomleft)) {
					$sticker = $this->__smart_resize_image(DIR_IMAGE . $this->bottomleft, $this->width * ($this->scale / 100), $this->height * ($this->scale / 100), true);
					$this->__imagecopymerge_alpha($final_img, $sticker, 0, imagesy($final_img) - imagesy($sticker), 0, 0, imagesx($sticker), imagesy($sticker), 100);
					imagedestroy($sticker);
      }
      if (!empty($this->bottomright) && file_exists(DIR_IMAGE . $this->bottomright)) {
					$sticker = $this->__smart_resize_image(DIR_IMAGE . $this->bottomright, $this->width * ($this->scale / 100), $this->height * ($this->scale / 100), true);
    			$this->__imagecopymerge_alpha($final_img, $sticker, imagesx($final_img) - imagesx($sticker), imagesy($final_img) - imagesy($sticker), 0, 0, imagesx($sticker), imagesy($sticker), 100);
					imagedestroy($sticker);
      }
      unset($sticker);
			$file = DIR_IMAGE . $this->newimage;
			$info = pathinfo($file);
			$extension = strtolower($info['extension']);
			if (is_resource($final_img)) {
				if ($extension == 'jpeg' || $extension == 'jpg') {
					imagejpeg($final_img, $file, 90);
				} elseif ($extension == 'png') {
					imagepng($final_img, $file);
				} elseif ($extension == 'gif') {
					imagegif($final_img, $file);
				}
				imagedestroy($final_img);
			}
			$time = time();
			$this->newimage = str_replace(' ', '%20', $this->newimage);  // fix bug when attach image on email (gmail.com). it is automatic changing space " " to +
			return 'image/' . $this->newimage . (!$this->cache ? ('?t=' . $time) : '');
		} else {
			return 'image/' . $this->newimage;
		}
	}

	function __imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct) {
      if (!isset($pct)) {
          return false;
      }
      $pct /= 100;
      // Get image width and height
      $w = imagesx($src_im);
      $h = imagesy($src_im);
      // Turn alpha blending off
      imagealphablending($src_im, false);
      // Find the most opaque pixel in the image (the one with the smallest alpha value)
      $minalpha = 127;
      for ($x = 0; $x < $w; $x++)
          for ($y = 0; $y < $h; $y++) {
              $alpha = ( imagecolorat($src_im, $x, $y) >> 24 ) & 0xFF;
              if ($alpha < $minalpha) {
                  $minalpha = $alpha;
              }
          }
      //loop through image pixels and modify alpha for each
      for ($x = 0; $x < $w; $x++) {
          for ($y = 0; $y < $h; $y++) {
              //get current alpha value (represents the TANSPARENCY!)
              $colorxy = imagecolorat($src_im, $x, $y);
              $alpha = ( $colorxy >> 24 ) & 0xFF;
              //calculate new alpha
              if ($minalpha !== 127) {
                  $alpha = 127 + 127 * $pct * ( $alpha - 127 ) / ( 127 - $minalpha );
              } else {
                  $alpha += 127 * $pct;
              }
              //get the color index with new alpha
              $alphacolorxy = imagecolorallocatealpha($src_im, ( $colorxy >> 16 ) & 0xFF, ( $colorxy >> 8 ) & 0xFF, $colorxy & 0xFF, $alpha);
              //set pixel with the new color + opacity
              if (!imagesetpixel($src_im, $x, $y, $alphacolorxy)) {
                  return false;
              }
          }
      }

      // The image copy
      imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
  }



	  function __smart_resize_image($file, $width = 0, $height = 0, $proportional = false) {
	      //return false;
	      $trnprt_color['red'] = 255;
	      $trnprt_color['green'] = 255;
	      $trnprt_color['blue'] = 255;
	      $trnprt_indx = 127;
	      if ($height <= 0 && $width <= 0)
	          return false;

	      # Setting defaults and meta
	      $info = getimagesize($file);
	      $image = '';
	      $final_width = 0;
	      $final_height = 0;
	      list($width_old, $height_old) = $info;

	      # Calculating proportionality
	      if ($proportional) {
	          if ($width == 0)
	              $factor = $height / $height_old;
	          elseif ($height == 0)
	              $factor = $width / $width_old;
	          else
	              $factor = min($width / $width_old, $height / $height_old);

	          $final_width = round($width_old * $factor);
	          $final_height = round($height_old * $factor);
	      }
	      else {
	          $final_width = ( $width <= 0 ) ? $width_old : $width;
	          $final_height = ( $height <= 0 ) ? $height_old : $height;
	      }

	      # Loading image to memory according to type
	      switch ($info[2]) {
	          case IMAGETYPE_GIF: $image = imagecreatefromgif($file);
	              break;
	          case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($file);
	              break;
	          case IMAGETYPE_PNG: $image = imagecreatefrompng($file);
	              break;
	          default: return false;
	      }


	      # This is the resizing/resampling/transparency-preserving magic
	      $image_resized = imagecreatetruecolor($final_width, $final_height);
	      if (($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG)) {
	          $transparency = imagecolortransparent($image);

	          if ($transparency >= 0) {
	              $transparent_color = imagecolorsforindex($image, $trnprt_indx);
	              $transparency = imagecolorallocate($image_resized, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
	              imagefill($image_resized, 0, 0, $transparency);
	              imagecolortransparent($image_resized, $transparency);
	          } elseif ($info[2] == IMAGETYPE_PNG) {
	              imagealphablending($image_resized, false);
	              $color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);
	              imagefill($image_resized, 0, 0, $color);
	              imagesavealpha($image_resized, true);
	          }
	      }
	      imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $final_width, $final_height, $width_old, $height_old);
	      return $image_resized;
	  }
}

class Tinyimage {
	private $file;
	private $image;
	private $width;
	private $height;
	private $bits;
	private $mime;

	public function __construct($file) {
		if (file_exists($file)) {
			$this->file = $file;

			$info = getimagesize($file);

			$this->width  = $info[0];
			$this->height = $info[1];
			$this->bits = isset($info['bits']) ? $info['bits'] : '';
			$this->mime = isset($info['mime']) ? $info['mime'] : '';

			if ($this->mime == 'image/gif') {
				$this->image = imagecreatefromgif($file);
			} elseif ($this->mime == 'image/png') {
				$this->image = imagecreatefrompng($file);
			} elseif ($this->mime == 'image/jpeg') {
				$this->image = imagecreatefromjpeg($file);
			}
		} else {
			exit('Error: Could not load image ' . $file . '!');
		}
	}

	public function getFile() {
		return $this->file;
	}

	public function getImage() {
		return $this->image;
	}


	public function save($file, $quality = 90) {
		$info = pathinfo($file);

		$extension = strtolower($info['extension']);

		if (is_resource($this->image)) {
			if ($extension == 'jpeg' || $extension == 'jpg') {
				imagejpeg($this->image, $file, $quality);
			} elseif ($extension == 'png') {
				imagepng($this->image, $file);
			} elseif ($extension == 'gif') {
				imagegif($this->image, $file);
			}

			imagedestroy($this->image);
		}
	}

	public function resize($width = 0, $height = 0, $default = '') {
		if (!$this->width || !$this->height) {
			return;
		}

		$xpos = 0;
		$ypos = 0;
		$scale = 1;

		$scale_w = $width / $this->width;
		$scale_h = $height / $this->height;

		if ($default == 'w') {
			$scale = $scale_w;
		} elseif ($default == 'h') {
			$scale = $scale_h;
		} else {
			$scale = min($scale_w, $scale_h);
		}

		if ($scale == 1 && $scale_h == $scale_w && $this->mime != 'image/png') {
			return;
		}

		$new_width = (int)($this->width * $scale);
		$new_height = (int)($this->height * $scale);
		$xpos = (int)(($width - $new_width) / 2);
		$ypos = (int)(($height - $new_height) / 2);

		$image_old = $this->image;
		$this->image = imagecreatetruecolor($width, $height);

		if ($this->mime == 'image/png') {
			imagealphablending($this->image, false);
			imagesavealpha($this->image, true);
			$background = imagecolorallocatealpha($this->image, 255, 255, 255, 127);
			imagecolortransparent($this->image, $background);
		} else {
			$background = imagecolorallocate($this->image, 255, 255, 255);
		}

		imagefilledrectangle($this->image, 0, 0, $width, $height, $background);

		imagecopyresampled($this->image, $image_old, $xpos, $ypos, 0, 0, $new_width, $new_height, $this->width, $this->height);
		imagedestroy($image_old);

		$this->width = $width;
		$this->height = $height;
	}

}
