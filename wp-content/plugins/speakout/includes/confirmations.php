<?php

// capture confirmation_code variable from links clicked in confirmation emails
if ( isset( $_REQUEST['dkspeakoutconfirm'] ) ) {
	add_action( 'template_redirect', 'dk_speakout_confirm_email' );
}

/**
 * Displays the confirmation page
 */
function dk_speakout_confirm_email() {

	// set WPML language
	global $sitepress;
	$lang = isset( $_REQUEST['lang'] ) ? $_REQUEST['lang'] : '';

	if ( isset( $sitepress ) ) {
		$sitepress->switch_lang( $lang, true );
	}

	include_once( 'class.signature.php' );
	include_once( 'class.petition.php' );
	include_once( 'class.mail.php' );
	include_once( 'class.wpml.php' );
	$the_signature = new dk_speakout_Signature();
	$the_petition  = new dk_speakout_Petition();
	$wpml          = new dk_speakout_WPML();

	// get the confirmation code from url
	$confirmation_code = $_REQUEST['dkspeakoutconfirm'];

	// try to confirm the signature
	$try_confirm = $the_signature->confirm( $confirmation_code );

	// if our attempt to confirm the signature was successful
	if ( $try_confirm ) {

		// retrieve the signature data
		$the_signature->retrieve_confirmed( $confirmation_code );

		// retrieve the petition data
		$the_petition->retrieve( $the_signature->petitions_id );
		$wpml->translate_petition( $the_petition );


		// send the petition email.  Querystring value is whether or not to BCC, called from confirmations.php
		if ( $the_petition->sends_email ) {
			dk_speakout_Mail::send_petition( $the_petition, $the_signature, $_GET["b"] );
		}

		// set up the status message
		$message = __( 'Thank you. Your signature has been added to the petition.', 'speakout' );
	}
	else {
		// has the signature already been confirmed?
		if ( $the_signature->check_confirmation( $confirmation_code ) ) {
			$message = __( 'Your signature has already been confirmed.', 'speakout' );
		}
		else {
			// the confirmation code is fubar or an admin has already deleted the signature
			$message = __( 'The confirmation code you provided is invalid.', 'speakout' );
		}
	}

	// display the confirmation page
	$confirmation_page = '
		<!doctype html>
		<html>
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=' . get_bloginfo( "charset" ) . '" />
			';
	//if we don't have a confirmation URL don't attempt o redirect
	if($the_petition->return_url > "" && $the_petition->return_url != 0){
		$confirmation_page .= 	'<meta http-equiv="refresh" content="2;' . $the_petition->return_url . '">';
	}
	
	$confirmation_page .= 	'<title>' . get_bloginfo( "name" ) . '</title>
			<style type="text/css">
				body {
					background: #666;
					font-family: arial, sans-serif;
					font-size: 14px;
				}
				#confirmation {
					background: #fff url(' . plugins_url( "speakout/images/mail-stripes.png" ) . ') repeat top left;
					border: 1px solid #fff;
					width: 515px;
					margin: 200px auto 0 auto;
					box-shadow: 0px 3px 5px #333;
				}
				#confirmation-content {
					background: #fff url(' . plugins_url( "speakout/images/postmark.png" ) . ') no-repeat top right;
					margin: 10px;
					padding: 40px 0 20px 100px;
				}
			</style>
		</head>
		<body>
			<div id="confirmation">
				<div id="confirmation-content">
					<h2>' . __( "Email Confirmation", "speakout" ) . '</h2>
					<p>' . $message . '</p>';
					
			if($the_petition->return_url > "" && $the_petition->return_url != 0){
				$confirmation_page .= 	'<p>' . __( "You will be redirected momentarily.", "speakout" ) . '</p>					
					<p><a href="' . home_url() . '">' . get_bloginfo( "name" ) . '  &raquo;</a></p>';
			}
			else{
				$confirmation_page .= 	'<p>' . '<a href="' . home_url() . '">' . __( "Click here") . '</a> ' . __("to return to site ", "speakout" )  . '</p>';
			}			
					
				
				$confirmation_page .= 	'</div>
			</div>
		</body>
		</html>
	';

	echo $confirmation_page;

	// stop page rendering here
	// without this, the home page will display below the confirmation message
	die();
}

?>