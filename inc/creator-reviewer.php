<?php

/**
 * Review Creator.
 */
class Reviewer_Creator {
	public function __construct() {
		if ( get_option( 'addiction_recovery_reviews_enabled' ) ) {
			add_action( 'init', array( $this, 'add_new_cpt' ) );
			add_action( 'add_meta_boxes_ar_reviewer_h', array( $this, 'add_meta_boxes_to_cpt' ) );
			add_action( 'save_post_ar_reviewer_h', array( $this, 'save_meta_boxes_to_cpt' ), 10, 2 );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes_to_post' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes_to_page' ) );
			add_action( 'save_post', array( $this, 'save_meta_boxes_to_post' ), 10, 2 );
			add_action( 'save_post_page', array( $this, 'save_meta_boxes_to_post' ), 10, 2 );
		}

		add_filter( 'the_content', array( $this, 'add_reviewers_to_post' ) );
		add_action( 'wp_head', array( $this, 'add_styles_to_the_header' ) );
		add_action( 'template_redirect', array( $this, 'page_for_reviewer' ) );
		add_filter( 'single_template', array( $this, 'load_reviewer_template' ) );
		add_filter( 'post_type_link', array( $this, 'remove_slug_for_custom_post_type' ), 10, 3 );
		add_action( 'pre_get_posts', array( $this, 'set_query_params_on_request' ) );
		add_action( 'show_user_profile', array( $this, 'true_show_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'true_show_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'true_save_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'true_save_profile_fields' ) );
		add_action( 'wp_head', array( $this, 'additional_info_author' ) );
		add_shortcode( 'ar_author_information', array( $this, 'show_reviewer_information' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 999 );
		add_action( 'wp_footer', array( $this, 'enqueue_output_html' ) );
		add_action( 'ar_show_reviewer_list', array( $this, 'show_reviewers_page' ) );
		add_action( 'ar_show_authors_list', array( $this, 'show_authors_page' ) );
		add_action( 'ar_display_page_header', array( $this, 'display_header' ) );
		add_action( 'ar_display_page_footer', array( $this, 'display_footer' ) );
	}

	/**
	 * @return void
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		if ( ( ! empty( get_option( 'addiction_recovery_reviews_enabled' ) ) && is_singular() ) || $this->isGeneralPage() ) {
			if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
				wp_enqueue_script( 'addiction-recovery-js-instagram-jquery', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/jquery-2.1.3.min.js', [], [], true );
			}
			if ( ! wp_script_is( 'addiction-recovery-js-instagram-lazy', 'enqueued' ) ) {
				wp_enqueue_script( 'addiction-recovery-js-instagram-lazy', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/jquery.lazyload.min.js', [], [], true );
				wp_enqueue_script( 'addiction-recovery-js-instagram-lazy-loader', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/image-loader.js', [], [], true );
			}
			wp_enqueue_style( 'ar-creator-reviewer-styles', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/creator-reviewer-styles.css', [], [] );
		}
	}

	/**
	 * @param $args
	 * @return string
	 * Show reviewer's information.
	 */
	function show_reviewer_information( $args ): string {
		$author = get_queried_object();
		$author_id = $author->ID;
		$name = get_user_meta( $author_id, 'ar_name', 1 );
		$title = get_user_meta( $author_id, 'ar_title', 1 );
		$description = get_user_meta( $author_id, 'ar_description', 1 );
		$credentials = get_user_meta( $author_id, 'ar_credentials', 1 );
		$twitter = get_user_meta( $author_id, 'ar_twitter', 1 );
		$facebook = get_user_meta( $author_id, 'ar_facebook', 1 );
		$instagram = get_user_meta( $author_id, 'ar_instagram', 1 );
		$linkedin = get_user_meta( $author_id, 'ar_linkedin', 1 );
		$enable_ar_connector = get_user_meta( $author_id, 'enable_ar_connector', 1 );
		$image = get_avatar_url( $author_id );

		$html_out = '';
		if ( isset( $enable_ar_connector ) && ! empty( $enable_ar_connector ) ) {
			$html_out .= "<div class=\"ar-reviewers-wrapper\">
            <div class=\"ar-reviewer-page\"><img class=\"ar-reviewer-image--lg\" src=\"$image\" alt=\"Author Profile Image\"><h1 class=\"ar-reviewer-name\">$name</h1><h2 class=\"ar-reviewer-title\">$title</h2><p class=\"ar-reviewer-description\">$description</p><label class=\"ar-reviewer-credentials--label\">Credentials:</label><p class=\"ar-reviewer-credentials\">$credentials</p><div class=\"ar-reviewer-social\">";

			if ( ! empty( $twitter ) ) {
				$html_out .= "<a href=\"$twitter\" class=\"ar-reviewer-social-link\"><i class=\"fab fa-twitter\"></i></a>";
			}
			if ( ! empty( $facebook ) ) {
				$html_out .= "<a href=\"$facebook\" class=\"ar-reviewer-social-link\"><i class=\"fab fa-facebook\"></i></a>";
			}
			if ( ! empty( $instagram ) ) {
				$html_out .= "<a href=\"$instagram\" class=\"ar-reviewer-social-link\"><i class=\"fab fa-instagram\"></i></a>";
			}
			if ( ! empty( $linkedin ) ) {
				$html_out .= "<a href=\"$linkedin\" class=\"ar-reviewer-social-link\"><i class=\"fab fa-linkedin\"></i></a>";
			}
			$html_out .= "</div></div></div>";
		}
		return $html_out;
	}

	/**
	 * Enqueue output html.
	 *
	 * @return void
	 */
	public function enqueue_output_html(): void {
		global $post;
		if ( ! isset( $post ) ) {
			echo '';
		}
		if ( get_option( 'addiction_recovery_reviews_enabled' ) && ! is_admin() && ( get_post_type( $post ) === 'page' || get_post_type( $post ) === 'post' ) ) {

			global $post;

			$post_permalink = get_permalink( $post->ID );
			$post_title = $post->post_title;
			$post_date_published = get_the_date( 'Y-m-d\TH:i:s', $post->ID );
			$post_date_modified = get_the_modified_date( 'Y-m-d\TH:i:s', $post->ID );
			$site_url = home_url();
			$post_meta_reviewer_id = get_post_meta( $post->ID, 'ar_reviewer', 1 );
			$post_meta_reviewer_id = json_decode( $post_meta_reviewer_id );

			$post_meta_reviewer_id = $post_meta_reviewer_id[0] ?? 0;
			$reviewer_name = get_post_meta( $post_meta_reviewer_id, 'name', 1 );
			$reviewer_description = get_post_meta( $post_meta_reviewer_id, 'bio', 1 );
			$reviewer_title = get_post_meta( $post_meta_reviewer_id, 'title', 1 );
			$image = get_the_post_thumbnail_url( $post_meta_reviewer_id, 'full' );
			$link = get_permalink( $post_meta_reviewer_id );

			$twitter = get_post_meta( $post_meta_reviewer_id, 'twitter', 1 );
			$facebook = get_post_meta( $post_meta_reviewer_id, 'facebook', 1 );
			$instagram = get_post_meta( $post_meta_reviewer_id, 'instagram', 1 );
			$linkedin = get_post_meta( $post_meta_reviewer_id, 'linkedin', 1 );

			$links_social = array();

			$links_social[] = "\"" . $site_url . "\"";

			if ( $twitter ) {
				$links_social[] = "\"" . $twitter . "\"";
			}
			if ( $facebook ) {
				$links_social[] = "\"" . $facebook . "\"";
			}
			if ( $instagram ) {
				$links_social[] = "\"" . $instagram . "\"";
			}
			if ( $linkedin ) {
				$links_social[] = "\"" . $linkedin . "\"";
			}

			$links_social = '[' . implode( ', ', $links_social ) . ']';

			$schema = '<script type="application/ld+json">{ "@context": "https://schema.org", "@graph": [{ "@type": "WebPage", "@id": "' . $post_permalink . '", "url": "' . $post_permalink . '", "name": "' . $post_title . '", "isPartOf": { "@id": "' . $site_url . '" }, "datePublished": "' . $post_date_published . '", "dateModified": "' . $post_date_modified . '", "inLanguage": "en-US", "reviewedBy": [ { "@type": "Person", "@id": "' . $link . '", "name": "' . $reviewer_name . '", "image": "' . $image . '", "jobTitle": "' . $reviewer_title . '", "memberOf": "' . $site_url . '", "sameAs": ' . $links_social . ' } ] } ] }</script></body>';

			$html = $schema;

			echo $html;
		}
		if ( get_option( 'addiction_recovery_reviews_enabled' ) && ! is_admin() && get_post_type( $post ) === 'ar_reviewer_h' ) {

			global $post;

			$site_url = home_url();
			$post_meta_reviewer_id = $post->ID;
			$reviewer_link = get_permalink( $post_meta_reviewer_id );
			$reviewer_name = get_post_meta( $post_meta_reviewer_id, 'name', 1 );
			$reviewer_description = get_post_meta( $post_meta_reviewer_id, 'bio', 1 );
			$reviewer_title = get_post_meta( $post_meta_reviewer_id, 'title', 1 );
			$image = get_the_post_thumbnail_url( $post_meta_reviewer_id, 'full' );

			$twitter = get_post_meta( $post_meta_reviewer_id, 'twitter', 1 );
			$facebook = get_post_meta( $post_meta_reviewer_id, 'facebook', 1 );
			$instagram = get_post_meta( $post_meta_reviewer_id, 'instagram', 1 );
			$linkedin = get_post_meta( $post_meta_reviewer_id, 'linkedin', 1 );

			$links_social = array();

			$links_social[] = "\"" . $site_url . "\"";

			if ( $twitter ) {
				$links_social[] = "\"" . $twitter . "\"";
			}
			if ( $facebook ) {
				$links_social[] = "\"" . $facebook . "\"";
			}
			if ( $instagram ) {
				$links_social[] = "\"" . $instagram . "\"";
			}
			if ( $linkedin ) {
				$links_social[] = "\"" . $linkedin . "\"";
			}

			$links_social = '[' . implode( ', ', $links_social ) . ']';

			$schema = '<script type="application/ld+json">{"@context": "https://schema.org","@graph": [{"@type": "Person","@id": "' . $reviewer_link . '","name": "' . $reviewer_name . '","url": "' . $reviewer_link . '","image": {"@type": "ImageObject","@id": "' . $image . '","url": "' . $image . '","caption": "' . $reviewer_name . '","inLanguage": "en-US"},"sameAs": ' . $links_social . ',"worksFor": {"@id": "' . $site_url . '"}}]}</script></body>';

			$html = $schema;

			if ( ! empty( get_option( 'addiction_historical_review_date' ) ) ) {
				if ( strtotime( $post->post_date ) > strtotime( get_option( 'addiction_historical_review_date' ) ) ) {
					echo $html;
				}
			} else {
				echo $html;
			}
		}

		if ( get_option( 'addiction_recovery_basic_markup' ) ) {
			global $post;
			if ( isset( $post ) && ! is_null( $post ) ) {
				if ( $post->post_type == 'post' || $post->post_type == 'page' ) {

					$post_thumbnail_id = get_post_thumbnail_id( $post->ID );
					$attachment = false;
					$width = false;
					$height = false;
					$alt = false;

					if ( $post_thumbnail_id ) {
						$attachment = wp_get_attachment_image_src( $post_thumbnail_id );
						$width = $attachment[1];
						$height = $attachment[2];
						$alt = get_post_meta( $post_thumbnail_id, '_wp_attachment_image_alt', true );
					}

					$type = 'WebPage';

					if ( $post->post_type == 'post' ) {
						$type = 'Article';
					}

					$schema = '<script type="application/ld+json">{"@context": "https://schema.org","@graph":[{"@type":"' . $type . '","@id":"' . get_permalink( $post->ID ) . '","url":"' . get_permalink( $post->ID ) . '","name":"' . $post->post_title . '","isPartOf":{"@id": "' . home_url() . '/#website"},';

					if ( $post->post_type == 'page' )
						$schema .= '"primaryImageOfPage":{"@id": "' . get_permalink( $post->ID ) . '/#primaryimage"},"image": {"@id": "' . get_permalink( $post->ID ) . '/#primaryimage"},';

					if ( $attachment )
						$schema .= '"thumbnailUrl": "' . get_the_post_thumbnail_url( $post->ID, 'full' ) . '",';

					if ( $post->post_type == 'page' )
						$breadcrumb = '"breadcrumb":{"@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","position":1,"item":{"@id":"' . get_permalink( $post->ID ) . '","name":"' . $post->post_title . '"}}]},';

					$schema .= '"datePublished": "' . get_the_date( 'Y-m-d', $post->ID ) . '","dateModified":"' . get_the_modified_date( 'Y-m-d', $post->ID ) . '","author":{"@id": "' . get_author_posts_url( $post->post_author ) . '"},"description":"' . $post->post_excerpt . '",' . $breadcrumb . '"inLanguage":"en-US","potentialAction":[{"@type":"ReadAction","target":["' . get_permalink( $post->ID ) . '"]}]},';

					if ( $attachment )
						$schema .= '{"@type":"ImageObject","inLanguage":"en-US","@id":"' . get_permalink( $post->ID ) . '/#primaryimage","url":"' . get_the_post_thumbnail_url( $post->ID, 'full' ) . '","contentUrl":"' . get_the_post_thumbnail_url( $post->ID, 'full' ) . '","width":' . $width . ',"height":' . $height . ',"caption":"' . $alt . '"},';

					$schema .= '{"@type":"WebSite","@id":"' . home_url() . '/#website","url":"' . home_url() . '","name":"' . get_bloginfo( 'name' ) . '","description":"' . $post->post_excerpt . '","inLanguage":"en-US"},{"@type":"Person","@id":"' . get_author_posts_url( $post->post_author ) . '","name":"' . get_the_author_meta( 'display_name', $post->post_author ) . '","image":{"@type": "ImageObject","inLanguage":"en-US","@id":"' . get_avatar_url( $post->post_author ) . '","url":"' . get_avatar_url( $post->post_author ) . '","contentUrl":"' . get_avatar_url( $post->post_author ) . '","caption":"' . get_the_author_meta( 'display_name', $post->post_author ) . '"},"sameAs":["' . home_url() . '"],"url": "' . get_author_posts_url( $post->post_author ) . '"}]}</script></body>';

					$html = $schema;
					echo $html;
				}
			}
		}
	}

	public function additional_info_author() {
		if ( is_author() ) {
			/**
			 * Show reviewer's information on the author's page.
			 */
			add_filter( 'final_output', function ($html) {
				$html_out = '</header>';
				$author = get_queried_object();
				$author_id = $author->ID;
				$name = get_user_meta( $author_id, 'ar_name', 1 );
				$title = get_user_meta( $author_id, 'ar_title', 1 );
				$description = get_user_meta( $author_id, 'ar_description', 1 );
				$credentials = get_user_meta( $author_id, 'ar_credentials', 1 );
				$twitter = get_user_meta( $author_id, 'ar_twitter', 1 );
				$facebook = get_user_meta( $author_id, 'ar_facebook', 1 );
				$instagram = get_user_meta( $author_id, 'ar_instagram', 1 );
				$linkedin = get_user_meta( $author_id, 'ar_linkedin', 1 );
				$enable_ar_connector = get_user_meta( $author_id, 'enable_ar_connector', 1 );
				$image = get_avatar_url( $author_id );

				if ( isset( $enable_ar_connector ) && ! empty( $enable_ar_connector ) ) {
					$html_out .= "<div class=\"ar-reviewers-wrapper\"><div class=\"ar-reviewer-page\"><img class=\"ar-reviewer-image--lg\" src=\"$image\" alt=\"Author Profile Image\"><h1 class=\"ar-reviewer-name\">$name</h1><h2 class=\"ar-reviewer-title\">$title</h2><p class=\"ar-reviewer-description\">$description</p><label class=\"ar-reviewer-credentials--label\">Credentials:</label><p class=\"ar-reviewer-credentials\">$credentials</p><div class=\"ar-reviewer-social\">";

					if ( ! empty( $twitter ) ) {
						$html_out .= "<a href=\"$twitter\" class=\"ar-reviewer-social-link\"><i class=\"fab fa-twitter\"></i></a>";
					}
					if ( ! empty( $facebook ) ) {
						$html_out .= "<a href=\"$facebook\" class=\"ar-reviewer-social-link\"><i class=\"fab fa-facebook\"></i></a>";
					}
					if ( ! empty( $instagram ) ) {
						$html_out .= "<a href=\"$instagram\" class=\"ar-reviewer-social-link\"><i class=\"fab fa-instagram\"></i></a>";
					}
					if ( ! empty( $linkedin ) ) {
						$html_out .= "<a href=\"$linkedin\" class=\"ar-reviewer-social-link\"><i class=\"fab fa-linkedin\"></i></a>";
					}
					$html_out .= "</div></div></div>";
				}

				return preg_replace( '/<\/header>/', $html_out, $html, 1 );
			} );

			add_filter( 'get_the_archive_title', function ($title) {
				if ( is_author() ) {
					$author = get_queried_object();
					$author_id = $author->ID;
					$enable_ar_connector = get_user_meta( $author_id, 'enable_ar_connector', 1 );
					if ( isset( $enable_ar_connector ) && ! empty( $enable_ar_connector ) ) {
						$title = '';
					}
				}

				return $title;
			} );
		}
	}

	/**
	 * Add fields to the user form.
	 *
	 * @param object $user
	 * @return void
	 */
	public function true_show_profile_fields( $user ): void {
		echo '<h3>Addiction recovery settings</h3>';
		echo '<table class="form-table">';
		$enable_ar_connector = get_user_meta( $user->ID, 'enable_ar_connector', 1 );
		$checked = '';
		if ( isset( $enable_ar_connector ) ) {
			if ( ! empty( $enable_ar_connector ) ) {
				$checked = 'checked';
			}
		}
		echo '<tr><th><label for="enable_ar_connector">Include in AR Connector</label></th>
        <td><input type="checkbox" ' . $checked . ' name="enable_ar_connector" id="enable_ar_connector" row="5" class="regular-text" value="1"></td>
        </tr>';
		$title = get_user_meta( $user->ID, 'ar_title', 1 );
		echo '<tr><th><label for="ar_title">Title</label></th>
        <td><input name="ar_title" id="ar_title" row="5" class="regular-text" value="' . $title . '"></td>
        </tr>';
		$name = get_user_meta( $user->ID, 'ar_name', 1 );
		echo '<tr><th><label for="ar_name">Name</label></th>
        <td><input name="ar_name" id="ar_name" row="5" class="regular-text" value="' . $name . '"></td>
        </tr>';
		$description = get_user_meta( $user->ID, 'ar_description', 1 );
		echo '<tr><th><label for="ar_description">Description</label></th>
        <td><textarea name="ar_description" row="5" id="ar_description" class="regular-text">' . esc_attr( $description ) . '</textarea></td>
        </tr>';
		$credentials = get_user_meta( $user->ID, 'ar_credentials', 1 );
		echo '<tr><th><label for="ar_credentials">Credentials</label></th>
        <td><textarea name="ar_credentials" row="5" id="ar_credentials" class="regular-text">' . esc_attr( $credentials ) . '</textarea></td>
        </tr>';
		$twitter = get_user_meta( $user->ID, 'ar_twitter', 1 );
		echo '<tr><th><label for="ar_twitter">Twitter</label></th>
        <td><input name="ar_twitter" id="ar_twitter" row="5" class="regular-text" value="' . $twitter . '"></td>
        </tr>';
		$facebook = get_user_meta( $user->ID, 'ar_facebook', 1 );
		echo '<tr><th><label for="ar_facebook">Facebook</label></th>
        <td><input name="ar_facebook" id="ar_facebook" row="5" class="regular-text" value="' . $facebook . '"></td>
        </tr>';
		$instagram = get_user_meta( $user->ID, 'ar_instagram', 1 );
		echo '<tr><th><label for="ar_instagram">Instagram</label></th>
        <td><input name="ar_instagram" id="ar_instagram" row="5" class="regular-text" value="' . $instagram . '"></td>
        </tr>';
		$linkedin = get_user_meta( $user->ID, 'ar_linkedin', 1 );
		echo '<tr><th><label for="ar_linkedin">Linkedin</label></th>
        <td><input name="ar_linkedin" id="ar_linkedin" row="5" class="regular-text" value="' . $linkedin . '"></td>
        </tr>';
		echo '</table>';
	}

	/**
	 * Update additional user's info.
	 *
	 * @param integer $user_id
	 * @return void
	 */
	public function true_save_profile_fields( int $user_id ) {
		update_user_meta( $user_id, 'ar_name', sanitize_text_field( $_POST['ar_name'] ) );
		update_user_meta( $user_id, 'ar_title', sanitize_text_field( $_POST['ar_title'] ) );
		update_user_meta( $user_id, 'ar_description', sanitize_text_field( $_POST['ar_description'] ) );
		update_user_meta( $user_id, 'ar_credentials', sanitize_text_field( $_POST['ar_credentials'] ) );
		update_user_meta( $user_id, 'ar_twitter', sanitize_text_field( $_POST['ar_twitter'] ) );
		update_user_meta( $user_id, 'ar_facebook', sanitize_text_field( $_POST['ar_facebook'] ) );
		update_user_meta( $user_id, 'ar_instagram', sanitize_text_field( $_POST['ar_instagram'] ) );
		update_user_meta( $user_id, 'ar_linkedin', sanitize_text_field( $_POST['ar_linkedin'] ) );
		if ( $_POST['enable_ar_connector'] ) {
			update_user_meta( $user_id, 'enable_ar_connector', 1 );
		} else {
			update_user_meta( $user_id, 'enable_ar_connector', null );
		}
	}

	/**
	 * Remove a slug from link of the CPT.
	 *
	 * @param string $post_link
	 * @param object $post
	 * @param string $leavename
	 * @return void
	 */
	public function remove_slug_for_custom_post_type( string $post_link, object $post, string $leavename ): string {

		if ( 'ar_reviewer_h' != $post->post_type || 'publish' != $post->post_status ) {
			return $post_link;
		}

		$post_link = str_replace( '/' . $post->post_type . '/', '/', $post_link );

		return $post_link;
	}

	/**
	 * Set query params for correct redirect of the single template.
	 *
	 * @param object $query
	 * @return mixed
	 */
	public function set_query_params_on_request( $query ) {
		if ( ! $query->is_main_query() || 2 != count( $query->query ) || ! isset( $query->query['page'] ) ) {
			return;
		}

		if ( ! empty( $query->query['name'] ) ) {
			$query->set( 'post_type', array( 'post', 'ar_reviewer_h', 'page' ) );
		}
	}

	/**
	 * Add styles to the header.
	 *
	 * @return void
	 */
	public function add_styles_to_the_header(): void {
		if ( ( ! empty( get_option( 'addiction_recovery_reviews_enabled' ) ) && is_singular() ) || $this->isGeneralPage() ) {
			?>
							<link rel="stylesheet" href="<?php echo plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/ar-font-awesome/style.css'; ?>">
						<?php
		}
	}

	/**
	 * Add a CPT.
	 *
	 * @return void
	 */
	public function add_new_cpt(): void {
		$slug = '.';

		if ( get_option( 'addiction_recovery_reviewers_slug' ) ) {
			$slug = get_option( 'addiction_recovery_reviewers_slug' );
		}

		register_post_type( 'ar_reviewer_h', array(
			'label' => null,
			'labels' => array(
				'name' => 'Reviewers',
				'singular_name' => 'Reviewer',
				'add_new' => 'Add Reviewer',
				'add_new_item' => 'Add a Reviewer',
				'edit_item' => 'Edit Reviewer',
				'new_item' => 'A new Reviewer',
				'view_item' => 'See Reviewer',
				'search_items' => 'Search Reviewer',
				'not_found' => 'Not found',
				'not_found_in_trash' => 'Not found in the trash',
				'parent_item_colon' => '',
				'menu_name' => 'Reviewer',
			),
			'description' => '',
			'public' => true,
			'show_in_menu' => null,
			'menu_position' => 21,
			'menu_icon' => 'dashicons-visibility',
			'hierarchical' => false,
			'supports' => array( 'title', 'editor', 'custom-fields', 'thumbnail' ),
			'has_archive' => true,
			'query_var' => true,
			'rewrite' => array(
				'slug' => $slug,
				'with_front' => false
			)
		) );
	}

	/**
	 * Return all custom fields for CPT.
	 *
	 * @return array
	 */
	public function get_all_custom_fields_for_cpt(): array {
		$fields = array(
			"name" => array(
				"name" => "Name",
				"type" => "input",
			),
			"title" => array(
				"name" => "Title",
				"type" => "input",
			),
			"bio" => array(
				"name" => "Bio",
				"type" => "textfield",
			),
			"credentials" => array(
				"name" => "Credentials",
				"type" => "textfield",
			),
			"twitter" => array(
				"name" => "Twitter link",
				"type" => "input",
			),
			"facebook" => array(
				"name" => "Facebook link",
				"type" => "input",
			),
			"instagram" => array(
				"name" => "Instagram link",
				"type" => "input",
			),
			"linkedin" => array(
				"name" => "Linkedin link",
				"type" => "input",
			),
		);
		return $fields;
	}

	/**
	 * Custom boxes.
	 *
	 * @return void
	 */
	public function add_meta_boxes_to_cpt(): void {
		add_meta_box( 'meta-general-info', __( "General information", 'textdomain' ), function () {
			global $post;
			foreach ( $this->get_all_custom_fields_for_cpt() as $key => $item ) {
				wp_nonce_field( basename( __FILE__ ), $key );
				if ( $item['type'] === 'textfield' ) {
					echo '<div style="margin-bottom:30px;width:100%;display:flex;flex-direction:column;gap:10px;"><label for="' . $key . '"><strong>' . __( $item['name'], 'textdomain' ) . '</strong></label><textarea style="height:100px;" id="' . $key . '" name="' . $key . '">' . get_post_meta( $post->ID, $key, true ) . '</textarea></div>';
				} elseif ( $item['type'] === 'input' ) {
					echo '<div style="margin-bottom:30px;width:100%;display:flex;flex-direction:column;gap:10px;"><label for="' . $key . '"><strong>' . __( $item['name'], 'textdomain' ) . '</strong></label><input id="' . $key . '" name="' . $key . '" value="' . get_post_meta( $post->ID, $key, true ) . '"></div>';
				}
			}
		}, 'ar_reviewer_h', 'normal', 'low' );
	}

	/**
	 * Custom boxes for a post.
	 *
	 * @return void
	 */
	public function add_meta_boxes_to_post(): void {
		add_meta_box( 'meta-info', __( "Reviewers", 'textdomain' ), function () {
			global $post;

			$reviewers_query = new WP_Query(
				array(
					'post_type' => 'ar_reviewer_h',
					'post_status' => 'publish',
					'posts_per_page' => -1
				)
			);
			$html = '';
			if ( ! empty( $reviewers_query->posts ) ) {
				$options = '';
				foreach ( $reviewers_query->posts as $post_item ) {
					$selected = '';
					$reviewers = json_decode( get_post_meta( $post->ID, 'ar_reviewer', 1 ) );

					if ( $reviewers != null && in_array( $post_item->ID, $reviewers ) ) {
						$selected = 'selected';
					}

					$options .= "<option {$selected} value=\"{$post_item->ID}\">{$post_item->post_title}</option>";
				}

				$html = '<div><select style="width:100%;height:150px;" multiple id="ar_reviewer" name="ar_reviewer[]">';
				$html .= $options;
				$html .= '</select></div>';
			} else {
				$html = '<div><select style="width:100%;height:150px;" id="ar_reviewer" name="ar_reviewer"><option>Create a reviewer</option></select></div>';
			}

			echo $html;
		}, 'post', 'side', 'low', null );
	}

	/**
	 * Custom boxes for a post.
	 *
	 * @return void
	 */
	public function add_meta_boxes_to_page(): void {
		add_meta_box( 'meta-info', __( "Reviewers", 'textdomain' ), function () {
			global $post;

			$reviewers_query = new WP_Query(
				array(
					'post_type' => 'ar_reviewer_h',
					'post_status' => 'publish',
					'posts_per_page' => -1
				)
			);
			$html = '';
			if ( ! empty( $reviewers_query->posts ) ) {
				$options = '';
				foreach ( $reviewers_query->posts as $post_item ) {
					$selected = '';
					$reviewers = json_decode( get_post_meta( $post->ID, 'ar_reviewer', 1 ) );

					if ( $reviewers != null && in_array( $post_item->ID, $reviewers ) ) {
						$selected = 'selected';
					}

					$options .= "<option {$selected} value=\"{$post_item->ID}\">{$post_item->post_title}</option>";
				}

				$html = '<div><select style="width:100%;height:150px;" multiple id="ar_reviewer" name="ar_reviewer[]">';
				$html .= $options;
				$html .= '</select></div>';
			} else {
				$html = '<div><select style="width:100%;height:150px;" id="ar_reviewer" name="ar_reviewer"><option>Create a reviewer</option></select></div>';
			}

			echo $html;
		}, 'page', 'side', 'low', null );
	}

	/**
	 * Saves meta boxes to CPT.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function save_meta_boxes_to_cpt( int $post_id ): void {
		$all_meta = array_merge( $this->get_all_custom_fields_for_cpt() );
		foreach ( $all_meta as $key => $item ) {
			// check for correct user capabilities - stop internal xss from customers
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// update fields
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
			}
			if ( isset( $_POST['_description_title'] ) ) {
				update_post_meta( $post_id, '_description_title', sanitize_text_field( $_POST['_description_title'] ) );
			}
			if ( isset( $_POST['_wpmb_url'] ) ) {
				update_post_meta( $post_id, '_wpmb_url', sanitize_text_field( $_POST['_wpmb_url'] ) );
			}
		}
	}

	/**
	 * Saves meta boxes to post.
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function save_meta_boxes_to_post( int $post_id ): void {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// update fields
		if ( isset( $_POST['ar_reviewer'] ) ) {
			if ( ! empty( $_POST['ar_reviewer'] ) ) {
				$reviewers = array();
				foreach ( $_POST['ar_reviewer'] as $reviewer ) {
					$reviewers[] = $reviewer;
				}
				update_post_meta( $post_id, 'ar_reviewer', json_encode( $reviewers ) );
			}
		}
	}

	/**
	 * Check if it is a general page.
	 *
	 * @return boolean
	 */
	private function isGeneralPage(): bool {
		$slugs = explode( '/', $_SERVER['REQUEST_URI'] );
		$slug = explode( '/', $_SERVER['REQUEST_URI'] )[1];

		if ( isset( $slugs[2] ) ) {
			if ( empty( $slugs[2] ) ) {
				unset( $slugs[2] );
			}
		}

		if ( ! empty( get_option( 'addiction_recovery_reviewers_slug' ) ) && ! empty( get_option( 'addiction_recovery_reviews_enabled' ) ) && ! is_admin() && ! isset( $slugs[2] ) ) {
			if ( $slug == get_option( 'addiction_recovery_reviewers_slug' ) ) {
				return true;
			}

			if ( $slug == 'people' ) {
				return true;
			}
		}

		if ( ! empty( get_option( 'addiction_recovery_authors_slug' ) ) && ! empty( get_option( 'addiction_recovery_authors_enabled' ) ) && ! is_admin() && ! isset( $slugs[2] ) ) {
			if ( $slug == get_option( 'addiction_recovery_authors_slug' ) ) {
				return true;
			}

			if ( $slug == 'authors' ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return a page for reviewers.
	 *
	 * @return void
	 */
	public function page_for_reviewer(): void {
		$slugs = explode( '/', $_SERVER['REQUEST_URI'] );
		$slug = explode( '/', $_SERVER['REQUEST_URI'] )[1];

		if ( isset( $slugs[2] ) ) {
			if ( empty( $slugs[2] ) ) {
				unset( $slugs[2] );
			}
		}

		if ( ! empty( get_option( 'addiction_recovery_reviewers_slug' ) ) && ! empty( get_option( 'addiction_recovery_reviews_enabled' ) ) && ! is_admin() && ! isset( $slugs[2] ) ) {
			if ( $slug == get_option( 'addiction_recovery_reviewers_slug' ) ) {
				$this->change_title( 'People' );
				$this->display_custom_template();
				exit;
			}

			if ( $slug == 'people' ) {
				$this->change_title( 'People' );
				$this->display_custom_template();
				exit;
			}
		} elseif ( empty( get_option( 'addiction_recovery_reviewers_slug' ) ) && ! empty( get_option( 'addiction_recovery_reviews_enabled' ) ) && ! is_admin() && ! isset( $slugs[2] ) ) {
			if ( $slug == get_option( 'addiction_recovery_reviewers_slug' ) || $slug == 'people' ) {
				wp_redirect( home_url( '/' ), 301 );
				exit;
			}
		}
		if ( ! empty( get_option( 'addiction_recovery_authors_slug' ) ) && ! empty( get_option( 'addiction_recovery_authors_enabled' ) ) && ! is_admin() && ! isset( $slugs[2] ) ) {
			if ( $slug == get_option( 'addiction_recovery_authors_slug' ) ) {
				$this->change_title( 'Author' );
				$this->display_custom_template_authors();
				exit;
			}

			if ( $slug == 'authors' ) {
				$this->change_title( 'Author' );
				$this->display_custom_template_authors();
				exit;
			}
		} elseif ( empty( get_option( 'addiction_recovery_authors_slug' ) ) && ! empty( get_option( 'addiction_recovery_authors_enabled' ) ) && ! is_admin() && ! isset( $slugs[2] ) ) {
			if ( $slug == get_option( 'addiction_recovery_authors_slug' ) || $slug == 'authors' ) {
				wp_redirect( home_url( '/' ), 301 );
				exit;
			}
		}
	}

	public function show_authors_page(): void {
		http_response_code( 200 );

		$authors = get_users( array(
			'orderby' => 'display_name',
			'meta_query' => array(
				array(
					'key' => 'enable_ar_connector',
					'value' => '1',
					'compare' => '='
				)
			)
		) );

		$html = '<h1 class="ar-reviewers-list-main-heading">Authors</h1><div class="ar-reviewers-list-feed-wrapper">';

		if ( ! empty( $authors ) ) {
			foreach ( $authors as $author ) {
				$html .= $this->get_markup_card_for_list_authors( $author );
			}
		}

		$html .= '</div>';

		echo $html;
	}


	/**
	 * Display a header of the page.
	 *
	 * @return void
	 */
	public function display_header() {
		if ( wp_is_block_theme() ) {
			echo '<!DOCTYPE html><html <?php language_attributes(); ?><head><meta charset="UTF-8"><meta http-equiv="X-UA-Compatible" content="IE=edge"><meta name="viewport" content="width=device-width, initial-scale=1.0">' . wp_head() . '</head>';
			block_template_part( 'header' );
		} else {
			get_header();
		}
	}

	/**
	 * Display a footer of the page.
	 *
	 * @return void
	 */
	public function display_footer() {
		if ( wp_is_block_theme() ) {
			block_template_part( 'footer' );
			echo '<footer>' . wp_footer() . '</footer><script src="' . plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/jquery-2.1.3.min.js' . '"></script><script src="' . plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/jquery.lazyload.min.js' . '"></script></html>';
		} else {
			get_footer();
		}
	}

	/**
	 * Return a template path of the custom page.
	 *
	 * @return string
	 */
	private function get_template_theme_path(): string {
		$template_dir = get_stylesheet_directory() . '/ar/';
		$template_path = $template_dir . 'reviewers-template.php';

		return $template_path;
	}

	/**
	 * Return a template path of the custom authors page.
	 *
	 * @return string
	 */
	private function get_template_theme_path_authors(): string {
		$template_dir = get_stylesheet_directory() . '/ar/';
		$template_path = $template_dir . 'authors-template.php';

		return $template_path;
	}

	/**
	 * Display a template.
	 *
	 * @return void
	 */
	private function display_custom_template() {
		http_response_code( 200 );
		$template_path = $this->get_template_theme_path();

		if ( file_exists( $template_path ) ) {
			include( $template_path );
		} else {
			include( plugin_dir_path( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'templates/reviewers-template.php' );
		}
	}

	/**
	 * Display a template.
	 *
	 * @return void
	 */
	private function display_custom_template_authors() {
		http_response_code( 200 );
		$template_path = $this->get_template_theme_path_authors();

		if ( file_exists( $template_path ) ) {
			include( $template_path );
		} else {
			include( plugin_dir_path( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'templates/authors-template.php' );
		}
	}



	/**
	 * Displays a page with the feed.
	 *
	 * @return void
	 */
	public function show_reviewers_page(): void {
		$reviewers = new WP_Query( array(
			'post_type' => 'ar_reviewer_h',
			'post_status' => 'publish',
			'posts_per_page' => -1
		) );

		$html = '  <h1 class="ar-reviewers-list-main-heading">Reviewers</h1><div class="ar-reviewers-list-feed-wrapper" style="">';

		if ( ! empty( $reviewers->posts ) ) {
			foreach ( $reviewers->posts as $reviewer ) {
				$html .= $this->get_markup_card_for_list( $reviewer );
			}
		}

		$html .= '</div>';

		echo $html;
	}

	/**
	 * Change a title for the page.
	 *
	 * @param string $title
	 * @return void
	 */
	private function change_title( string $title ) {
		add_filter( 'pre_get_document_title', function ($data) use ($title) {
			return $title;
		} );
	}

	/**
	 * Add a block with reviewers to the post.
	 *
	 * @param string $content
	 * @return string
	 */
	public function add_reviewers_to_post( string $content ): string {
		global $post;
		$html = '';

		$reviewers = json_decode( get_post_meta( $post->ID, 'ar_reviewer', 1 ) );

		if ( ! empty( $reviewers ) && ! empty( get_option( 'addiction_recovery_reviews_enabled' ) ) ) {
			$html .= '<div class="ar-reviewers-wrapper"><div class="ar-reviewer-card"><div class="ar-reviewer-label"><p>Reviewed by:</p></div>';
			foreach ( $reviewers as $reviewer ) {
				$html .= $this->get_reviewer_card_markup( $reviewer );
			}
			$html .= '</div></div>';
		} elseif ( empty( $reviewers ) && ! empty( get_option( 'addiction_recovery_reviews_enabled' ) ) && ! empty( get_option( 'addiction_recovery_reviews_default_author' ) ) ) {
			$html .= '<div class="ar-reviewers-wrapper"><div class="ar-reviewer-card"><div class="ar-reviewer-label"><p>Reviewed by:</p></div>';

			$reviewer = get_option( 'addiction_recovery_reviews_default_author' );

			$html .= $this->get_reviewer_card_markup( $reviewer );

			$html .= '</div></div>';
		}

		if ( ! empty( get_option( 'addiction_recovery_reviews_enabled' ) ) && ! empty( get_option( 'addiction_historical_review_date' ) ) ) {
			if ( strtotime( $post->post_date ) < strtotime( get_option( 'addiction_historical_review_date' ) ) ) {
				return $content;
			}
		}

		return $content . $html;
	}

	public function add_filter_for_output_html(): void {
		$final = '';

		$levels = ob_get_level();


		$final .= ob_get_clean();

		echo apply_filters( 'final_output', $final );
	}

	/**
	 * Return markup for a reviewer's card with all needed information.
	 *
	 * @param integer $reviewer
	 * @return string
	 */
	private function get_reviewer_card_markup( int $reviewer ): string {
		$reviewer_name = get_post_meta( $reviewer, 'name', 1 );
		$reviewer_description = get_post_meta( $reviewer, 'bio', 1 );
		$image = get_the_post_thumbnail_url( $reviewer, 'full' );
		$link = get_permalink( $reviewer );

		$html = "<a href=\"{$link}\" class=\"ar-reviewer-card--inner\" style=\"margin-bottom:20px;text-decoration:none;\"><img src=\"{$image}\" alt=\"Author Profile Image\"><div class=\"ar-reviewer-info\"><h3>{$reviewer_name}</h3><p>{$reviewer_description}</p></div></a>";

		return $html;
	}

	/**
	 * Return markup for the single card which will be shown in the list.
	 *
	 * @param object $reviewer
	 * @return string
	 */
	private function get_markup_card_for_list( object $reviewer ): string {
		$name = get_post_meta( $reviewer->ID, 'name', 1 );
		$title = get_post_meta( $reviewer->ID, 'title', 1 );
		$description = get_post_meta( $reviewer->ID, 'bio', 1 );
		if ( strlen( $description ) > 250 ) {
			$description = substr( $description, 0, 250 ) . '...';
		}
		$link = get_permalink( $reviewer->ID );
		$link_twitter = get_post_meta( $reviewer->ID, 'twitter', 1 );
		$link_facebook = get_post_meta( $reviewer->ID, 'facebook', 1 );
		$link_instagram = get_post_meta( $reviewer->ID, 'instagram', 1 );
		$link_linkedin = get_post_meta( $reviewer->ID, 'linkedin', 1 );
		$image = get_the_post_thumbnail_url( $reviewer->ID, 'full' );

		$html = '<div class="ar-reviewers-list-entry-1"><span class="ar-reviewers-list--reviewer-card">';

		$html .= '<a href="' . $link . '" class="ar-reviewers-list-image-wrapper"><img class="ar-reviewers-list-image lazy" data-original="' . $image . '"></a><a href="' . $link . '" class="ar-reviewers-list-heading">' . $name . '</a><h4 class="ar-reviewers-list-subheading">' . $title . '</h4><p class="ar-reviewer-description">' . $description . '</p>';
		$html .= '<span class="ar-reviewer-social">';
		if ( ! empty( $link_twitter ) ) {
			$html .= '<a href="' . $link_twitter . '" class="ar-reviewer-social-link"><i class="fab fa-twitter"></i></a>';
		}
		if ( ! empty( $link_facebook ) ) {
			$html .= '<a href="' . $link_facebook . '" class="ar-reviewer-social-link"><i class="fab fa-facebook"></i></a>';
		}
		if ( ! empty( $link_instagram ) ) {
			$html .= '<a href="' . $link_instagram . '" class="ar-reviewer-social-link"><i class="fab fa-instagram"></i></a>';
		}
		if ( ! empty( $link_linkedin ) ) {
			$html .= '<a href="' . $link_linkedin . '" class="ar-reviewer-social-link"><i class="fab fa-linkedin"></i></a>';
		}
		$html .= '</span></span></div>';

		return $html;
	}

	/**
	 * Return markup for ta single card which will be shown in the list.
	 *
	 * @param object $reviewer
	 * @return string
	 */
	private function get_markup_card_for_list_authors( object $author ): string {
		$name = get_user_meta( $author->ID, 'ar_name', 1 );
		$title = get_user_meta( $author->ID, 'ar_title', 1 );
		$description = get_user_meta( $author->ID, 'ar_description', 1 );
		if ( strlen( $description ) > 250 ) {
			$description = substr( $description, 0, 250 ) . '...';
		}
		$link = get_author_posts_url( $author->ID );
		$link_twitter = get_user_meta( $author->ID, 'ar_twitter', 1 );
		$link_facebook = get_user_meta( $author->ID, 'ar_facebook', 1 );
		$link_instagram = get_user_meta( $author->ID, 'ar_instagram', 1 );
		$link_linkedin = get_user_meta( $author->ID, 'ar_linkedin', 1 );
		$image = get_avatar_url( $author->ID );

		$html = '<div class="ar-reviewers-list-entry-1"><span class="ar-reviewers-list--reviewer-card">';

		$html .= '<a href="' . $link . '" class="ar-reviewers-list-image-wrapper"><img class="ar-reviewers-list-image lazy" data-original="' . $image . '"></a><a href="' . $link . '" class="ar-reviewers-list-heading">' . $name . '</a><h4 class="ar-reviewers-list-subheading">' . $title . '</h4><p class="ar-reviewer-description">' . $description . '</p><span class="ar-reviewer-social">';

		if ( ! empty( $link_twitter ) ) {
			$html .= '<a href="' . $link_twitter . '" class="ar-reviewer-social-link"><i class="fab fa-twitter"></i></a>';
		}
		if ( ! empty( $link_facebook ) ) {
			$html .= '<a href="' . $link_facebook . '" class="ar-reviewer-social-link"><i class="fab fa-facebook"></i></a>';
		}
		if ( ! empty( $link_instagram ) ) {
			$html .= '<a href="' . $link_instagram . '" class="ar-reviewer-social-link"><i class="fab fa-instagram"></i></a>';
		}
		if ( ! empty( $link_linkedin ) ) {
			$html .= '<a href="' . $link_linkedin . '" class="ar-reviewer-social-link"><i class="fab fa-linkedin"></i></a>';
		}
		$html .= '</span></span></div>';

		return $html;
	}

	/**
	 * Return template for a single reviewer.
	 *
	 * @param $template
	 * @return void
	 */
	public function load_reviewer_template( $template ) {
		global $post;

		if ( 'ar_reviewer_h' === $post->post_type && locate_template( array( 'single-ar_reviewer_h.php' ) ) !== $template ) {
			return plugin_dir_path( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'templates/single-ar_reviewer_h.php';
		}

		return $template;
	}
}

new Reviewer_Creator;