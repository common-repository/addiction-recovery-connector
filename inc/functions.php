<?php

// Return values stored in All In One SEO fields.	
function addiction_recovery_get_aioseo_fields( $post_id ) {
	global $wpdb;
	$table = $wpdb->prefix . 'aioseo_posts';

	// Update the fields
	$query      = "SELECT * FROM $table WHERE `post_id`=$post_id";
	$res        = $wpdb->get_results( $query );
	$data       = $res[0];
	$keyphrases = json_decode( $data->keyphrases );

	$result = array(
		"title"       => $data->title,
		"description" => $data->description,
		"keyphrase"   => $keyphrases->focus->keyphrase,
	);

	return $result;
}

// Send request to AR Web Portal.
function addiction_recovery_send_request( $url,  $params, $token = false ) {

	if ( ! $token ) {
		return false;
	}

	$args = [
		'method'  => 'POST',
		"timeout" => 500,
		'headers' => [
			'Authorization' => 'Bearer ' . $token,
			"Content-type"  => "application/json",
			"Accept"        => "application/json"
		],
		'body'    => json_encode( $params )
	];

	$res  = wp_remote_post( $url, $args );
	
	$body = [];

	if( is_array( $res ) ) {
		$body = isset( $res['body'] ) ? $res['body'] : false;
	}

	return $body;

}

// Check if Yoast SEO plugin is enabled.
function addiction_recovery_check_yoast_enabled() {
	$plugins = get_option( 'active_plugins' );
	if ( in_array( 'wordpress-seo/wp-seo.php', $plugins ) ) {
		return true;
	}
}

// Check if All In One SEO plugin is enabled.
function addiction_recovery_check_aio_enabled() {
	$plugins = get_option( 'active_plugins' );
	if ( in_array( 'all-in-one-seo-pack/all_in_one_seo_pack.php', $plugins ) ) {
		return true;
	}
}


// Log in user by token
function addiction_recovery_login_user( $user_id, $token ) {
	if ( $GLOBALS['pagenow'] === 'wp-login.php' ) {
		$user = get_user_by( 'id', $user_id );

		if ( $user ) {
			$user_active_token = get_user_meta( $user_id, 'one_time_token', true );

			// Remove the token
			if ( $user_active_token == $token ) {
				update_user_meta( $user_id, 'one_time_token', '' );
				wp_set_auth_cookie( $user_id );
				wp_redirect( '/wp-admin/' );
				exit();
			}
		}
	}
}