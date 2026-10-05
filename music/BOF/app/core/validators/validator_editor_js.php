<?php

if ( !defined( "root" ) ) die;
use \EditorJS\EditorJS;

function validator_editor_js( &$value, $args, $nest ){

	require_once( bof_root . "/app/core/third/editorjs-php/vendor/autoload.php" );

	$allowed_tags = "*";

	$editor_js_conf = json_encode( array(

		"tools" => array(
			"paragraph" => array(
				"text" => array(
					"type" => "string",
					"allowedTags" => $allowed_tags
				),
				"alignment" => array(
					"type" => "string",
					"canBeOnly" => [ "right", "left", "center", "justify", "inherit" ]
				)
			),
			"header" => array(
				"text" => array(
					"type" => "string",
					"required" => true,
					"allowedTags" => $allowed_tags
				),
				"level" => array(
					"type" => "int",
					"canBeOnly" => [ 1, 2, 3, 4, 5, 6 ]
				)
			),
			"list" => array(
				"items" => array(
					"type" => "array",
					"data" => array(
						"-" => array(
							"type" => "string",
							"allowedTags" => $allowed_tags
						)
					)
				),
				"style" => array(
					"type" => "string",
					"canBeOnly" => [ "ordered", "unordered" ]
				)
			),
			"checklist" => array(
				"items" => array(
					"type" => "array",
					"data" => array(
						"-" => array(
							"type" => "array",
							"data" => array(
								"text" => array(
									"type" => "string",
									"allowedTags" => $allowed_tags
								),
								"checked" => array(
									"type" => "boolean"
								)
							)
						)
					)
				),
			),
			"quote" => array(
				"alignment" => array(
					"type" => "string",
					"canBeOnly" => ["left", "center", "right" ]
				),
				"caption" => array(
					"type" => "string",
					"allowedTags" => $allowed_tags
				),
				"text" => array(
					"type" => "string",
					"allowedTags" => $allowed_tags
				),
			),
			"warning" => array(
				"title" => array(
					"type" => "string",
					"allowedTags" => $allowed_tags
				),
				"message" => array(
					"type" => "string",
					"allowedTags" => $allowed_tags
				),
			),
			"code" => array(
				"code" => array(
					"type" => "string",
					"allowedTags" => "b,i"
				),
			),
			"delimiter" => array(
			),
			"table" => array(
				"withHeadings" => array(
					"type" => "boolean"
				),
				"stretched" => array(
					"type" => "boolean"
				),
				"content" => array(
					"type" => "array",
					"data" => array(
						"-" => array(
							"type" => "array",
							"data" => array(
								"-" => array(
									"type" => "string",
									"allowedTags" => $allowed_tags
								)
							)
						)
					)
				)
			),
			"image" => array(
				"caption" => array(
					"type" => "string",
					"allowedTags" => $allowed_tags
				),
				"stretched" => array(
					"type" => "boolean"
				),
				"withBackground" => array(
					"type" => "boolean"
				),
				"withBorder" => array(
					"type" => "boolean"
				),
				"file" => array(
					"type" => "array",
					"data" => array(
						"id" => array(
							"type" => "int",
							"required" => true
						),
						"key" => array(
							"type" => "string",
							"required" => true
						),
						"url" => array(
							"type" => "string",
							"required" => true
						)
					)
				)
			),
			"AnyButton" => array(
				"text" => array(
					"type" => "string",
					"allowedTags" => $allowed_tags
				),
				"link" => array(
					"type" => "string"
				),
			),
			"video" => array(
				"caption" => array(
					"type" => "string",
					"allowedTags" => $allowed_tags
				),
				"stretched" => array(
					"type" => "boolean"
				),
				"withBackground" => array(
					"type" => "boolean"
				),
				"withBorder" => array(
					"type" => "boolean"
				),
				"file" => array(
					"type" => "array",
					"data" => array(
						"id" => array(
							"type" => "int",
							"required" => true
						),
						"key" => array(
							"type" => "string",
							"required" => true
						),
						"url" => array(
							"type" => "string",
							"required" => true
						)
					)
				)
			),
		)

	) );

	$editor_js_json = is_array( $value ) ? json_encode( $value ) : $value;

	try {

		$editor = new EditorJS( $editor_js_json, $editor_js_conf );
		$new_blocks = $editor->getBlocks();

		$value = json_decode( $editor_js_json, 1 );
		$value["blocks"] = $new_blocks;
		$value = json_encode( $value, JSON_UNESCAPED_UNICODE );

		$valueObject = json_decode( $value );
		$htmlContent = bof()->editorjs->htmlize( $valueObject );
		$value = json_decode( $value, 1 );
		$value["html"] = $htmlContent;
		$value = json_encode( $value, JSON_UNESCAPED_UNICODE );

		$validate = true;

	} catch ( \EditorJS\EditorJSException $e ) {

		$validate = false;
		$value = null;
	}

	return $validate;

}

?>
