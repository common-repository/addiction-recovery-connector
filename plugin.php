<?php
/*
Plugin Name: Addiction Recovery Connector
Description: A simple plugin to integrate your website with the Addiction Recovery content and publication portal.
Version: 1.1.5
Author: Addiction Recovery
Author URI: https://content.addictionrecovery.com
Requires at least: 3.5
*/

$plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);

define('ADDICTION_RECOVERY_VERSION_PLUGIN', $plugin_data['Version']);
define('ADDICTION_RECOVERY_PLUGIN_DIR_PATH', __FILE__);

// Admin area
include('inc/admin.php');

// Routes
include('custom_routes/class.main.php');

// Hooks
include('inc/hooks.php');

// Functions
include('inc/functions.php');

//Instagram feed
include('inc/creator-instagram.php');

//Anchors Creator
include('inc/creator-anchors.php');

//Related posts list Creator
include('inc/creator-related.php');

//Reviewer Creator
include('inc/creator-reviewer.php');

//Token validation endpoint
include('inc/token-validation-endpoint.php');

function modify_html_output() {
	ob_start();
	ob_implicit_flush( 0 );
	?>
		<?php
		$html = ob_get_clean();

		echo apply_filters( 'final_output', $html );
}
//wp
add_action( 'template_redirect', 'modify_html_output' );
