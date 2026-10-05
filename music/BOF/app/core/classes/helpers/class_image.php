<?php

if ( !defined("root" ) ) die;

class image extends bof_type_class {

	public $path = null;
	public $source = null;
	public $width = null;
	public $height = null;
	public $format = null;

	public function change_source( $newSource, $full=false ){

		if ( $this->source )
		$this->unset();

		$this->source = $newSource;

		if ( $full ){
			if ( !$this->source ) 
			throw new Exception("Setting new source full failed");
			$this->width = imagesx( $this->source );
			$this->height = imagesy( $this->source );
		}

	}
	public function set( $i, $args=[] ){

		$this->path = null;
		$this->format = null;

		if ( gettype( $i ) == "string" && ctype_print( $i ) ? is_file( $i ) : false ){
			$this->path = $i;
			$this->format = pathinfo( $i, PATHINFO_EXTENSION );
		}

		try {
			$_source = $this->createfrom( $i, array_merge( $args, [ "set" => true ] ) );
		} catch ( Exception|Error|Warning $err ){
			fall("Invalid image -> " . $err->getMessage() );
		}

		$this->change_source( $_source, true );

		return $this;

	}
	public function unset(){

		if ( $this->source ? gettype($this->source) == "object" && get_class($this->source) == "GdImage" : false )
		imagedestroy( $this->source );

		$this->source = $this->width = $this->height = $this->path = null;

	}
	public function createfrom( $i, $args=[] ){

		$ext = null;
		$set = false;
		extract( $args );

		if ( gettype( $i ) == "string" && ctype_print( $i ) ? is_file( $i ) : false ){

			if ( strtolower( $ext ? $ext : pathinfo( $i, PATHINFO_EXTENSION ) ) == "png" ){

				// Create an image wrapper and fill it with transparent color
				$_dimensions = getimagesize( $i );
				$_tp_image  = imagecreatetruecolor( $_dimensions[0], $_dimensions[1] );
				$_tp_color = imagecolorallocatealpha( $_tp_image, 0, 0, 0, 127 );
				imagecolortransparent( $_tp_image, $_tp_color );
				imagefill( $_tp_image, 0, 0, $_tp_color );

				// Create image from source and copy it to wrapper
				$_src_image = imagecreatefrompng( $i );
				if ( !$_src_image ) throw new Exception("Unable to create PNG image");

				imagecopy( $_tp_image, $_src_image, 0, 0, 0, 0, $_dimensions[0], $_dimensions[1] );
				imagesavealpha( $_tp_image, true );

				$image = $_tp_image;
				if ( $set ) $this->format = "png";

			}

			else if ( strtolower( $ext ? $ext : pathinfo( $i, PATHINFO_EXTENSION ) ) == "gif" ){
				$image = imagecreatefromstring( file_get_contents( $i ) );
				if ( !$image ) throw new Exception("Unable to create gif image");
				if ( $set ) $this->format = "gif";
			}

			else if ( in_array( strtolower( $ext ? $ext : pathinfo( $i, PATHINFO_EXTENSION ) ), [ "jpg", "jpeg" ] ) ){
				$image = imagecreatefromstring( file_get_contents( $i ) );
				if ( !$image ) throw new Exception("Unable to create jpg image");
				if ( $set ) $this->format = "jpg";
			}

			else
			return false;

		}
		elseif ( gettype( $i ) == "string" ? is_string( $i ) : false ){
			$image = imagecreatefromstring( $i );
			if ( $set ) $this->format = "png";
		}
		elseif( PHP_MAJOR_VERSION >= 8 ? gettype( $i ) == "object" && get_class( $i ) == "GdImage" : get_resource_type( $i ) == "gd" ){
			$image = $i;
			if ( $set ) $this->format = "png";
		}

		return empty( $image ) ? false : $image;

	}
	public function resize( $args ){

		$abs_width = null;
		$abs_height = null;
		$max_width = null;
		$max_height = null;
		$min_width = null;
		$min_height = null;
		$remove_src = false;
		extract( $args );

		if ( empty( $this->source ) ) return $this;

		if ( !empty( $abs_width )  ? $abs_width  > $this->width  : false ) return $this;
		if ( !empty( $abs_height ) ? $abs_height > $this->height : false ) return $this;

		if ( $abs_width && $abs_height ){

			$_nw = $abs_width;
			$_nh = $abs_height;

		}
		elseif ( $max_width && $max_height ) {

			// Should we make this image smaller?
			$_wr = $this->width  / $max_width;
			$_hr = $this->height / $max_height;
			$_r  = $_wr > $_hr ? $_wr : $_hr;
			if ( 1 > $_r ) return $this;

			// Make it small
			$_nw = round( $this->width / $_r );
			$_nh = round( $this->height / $_r );

			// Should we don't let this image get too small?
			if ( !empty( $min_width ) || !empty( $min_height ) ){

				// Is this image too small?
				$_wr = $_nw / $min_width;
				$_hr = $_nh / $min_height;
				$_r  = $_wr > $_hr ? $_hr : $_wr;

				// Make it bigger
				if ( 1 > $_r ){
					$_nw = round( $_nw / $_r );
					$_nh = round( $_nh / $_r );
				}

				// Too big? noway to edit this image
				if ( $_nw > $this->width || $_nh > $this->height )
				return $this;

			}

		}
		else {

			die("bad_args");

		}

		$_ni = imagecreatetruecolor( $_nw, $_nh );
		$_tp_color = imagecolorallocatealpha( $_ni, 0, 0, 0, 127 );
		imagecolortransparent( $_ni, $_tp_color );
		imagefill( $_ni, 0, 0, $_tp_color );
		imagealphablending( $_ni, false );
		imagesavealpha( $_ni, true );
		imagecopyresampled( $_ni, $this->source, 0, 0, 0, 0, $_nw, $_nh, $this->width, $this->height );
		imagesavealpha( $_ni, true );
		imagedestroy( $this->source );
		if ( $remove_src && $this->path ) unlink( $this->path );
		$this->change_source( $_ni );
		$this->width  = $_nw;
		$this->height = $_nh;
		$this->path   = null;

		return $this;

	}
	public function square( $args = [] ){

		$remove_src = false;
		extract( $args );

		$_s  = $this->width > $this->height ? $this->height : $this->width;
		$_ow = round( ( $this->width  - $_s ) / 2 );
		$_oh = round( ( $this->height - $_s ) / 2 );
		$_ni = imagecreatetruecolor( $_s, $_s );
		$_tp_color = imagecolorallocatealpha( $_ni, 0, 0, 0, 127 );
		imagecolortransparent( $_ni, $_tp_color );
		imagefill( $_ni, 0, 0, $_tp_color );
		imagecopyresampled( $_ni, $this->source, 0, 0, $_ow, $_oh, $this->width, $this->height, $this->width, $this->height );
		imagesavealpha( $_ni, true );
		imagedestroy( $this->source );

		if ( $remove_src && $this->path ) unlink( $this->path );

		$this->change_source( $_ni );
		$this->width  = $_s;
		$this->height = $_s;

		return $this;

	}
	public function style_wave(){

		$new_height = ( $this->height*.5 ) + ( $this->height*.5 *.5 );
		$new_image = imagecreatetruecolor( $this->width, $new_height );
		$transparent = imagecolorallocatealpha( $new_image, 255, 255, 255, 127 );
		imagealphablending( $new_image, false );
		imagesavealpha( $new_image, true );
		imagefilledrectangle( $new_image, 0, 0, $this->width, $new_height, $transparent );
		imagecopyresampled( $new_image, $this->source, 0, 0, 0, 0, $this->width, $this->height/2, $this->width, $this->height/2 );
		imagecopyresampled( $new_image, $this->source, 0, ($this->height*.5)+1, 0, $this->height*.5, $this->width, ($this->height*.5*.5), $this->width, $this->height*.5 );
		imagedestroy( $this->source );
		$this->change_source( $new_image );
		$this->width = $this->width;
		$this->height = $new_height;
		$this->path = null;

		return $this;

	}
	public function change_color( $new_color ){

		list( $r, $g, $b ) = sscanf( $new_color, "#%02x%02x%02x" );
		imagefilter( $this->source, IMG_FILTER_COLORIZE, $r, $g, $b );
		return $this;

	}
	public function get_dominant_color(){

		$fake_img = imagecreatetruecolor(1, 1);
		imagecopyresampled($fake_img, $this->source, 0, 0, 0, 0, 1, 1, $this->width, $this->height);
		$fake_img_color = imagecolorat($fake_img, 0, 0);
		$fake_img_color_red = ($fake_img_color >> 16) & 0xFF;
		$fake_img_color_green = ($fake_img_color >> 8) & 0xFF;
		$fake_img_color_blue = $fake_img_color & 0xFF;
		$fake_img_color_hex = dechex($fake_img_color);

		$this->unset();
		imagedestroy($fake_img);

		return array(
			"hex" => $fake_img_color_hex,
			"rgb" => "{$fake_img_color_red}, {$fake_img_color_green}, {$fake_img_color_blue}"
		);
	}
	public function secure(){

		// Create new image same as original image ( just to verify that this image is actually an image )
		$new_image = imagecreatetruecolor( $this->width, $this->height );

		if ( $this->format == "png" ){
			$_tp_color = imagecolorallocatealpha( $new_image, 0, 0, 0, 127 );
			imagecolortransparent( $new_image, $_tp_color );
			imagefill( $new_image, 0, 0, $_tp_color );
			imagecopy( $new_image, $this->source, 0, 0, 0, 0, $this->width, $this->height );
			imagesavealpha( $new_image, true );
		}
		else {
			imagecopyresampled( $new_image, $this->source, 0, 0, 0, 0, $this->width, $this->height, $this->width, $this->height );
		}

		if ( $this->format == "jpg" || $this->format == "jpeg" )
		imagejpeg( $new_image, $this->path );

		else if ( $this->format == "gif" )
		imagegif( $new_image, $this->path );

		else if ( $this->format == "png" )
		imagepng( $new_image, $this->path, 9 );

		imagedestroy( $this->source );
		$this->change_source( $new_image );

		return $this;

	}
	public function save( $args = [] ){

		$i = $this->source;

		$save_ext = null;
		$force_ext = null;
		$path = null;
		$png_q = 9;
		$jpg_q = 80;
		extract( $args );

		if ( ( !$force_ext || $force_ext == "jpg" ) && ( $save_ext === "jpg" || $this->format == "jpg" || $this->format == "jpeg" ) )
		$save = imagejpeg( $this->source, $path, $jpg_q );

		else if ( ( !$force_ext || $force_ext == "gif" ) && ( $save_ext === "gif" || $this->format == "gif" ) )
		$save = imagegif( $this->source, $path );

		else if (( !$force_ext || $force_ext == "png" ) && (  $save_ext === "png" || $this->format == "png" ) )
		$save = imagepng( $this->source, $path, $png_q );

		try {
			imagedestroy( $this->source );
		} catch( Exception|Warning $err ){}

		return $this;

	}
	public function html( $sources, $item=null ){

		$alt = "";
		if ( !empty( $item["name"] ) )
		$alt = $item["name"];

		$_raw = [];
		foreach( $sources as $link => $dims ){
			$images[ $dims[0] - ( bof()->general->endsWidth($link,".webp") ? 1 : 0 ) ] = $link;
			$_raw[ $dims[0] - ( bof()->general->endsWidth($link,".webp") ? 1 : 0 ) ] = $link;
		}
		krsort( $images );

		$smallest_image = array_pop( $images );
		if ( !empty( $images ) ){

			for( $images_in_row=1; $images_in_row<=12; $images_in_row++ ){
				foreach( $images as $_w => $_l ){
					$__l = explode( "?", $_l );
					$__l = reset( $__l );
					$__l = explode( ".", $__l );
					$__l = end( $__l );
					if ( !in_array( $__l, [ "jpeg", "gif", "png", "webp" ], true ) ) $__l = "jpeg";
					$___m = round( $_w * $images_in_row * ( bof()->request->is_mobile() ? 0.5 : 0.7 ) );
					$images_by_rows[ $images_in_row ][] = "<source srcset=\"{$_l}\" type=\"image/{$__l}\" media=\"(min-width: {$___m}px)\" >";
				}
			}

			foreach( $images_by_rows as &$images_by_row ){
				$images_by_row = array(
					"image" => "<img src=\"{$smallest_image}\" alt=\"{$alt}\" loading=\"lazy\" >",
					"sources" => $images_by_row
				);
				$images_by_row["html"] = "<picture>".implode("",$images_by_row["sources"]).$images_by_row["image"]."</picture>";
			}

		}
		else {
			for( $images_in_row=1; $images_in_row<=12; $images_in_row++ ){
				$images_by_rows[ $images_in_row ] = array(
					"image" => "<img src=\"{$smallest_image}\" alt=\"{$alt}\" loading=\"lazy\" >",
					"sources" => []
				);
				$images_by_rows[ $images_in_row ]["html"] = "<picture>". $images_by_rows[ $images_in_row ]["image"] ."</picture>";
			}

		}

		$images_by_rows["_raw"] = $images;

		return $images_by_rows;

	}

}

?>
