<?php
add_action( 'pre_post_update', 'addiction_recovery_save_post', 10, 3 );

// Custom function to send updated post meta title/description/focus keyphrase values back to the AR Web Portal.
function addiction_recovery_save_post( $post_ID, $post ) {

	if ( isset( $_POST['updating_remote_endpoint'] ) ) {
		return $post;
	}

	$main_user    = get_option( 'addiction_recovery_main_user' );
	$author_id    = $post['post_author'];

	if ( $main_user == $author_id ) {
		// do nothing, fixes weird bug with != statement.
	} else {
		return $post;
	}

	// If both SEO plugins are disabled
	if ( ! addiction_recovery_check_yoast_enabled() && ! addiction_recovery_check_aio_enabled() ) {
		return $post;
	}

	if ( addiction_recovery_check_aio_enabled() ) {
		// The new set of data
		$aioseo   = json_decode( stripcslashes( $_POST['aioseo-post-settings'] ), true );
		$new_data = array(
			"aioseo_title"    => sanitize_text_field( $aioseo['title'] ),
			"aioseo_metadesc" => sanitize_text_field( $aioseo['description'] ),
			"aioseo_focuskw"  => sanitize_text_field( $aioseo['keyphrases']['focus']['keyphrase'] ),
		);

		// The old set of data
		$aioseo_fields = addiction_recovery_get_aioseo_fields( $post_ID );
		$old_data      = array(
			"aioseo_title"    => sanitize_text_field( $aioseo_fields['title'] ),
			"aioseo_metadesc" => sanitize_text_field( $aioseo_fields['description'] ),
			"aioseo_focuskw"  => sanitize_text_field( $aioseo_fields['keyphrase'] ),
		);

		$params = array(
			"meta_title"           => sanitize_text_field( $aioseo['title'] ),
			"meta_description"     => sanitize_text_field( $aioseo['description'] ),
			"meta_focus_keyphrase" => sanitize_text_field( $aioseo['keyphrases']['focus']['keyphrase'] ),
		);
	}

	if ( addiction_recovery_check_yoast_enabled() ) {
		// The new set of data
		$new_data = array(
			"yoast_wpseo_title"    => sanitize_text_field( $_POST['yoast_wpseo_title'] ),
			"yoast_wpseo_metadesc" => sanitize_text_field( $_POST['yoast_wpseo_metadesc'] ),
			"yoast_wpseo_focuskw"  => sanitize_text_field( $_POST['yoast_wpseo_focuskw'] ),
		);

		// The old set of data
		$old_data = array(
			"yoast_wpseo_title"    => get_post_meta( $post_ID, '_yoast_wpseo_title', true ),
			"yoast_wpseo_metadesc" => get_post_meta( $post_ID, '_yoast_wpseo_metadesc', true ),
			"yoast_wpseo_focuskw"  => get_post_meta( $post_ID, '_yoast_wpseo_focuskw', true ),
		);

		$params = array(
			"meta_title"           => sanitize_text_field( $_POST['yoast_wpseo_title'] ),
			"meta_description"     => sanitize_text_field( $_POST['yoast_wpseo_metadesc'] ),
			"meta_focus_keyphrase" => sanitize_text_field( $_POST['yoast_wpseo_focuskw'] ),
		);
	}

	// Let's check any changes
	$changed = false;
	foreach ( $new_data as $key => $value ) {
		if ( $value != $old_data[ $key ] ) {
			$changed = true;
			break;
		}
	}

	// If the Yoast/All In One SEO values have changed, send updated values back to AR Web Portal.
	if ( $changed ) {

		$params['post_id'] = $post_ID;

		$url   = get_option( 'addiction_recovery_web_portal_endpoint' );
		$token = get_option( 'addiction_recovery_web_portal_token' );

		addiction_recovery_send_request( $url,  $params, $token );

	}

	return $post;
}


// Hook for title/description changes
add_action( 'post_updated', 'addiction_recovery_check_post_changes', 10, 30 ); // Let's check the priority!
function addiction_recovery_check_post_changes( $post_ID, $post_after, $post_before ) {

	$changed = false;

	$title_old = $post_before->post_title;
	$title_new = $post_after->post_title;

	$content_old = $post_before->post_content;
	$content_new = $post_after->post_content;

	global $wpdb;

	$query = "SELECT * FROM $wpdb->posts WHERE `ID`='$post_ID'";
	$posts = $wpdb->get_results( $query );
	$post  = $posts[0];

	if ( $title_old != $title_new || $content_old != $content_new) {
		$changed = true;
	}

	$params['post_id'] = $post_ID;

	$url   = get_option( 'addiction_recovery_web_portal_endpoint' );
	$token = get_option( 'addiction_recovery_web_portal_token' );

	// Let's get the formatting back
	$content_new = wpautop( $post_after->post_content );

	$params['title']       = $title_new;
	$params['description'] = $content_new;

	addiction_recovery_send_request( $url, $params, $token );

	return true;
}


add_action( 'init', 'addiction_recovery_auto_login', 1 );
function addiction_recovery_auto_login() {

	/*
	1. Request to the endpoint to get a login url
	2. Generate a token for the user from settings
	3. If token equal to the user token then authorize and clear the token field
	*/

	// Example: site-url/wp-login.php?user=1&token=gggttt

	$user_id = $_GET['user'] ?? '';
	$token   = $_GET['token'] ?? '';

	if ( $user_id && $token != '' ) {
		addiction_recovery_login_user( $user_id, $token );
	}
}



