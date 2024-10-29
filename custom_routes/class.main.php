<?php

/**
 * The main controller which handles our API functionality.
 */
class addition_recovery_controller {

	// Here initialize our namespace and resource name.
	public function __construct() {
		$this->namespace = 'custom_posts/v1';
	}

	// Register our routes.
	public function register_routes() {

		// Create/update posts
		register_rest_route( $this->namespace, '/create/', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'create_custom_posts' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );

		// Get users list
		register_rest_route( $this->namespace, '/get_users/', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_users_list' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );

		// Get categories list
		register_rest_route( $this->namespace, '/get_categories/', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_categories_list' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );

		// Get posts by search query
		register_rest_route( $this->namespace, '/get_posts/', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_posts_list' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );

		// Get posts by search query
		register_rest_route( $this->namespace, '/get_onetime_login/', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_onetime_login' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );

		// Get posts by search query
		register_rest_route( $this->namespace, '/get_terms/', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_all_terms' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );
		
		// Get seo key phrases
		register_rest_route($this->namespace, '/get-seo-keyphrases', array(
			'methods'  => 'GET',
			'callback' => array($this, 'get_seo_key_phrases_endpoint'),
			'permission_callback' => array($this, 'check_permissions'),
		));

		// Verify token values
		register_rest_route( $this->namespace, '/verify_token/', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'verify_tokens' ),
			'permission_callback' => array( $this, 'check_permissions' ),
		) );
	}

	public function get_all_terms($request) {

		// Get Data from Request
		$data = $request->get_params();

		$terms = get_terms( 'post_tag', array(
			'hide_empty' => false,
		) );

		$filtered_terms = [];
		if(!empty($terms)) {
			foreach($terms as $term) {
				$filtered_terms[] = [
					'id' => $term->term_id,
					'name' => $term->name
				];
			}
		}

		return $filtered_terms;
	}

	/*
	 * Verify Tokens by Client Request
	 */
	public function verify_tokens( $request ) {

		// Get Data from Request
		$data = $request->get_params();

		if ( isset( $data['api_token'] ) && isset( $data['portal_token'] ) ) {

			// Get Settings
			$api_token    = get_option( 'addiction_recovery_custom_api_token' );
			$portal_token = get_option( 'addiction_recovery_web_portal_token' );

			// Check if Tokens if equal
			if ( $data['api_token'] == $api_token && $data['portal_token'] == $portal_token ) {
				$response = [
					'status'  => 'true',
					'version'  => ADDICTION_RECOVERY_VERSION_PLUGIN,
					'instagram' => !empty(get_option('addiction_recovery_instagram_feed_enabled')) ? true : false,
					'message' => 'API Token and Portal Token is equal!'
				];
			} else {
				$response = [
					'status'  => 'false',
					'message' => 'API Token and Portal Token is not equal'
				];
			}
		} else {
			$response = [
				'status'  => 'false',
				'message' => 'Required fields is not set'
			];
		}

		return $response;
	}

	// Verify the bearer token is found in POST request headers.
	public function check_permissions( $request ) {

		// Get Data from Request
		$data = $request->get_params();

		// Headers
		$headers    = $request->get_headers();
		$user_agent = $headers['x_pantheon_client_ip'][0];

		// Bearer token
		$auth  = $headers['authorization'][0];
		$exp   = explode( 'Bearer ', $auth );
		$token = $exp[1];

		if ( get_option( 'addiction_recovery_custom_api_token' ) != $token && !isset($data['api_token']) || isset($data['api_token']) && $data['api_token'] != get_option( 'addiction_recovery_custom_api_token' )) {
			return false;
		}

		return true;
	}

	// Handle error messages
	public function return_error( $code, $message, $status ) {
		$data = array(
			"code"    => $code,
			"message" => $message,
			"data"    => array( "status" => $status )
		);

		$response = new WP_REST_Response( $data );

		return $response;
	}

	// Handle post creation
	public function create_custom_posts( $request ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' ); // For front-end part, to check if plugins are active or not

		$_POST['updating_remote_endpoint'] = 'Y';

		// Request params
		$params = $request->get_params();

		// List of posts
		$items = $params['items'];

		$result = array();

		if ( ! is_array( $items ) || count( $items ) == 0 ) {
			return $this->return_error( 'count_posts', 'An array of items is empty', '401' );
		}

		if ( ! in_array( $params['type'], array( "post", "page" ) ) ) {
			return $this->return_error( 'post_type', 'Unknown post type', '401' );
		}

		// Inserting the data
		foreach ( $items as $key => $item ) {
			// Statuses: publish, draft, future
			if ( $item['title'] == '' ) {
				return $this->return_error( "post_title", "Post title can't be empty", '401' );
			}

			if ( ! in_array( $item['post_status'], array( "publish", "draft", "future" ) ) ) {
				return $this->return_error( "post_status", "Unknown post status", '401' );
			}

			// Sanitizing categories
			foreach ( $item['categories'] as $key_cat => $cat ) {
				$item['categories'][ $key_cat ] = sanitize_text_field( $cat );
			}

			// Sanitizing tags
			foreach ( $item['tags'] as $key_tag => $tag ) {
				$item['tags'][ $key_tag ] = sanitize_text_field( $tag );
			}

			$post_data = array(
				'post_type'     => sanitize_text_field( $params['type'] ),
				'post_status'   => sanitize_text_field( $item['post_status'] ),
				'post_title'    => sanitize_text_field( $item['title'] ),
				'post_content'  => wp_kses_post( $item['body'] ),
				'post_author'   => sanitize_text_field( $item['author_id'] ),
				'post_category' => $item['categories'],
				'tags_input'    => $item['tags'],
			);

			// If post exists then update it
			if ( $item['ID'] && get_post( $item['ID'] ) ) {
				$post_data['ID'] = sanitize_text_field( $item['ID'] );
			}

			// Scheduled
			if ( $item['post_status'] == 'future' ) {
				$post_data['post_date'] = sanitize_text_field( $item['post_date'] );
			}

			// Post creation/update
			$post_id               = wp_insert_post( $post_data );
			$params['post_status'] = (string) $params['post_status'];

			// Publish live
			if ( $item['post_status'] == 'publish' ) {
				wp_publish_post( $post_id );
			}

			$items[ $key ]['ID'] = $post_id;
			$post_info           = get_post( $post_id );

			$result[] = array(
				"ID"          => $post_id,
				"permalink"   => get_permalink( $post_id ),
				"post_date"   => $post_info->post_date,
				"post_status" => sanitize_text_field( $item['post_status'] ),
			);

			// Image
			if ( $item['image'] ) {
				$image_url = $item['image'];

				// Image alt/title/description
				$seo = array(
					"alt"         => sanitize_text_field( $item['image_meta_alt'] ),
					"title"       => sanitize_text_field( $item['image_meta_title'] ),
					"description" => sanitize_text_field( $item['image_meta_description'] ),
				);

				$this->upload_post_image( $post_id, $image_url, $seo );
			}

			// Handle setting Yoast meta title/description/focus keyphrase.
			if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
				if ( $item['meta_title'] ) {
					update_post_meta( $post_id, '_yoast_wpseo_title', sanitize_text_field( $item['meta_title'] ) );
				}
				if ( $item['meta_description'] ) {
					update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_text_field( $item['meta_description'] ) );
				}
				if ( $item['meta_keyphrase'] ) {
					update_post_meta( $post_id, '_yoast_wpseo_focuskw', sanitize_text_field( $item['meta_keyphrase'] ) );
				}
			}

			// All in One SEO
			if ( is_plugin_active( 'all-in-one-seo-pack/all_in_one_seo_pack.php' ) ) {
				$keyphrase["focus"] = array(
					"keyphrase" => sanitize_text_field( $item['meta_keyphrase'] ),
					"score"     => "0",
					"analysis"  => array(),
				);

				$fields = array(
					"title"       => sanitize_text_field( $item['meta_title'] ),
					"description" => sanitize_text_field( $item['meta_description'] ),
					"keyphrases"  => json_encode( $keyphrase ),
				);

				$this->update_aioseo_fields( $post_id, $fields );
			}

			// Rank Math SEO
			if ( is_plugin_active( 'seo-by-rank-math/rank-math.php' ) ) {
				$fields = array(
					"title"       => sanitize_text_field( $item['meta_title'] ),
					"description" => sanitize_text_field( $item['meta_description'] ),
					"keyphrases"  => sanitize_text_field( $item['meta_keyphrase'] ),
				);

				$this->update_rank_seo_math_fields( $post_id, $fields );
			}
		}

		return rest_ensure_response( $result );
	}

	// Handle saving All In One SEO values directly to database.
	public function update_aioseo_fields( $post_id, $fields ) {
		global $wpdb;
		$table = $wpdb->prefix . 'aioseo_posts';
		// Let's check if exists
		$query = "SELECT * FROM $table WHERE `post_id`=$post_id";
		$res   = $wpdb->get_results( $query )[0];

		if ( ! $res ) {
			$wpdb->insert( $table, [ 'post_id' => $post_id ] );
		}

		// Update the fields
		$query = "UPDATE $table SET `title`='" . $fields['title'] . "' WHERE `post_id`=$post_id";
		$wpdb->query( $query );

		$wpdb->update( $table,
			$fields,
			[ 'post_id' => $post_id ]
		);
	}

	// Handle saving Rank Math SEO values directly to database.
	public function update_rank_seo_math_fields( $post_id, $fields ) {
		// Meta fields: rank_math_seo_score, rank_math_title, rank_math_description, rank_math_focus_keyword
		update_post_meta( $post_id, 'rank_math_title', $fields['title'] );
		update_post_meta( $post_id, 'rank_math_description', $fields['description'] );
		update_post_meta( $post_id, 'rank_math_focus_keyword', $fields['keyphrases'] );

		return true;
	}

	// Return list of users with ID/login/email/display name.
	public function get_users_list() {
		$users_raw = get_users();
		foreach ( $users_raw as $item ) {
			// Just supply the user from settings
			if ( get_option( 'addiction_recovery_main_user' ) != $item->ID ) {
				continue;
			}

			$users[] = array(
				"ID"         => $item->ID,
				"user_login" => $item->user_login,
				"user_email" => $item->user_email,
				"user_name"  => $item->display_name,
			);
		}

		return $users;
	}

	// Return list of categories.
	function get_categories_list() {
		$args     = array(
			'parent'     => 0,
			'hide_empty' => 0,
			'order'      => 'ASC',
		);
		$cats_raw = get_categories( $args );

		foreach ( $cats_raw as $item ) {
			$categories[] = array(
				"ID"    => $item->term_id,
				"title" => $item->name,
			);
		}

		return $categories;
	}
	
	/**
	 * Get key phrases from the existed posts.
	 *
	 * @return array
	 */
	public function get_seo_key_phrases_endpoint():array
	{
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		$plugins = array('all-in-one-seo-pack/all_in_one_seo_pack.php', 'rank-math/rank-math.php', 'wordpress-seo/wp-seo.php');
		$keyphrases = array();

		foreach ($plugins as $plugin) {
			if (is_plugin_active($plugin)) {
				$args = array(
					'post_type'      => array('post', 'page'),
					'posts_per_page' => -1,
				);

				$query = new WP_Query($args);

				if ($query->have_posts()) {
					while ($query->have_posts()) {
						$query->the_post();
						$focus_keyphrases = get_post_meta(get_the_ID(), '_yoast_wpseo_focuskw', true);
						$focus_keyphrases .=  ' ' . get_post_meta(get_the_ID(), '_aioseop_keywords', true);
						$focus_keyphrases .= ' ' . get_post_meta(get_the_ID(), '_rank_math_focus_keyword', true);

						$focus_keyphrases = array_unique(explode(" ", trim($focus_keyphrases)));
						$focus_keyphrases = implode(" ", $focus_keyphrases);

						if (!empty($focus_keyphrases)) {
							// Split keyphrases into an array
							$keyphrases_array = explode(',', $focus_keyphrases);

							// Loop through the keyphrases
							foreach ($keyphrases_array as $keyphrase) {
								$keyphrase = trim($keyphrase);

								if (
									!empty($keyphrase) && !isset($keyphrases[$keyphrase])
								) {
									$keyphrases[$keyphrase] = get_the_title();
								}
							}
						}
					}
					wp_reset_postdata();
				}
				break;
			}
		}
		return $keyphrases;
	}

	// Return list of posts by search query.
	function get_posts_list( $request ) {
		global $wpdb;

		$list          = array();
		$params        = $request->get_params();
		$search_string = $params['s'];
		$limit         = $params['limit'];
		$table         = $wpdb->posts;
		$query         = "SELECT * FROM $table WHERE `post_content` LIKE '%$search_string%' OR `post_title` LIKE '%$search_string%' ";

		if ( $limit != '' ) {
			$query .= " LIMIT $limit";
		}

		$results    = $wpdb->get_results( $query );
		$post_types = array( 'page', 'post' );

		foreach ( $results as $item ) {
			if ( ! in_array( $item->post_type, $post_types ) ) {
				continue;
			}

			$list[] = array(
				"title" => $item->post_title,
				"url"   => get_permalink( $item->ID ),
			);
		}

		if ( trim( $search_string ) == '' ) {
			$list = array();
		}

		return rest_ensure_response( $list );
	}

	// Return list of posts by search query.
	function get_posts_full_list( $request ) {
		$posts = get_posts( array(
			'numberposts' => - 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'post_type'   => array( 'post', 'page' ),
		) );

		foreach ( $posts as $item ) {
			$list[] = array(
				"title" => $item->post_title,
				"url"   => get_permalink( $item->ID ),
			);
		}

		return rest_ensure_response( $list );
	}

	// Handle uploading the featured image.
	public function upload_post_image( $post_id, $image_url, $meta ) {
		// The front-end needs these files
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$url  = $image_url;
		$desc = "";

		// Loading the file
		$tmp = download_url( $url );

		// Set of file's data
		$file_array = [
			'name'     => basename( $url ),
			'tmp_name' => $tmp,
			'error'    => 0,
			'size'     => filesize( $tmp ),
		];

		// Loading the image
		$attachment_id = media_handle_sideload( $file_array, $post_id, $desc );

		// Attachment meta
		$this->update_attachment_meta( $attachment_id, $meta );

		// Stick the attachment to the Post
		set_post_thumbnail( $post_id, $attachment_id );

		// If some error
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $file_array['tmp_name'] );
			echo $attachment_id->get_error_messages();
		}
	}

	/**
	 * Meta data: alt, title, description
	 * Alt: _wp_attachment_image_alt, post meta
	 * Title, description, caption: post_title, post_content, post_excerpt
	 */
	public function update_attachment_meta( $post_id, $params ) {
		global $wpdb;

		$wpdb->update( $wpdb->posts,
			[ 'post_title' => $params['title'], 'post_content' => $params['description'] ],
			[ 'ID' => $post_id ]
		);

		update_post_meta( $post_id, '_wp_attachment_image_alt', $params['alt'] );
	}

	public function get_onetime_login( $request ) {
		$params    = $request->get_params();
		$main_user = get_option( 'addiction_recovery_main_user' );
		$token     = $this->gen_token();

		update_user_meta( $main_user, 'one_time_token', $token );

		$url_login = get_site_url() . "/wp-login.php?user=$main_user&token=$token";

		$data = array(
			"login_url" => $url_login
		);

		return rest_ensure_response( $data );
	}

	public function gen_token() {
		$bytes = openssl_random_pseudo_bytes( 20, $cstrong );

		return bin2hex( $bytes );
	}
}

// Function to register our new routes from the controller.
function addiction_recovery_register_my_rest_routes() {
	$controller = new addition_recovery_controller();
	$controller->register_routes();
}

add_action( 'rest_api_init', 'addiction_recovery_register_my_rest_routes' );