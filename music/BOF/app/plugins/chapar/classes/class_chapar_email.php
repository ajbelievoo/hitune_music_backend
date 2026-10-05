<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

if ( !defined( "root" ) ) die;

class chapar_email extends bof_type_class {

  public function exe( $args ){

    $target_email = null;
    $target_user_id = null;
    $message_title = null;
    $message_content = null;
    $message_image = null;
    $no_unsub_link = false;
    extract( $args );
    $unsublink = false;

    // gen unsubscribe link if there is a target user, option is not disabled by force && admin has enabled unsubscribe links
    if ( ( $no_unsub_link === true || !$target_user_id ) ? false : bof()->object->db_setting->get( "ma_unsub_link" ) ){

      for( $i=1; $i<=3; $i++ )
      $keys[$i] = md5(uniqid().microtime(true).rand(1,666));

      bof()->db->_insert(array(
        "table" => "_bof_cache_unsubscribe_links",
        "set" => array(
          [ "key1", $keys[1] ],
          [ "key2", $keys[2] ],
          [ "key3", $keys[3] ],
          [ "user_id", $target_user_id ]
        )
      ));

      $unsublink = web_address . "/api/email_unsubscribe?email={$target_email}&key1={$keys[1]}&keys2={$keys[2]}&keys3={$keys[3]}";

    }

    $email_content = $this->_bof_this->parse_template( $message_content, $unsublink );

    $this->_bof_this->send( $target_email, $message_title, $email_content );

  }

  protected function make_unsub_link(){}

  public function parse_template( $content, $unsublink ){

    $logoID = bof()->object->db_setting->get( "logo" );
    if ( $logoID ){
      $logo = bof()->object->file->select( [ "ID" => $logoID ] );
      if ( $logo ) $logoAddress = $logo["image_original"];
    }

    $template_html_raw = $this->_bof_this->get_template();

    if ( !$unsublink ){
      if ( preg_match( "/%!UNSUB%(.*?)%UNSUB!%/s", $template_html_raw, $m ) ){
        $template_html_raw = str_replace( $m[0], "", $template_html_raw );
      }
    } else {
      $template_html_raw = str_replace( [ "%!UNSUB%", "%UNSUB!%" ], "", $template_html_raw );
    }

    $template_vars = array(
      "color" => bof()->object->db_setting->get("theme_color_rgb"),
      "website_address" => web_address,
      "website_name" => bof()->object->db_setting->get("sitename"),
      "logo_address" => $logoAddress,
      "email_content" => htmlspecialchars_decode( $content, ENT_QUOTES ),
      "dont_talk_back" => bof()->object->language->turn( "email_dont_talk" ),
      "unsub_title" => bof()->object->language->turn( "email_unsubscribe", [], [ "uc_first" => true ]  ),
      "unsub_link" => $unsublink,
    );

    foreach( $template_vars as $_k => $_v ){
      $template_html_raw = str_replace( "%".strtoupper($_k)."%", strval( $_v ), $template_html_raw );
    }

    return $template_html_raw;

  }
  public function get_template(){

    $theme = file_get_contents( chapar_plugin_root . "/templates/default.html" );
    return $theme;

  }

	public function send( $to, $_subject, $_content, $from=null ){

		$email_s_type    = bof()->object->db_setting->get( "ma_server", "localhost" );
		$email_s_host    = bof()->object->db_setting->get( "ma_s_addr", null );
		$email_s_port    = bof()->object->db_setting->get( "ma_s_port", null );
		$email_s_user    = bof()->object->db_setting->get( "ms_s_username", null );
		$email_s_pass    = bof()->object->db_setting->get( "ma_s_password", null );
		$email_s_encrypt = bof()->object->db_setting->get( "ma_s_encrypt", null );
    $email_from      = bof()->object->db_setting->get( "ma_from", null );
		$sitename        = bof()->object->db_setting->get( "sitename" );

    if ( $from )
    $_from = $from;
    elseif ( $email_from )
    $_from = $email_from;
    else
		$_from = "noreply@" . str_replace( "www.", "", parse_url( web_address, PHP_URL_HOST ) );

		if ( $email_s_type == "localhost" || empty( $email_s_host ) || empty( $email_s_port ) ){

			$headers  = "From: {$sitename} <{$_from}>\r\n";
			$headers .= "Content-Type: text/html; charset=UTF-8";
      bof()->general->set_full_fall(true);
			try {
				$_s = mail( $to, $_subject, $_content, $headers );
				$sent = true;
			}
			catch( Exception $err ){

        if ( bof()->chapar->get_debug() )
        throw new Exception( $err->getMessage() );

				$sent = false;

			}

		}
		else {

      require_once( chapar_plugin_root . "/third/phpmailer_6.9.3/autoload.php" );
			$mail = new PHPMailer( true );
			try {

				//Server settings
				$mail->isSMTP();
				$mail->Host       = $email_s_host;
				$mail->Port       = $email_s_port;

        if ( $email_s_user && $email_s_pass ){
          $mail->SMTPAuth = true;
          $mail->Username = $email_s_user;
          $mail->Password = $email_s_pass;  
        } 
        else {
          $mail->SMTPAuth = false;
        }

        if ( $email_s_encrypt == "none" ){
          $mail->SMTPAutoTLS = false;
          $mail->SMTPSecure = false;
        } else {
          $mail->SMTPSecure = $email_s_encrypt == "tls" ? PHPMailer::ENCRYPTION_STARTTLS : "ssl";
          $mail->SMTPOptions = array(
            'ssl' => array(
              'verify_peer' => false,
              'verify_peer_name' => false,
              'allow_self_signed' => true
            )
          );
        }

				//Recipients
				$mail->setFrom( $_from , $sitename );
				$mail->addAddress( $to );

				// Content
				$mail->isHTML( true );
				$mail->Subject = $_subject;
				$mail->Body    = $_content;

				// Send
				$mail->send();
				$sent = true;

			} catch (phpmailerException $e) {

				$error = $e->errorMessage();

        if ( bof()->chapar->get_debug() )
        throw new Exception( $error );

			} catch (Exception $e) {

				$error = $e->getMessage();

        if ( bof()->chapar->get_debug() )
        throw new Exception( $error );

			}

			if ( !empty( $sent ) && empty( $error ) )
			return true;

			return !empty( $error ) ? $error : false;

		}

	}
	public function test_smtp(){

		$tester_user = $this->loader->visitor->user()->data;
		$email_s_host = $this->loader->admin->get_setting( "email_s_host", null );
		$email_s_port = $this->loader->admin->get_setting( "email_s_port", null );

		if ( !$email_s_host || !$email_s_port ) return "Invalid SMTP setting";

		$test = $this->send( $tester_user["email"], "Testing SMTP", "SMTP Works!" );

		return $test === true ? true : ( $test === false ? "Failed. Unkown reason" : $test );

	}

}

?>
