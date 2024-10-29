<?php

/**
 * Related posts' list Creator.
 */
class Related_Creator extends WP_Widget {
	public string $founded = '';

	public function __construct() {
		parent::__construct(
			'Related_Creator',
			__( 'Related Content Widget by Addiction Recovery', 'ar_widget_domain' ),
			array( 'description' => __( 'Shows a list with related posts.', 'ar_widget_domain' ), )
		);

		add_action('wp_enqueue_scripts', function() {
			if ( is_active_widget(false, false, $this->id_base, true) ) {
				if ( ! wp_script_is( 'addiction-recovery-js-instagram-lazy', 'enqueued' ) ) {
					if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
						wp_enqueue_script( 'addiction-recovery-js-instagram-jquery', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/jquery-2.1.3.min.js', [], [], true );
					}
					wp_enqueue_script( 'addiction-recovery-js-instagram-lazy', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/jquery.lazyload.min.js', [], [], true );
					wp_enqueue_script( 'addiction-recovery-js-instagram-lazy-loader', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/image-loader.js', [], [], true );
				}
				if ( ! wp_script_is( 'related-posts-styles', 'enqueued' ) ) {
					wp_enqueue_style( 'related-posts-styles', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/related-posts-styles.css', [], '1.4' );
				}
			}
		});	
	}

	/**
	 * Create front-end for the widget.
	 *
	 * @param [type] $args
	 * @param [type] $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {
		global $post;
		global $wpdb;

		if ( isset( $post ) ) {
			$tags_array = array();
			$categories_array = array();
			if ( get_the_tags( $post->ID ) ) {
				foreach ( get_the_tags( $post->ID ) as $tag ) {
					$tags_array[] = $tag->term_id;
				}
			}

			if ( get_the_category( $post->ID ) ) {
				foreach ( get_the_category( $post->ID ) as $category ) {
					$categories_array[] = $category->term_id;
				}
			}

			$query_results_tags = array();

			$all_taxonomies_ids = array_merge( $tags_array, $categories_array );
			$all_taxonomies_ids = implode( ', ', $all_taxonomies_ids );

			$tags_array_imploded = implode( ', ', $tags_array );
			$categories_array_imploded = implode( ', ', $categories_array );

			$query_results_categories_taxonomies = array();

			$this->founded = $post->ID;

			if ( ! empty( $tags_array ) && ! empty( $categories_array ) ) {
				$query_results_categories_taxonomies = $wpdb->get_results( "SELECT p.ID as object_id FROM {$wpdb->prefix}term_relationships AS tr LEFT JOIN {$wpdb->prefix}posts AS p ON tr.object_id = p.ID  WHERE tr.term_taxonomy_id IN ( {$tags_array_imploded} ) AND tr.term_taxonomy_id IN ( {$categories_array_imploded} ) AND tr.object_id != {$post->ID} AND p.post_status LIKE 'publish' ORDER BY p.post_modified DESC LIMIT 5" );
			}

			if ( count( $query_results_categories_taxonomies ) < 5 && ! empty( $tags_array_imploded ) ) {
				$limit = 5 - count( $query_results_categories_taxonomies );
				$founded = $this->get_founded_rows_string( $query_results_categories_taxonomies );
				$query_results_tags = $wpdb->get_results( "SELECT p.ID as object_id FROM {$wpdb->prefix}term_relationships AS tr LEFT JOIN {$wpdb->prefix}posts AS p ON tr.object_id = p.ID  WHERE tr.term_taxonomy_id IN ( {$tags_array_imploded} ) AND tr.object_id NOT IN ($founded) AND p.post_status LIKE 'publish' ORDER BY p.post_modified DESC LIMIT {$limit}" );
				$query_results_categories_taxonomies = array_merge( $query_results_categories_taxonomies, $query_results_tags );
			}

			if ( count( $query_results_categories_taxonomies ) < 5 && ! empty( $categories_array_imploded ) ) {
				$limit = 5 - count( $query_results_categories_taxonomies );
				$founded = $this->get_founded_rows_string( $query_results_categories_taxonomies );
				$query_results_tags = $wpdb->get_results( "SELECT p.ID as object_id FROM {$wpdb->prefix}term_relationships AS tr LEFT JOIN {$wpdb->prefix}posts AS p ON tr.object_id = p.ID  WHERE tr.term_taxonomy_id IN ( {$categories_array_imploded} ) AND tr.object_id NOT IN ($founded) AND p.post_status LIKE 'publish' ORDER BY p.post_modified DESC LIMIT {$limit}" );
				$query_results_categories_taxonomies = array_merge( $query_results_categories_taxonomies, $query_results_tags );
			}

			if ( count( $query_results_categories_taxonomies ) < 5 && ! empty( $all_taxonomies_ids ) ) {
				$limit = 5 - count( $query_results_categories_taxonomies );
				$founded = $this->get_founded_rows_string( $query_results_categories_taxonomies );
				$query_results_tags = $wpdb->get_results( "SELECT p.ID as object_id FROM {$wpdb->prefix}term_relationships AS tr LEFT JOIN {$wpdb->prefix}posts AS p ON tr.object_id = p.ID  WHERE tr.term_taxonomy_id IN ( {$all_taxonomies_ids} ) AND tr.object_id NOT IN ($founded) AND p.post_status LIKE 'publish' ORDER BY p.post_modified DESC LIMIT {$limit}" );
				$query_results_categories_taxonomies = array_merge( $query_results_categories_taxonomies, $query_results_tags );
			}

			$query_results = $query_results_categories_taxonomies;

			if ( empty( $query_results ) ) {
				$query_posts = new WP_Query(
					array(
						'post_type' => 'post',
						'post_status' => 'publish',
						'post__not_in' => array( $post->ID ),
						'posts_per_page' => 4,
						'order' => 'DESC',
						'orderby' => 'post_date'
					)
				);
			}

			$html = '';

			if ( ! empty( $query_results ) ) {
				if ( count( $query_results ) < 4 ) {
					$count = 4 - count( $query_results_categories_taxonomies );
					$items = '';
					foreach ( $query_results as $key => $post_item ) {
						$id = $post_item->object_id;
						$items .= "{$id},";
					}
					$items = rtrim( $items, ',' );
					$additional = $wpdb->get_results( "SELECT p.ID as object_id FROM {$wpdb->prefix}posts as p WHERE p.ID NOT IN ($items) AND post_type = 'post' AND post_status = 'publish' ORDER by p.ID DESC LIMIT 0, $count " );
					$query_results = array_merge( $query_results, $additional );
				}

				$html .= '<div class="wrapper-related-posts"><h2 class="widget-title">Related Content</h2><div class="ar-connect-feed-wrapper"><div class="ar-connect-feed-row">';
				$items_s = array();
				foreach ( $query_results as $key => $post_item ) {
					if ( ! in_array( $post_item->object_id, $items_s ) ) {
						$items_s[] = $post_item->object_id;
						if ( $key < 4 ) {
							$title = get_the_title( $post_item->object_id );
							$permalink = get_permalink( $post_item->object_id );
							$image = get_the_post_thumbnail_url( $post_item->object_id, 'full' );
							$html .= "<a href=\"{$permalink}\" class=\"ar-connect-entry-1\"><img alt=\"\" class=\"ar-connect-image lazy\" data-original=\"{$image}\"><h2 class=\"ar-connect-heading\">{$title}</h2></a>";
						}
					}
				}
				$html .= '</div></div></div>';
			} else {
				$html .= '<div class="wrapper-related-posts"><h2 class="widget-title">Related Content</h2><div class="ar-connect-feed-wrapper"><div class="ar-connect-feed-row">';
				foreach ( $query_posts->posts as $key => $post_item ) {
					if ( $key < 4 ) {
						$post_item = get_post( $post_item );
						$permalink = get_permalink( $post_item->ID );
						$image = get_the_post_thumbnail_url( $post_item->ID, 'full' );
						$html .= "<a href=\"{$permalink}\" class=\"ar-connect-entry-1\"><img alt=\"\" class=\"ar-connect-image lazy\" data-original=\"{$image}\"><h2 class=\"ar-connect-heading\">{$post_item->post_title}</h2></a>";
					}
				}
				$html .= '</div></div></div>';
			}
		}

		if ( get_post_type( $post->ID ) == 'post' ) {
			echo $html;
		}
	}

	/**
	 * Return a string with ids of founded posts.
	 *
	 * @param array $target_array
	 * @return string
	 */
	private function get_founded_rows_string( array $target_array ): string {
		$items = '';
		if ( ! empty( $target_array ) ) {
			foreach ( $target_array as $item ) {
				$items .= ', ' . $item->object_id;
			}
		}

		$this->founded .= $items;

		return $this->founded;
	}

	/**
	 * Return a front-side for the widget.
	 *
	 * @param $instance
	 * @return void
	 */
	public function form( $instance ): void {
		?>
										<p>Recent posts</p>
									<?php
	}
}


class Related_Creator_Title_Link extends WP_Widget {
	public string $founded = '';

	public function __construct() {
		parent::__construct(
			'Related_Creator_Title_Link',
			__( 'Related Content Widget (titles/links only) by Addiction Recovery', 'ar_widget_domain' ),
			array( 'description' => __( 'Shows a list with related posts. Titles and links only.', 'ar_widget_domain' ), )
		);

		add_action('wp_enqueue_scripts', function() {
			if ( is_active_widget(false, false, $this->id_base, true) ) {
				if ( ! wp_script_is( 'addiction-recovery-js-instagram-lazy', 'enqueued' ) ) {
					if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
						wp_enqueue_script( 'addiction-recovery-js-instagram-jquery', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/jquery-2.1.3.min.js', [], [], true );
					}
					wp_enqueue_script( 'addiction-recovery-js-instagram-lazy', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/jquery.lazyload.min.js', [], [], true );
					wp_enqueue_script( 'addiction-recovery-js-instagram-lazy-loader', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/image-loader.js', [], [], true );
				}
				if ( ! wp_script_is( 'related-posts-styles', 'enqueued' ) ) {
					wp_enqueue_style( 'related-posts-styles', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/related-posts-styles.css', [], '1.3' );
				}
			}
		});	
	}

	/**
	 * Create front-end for the widget.
	 *
	 * @param [type] $args
	 * @param [type] $instance
	 * @return void
	 */
	public function widget( $args, $instance ) {
		global $post;
		global $wpdb;

		if ( isset( $post ) ) {
			$tags_array = array();
			$categories_array = array();
			if ( get_the_tags( $post->ID ) ) {
				foreach ( get_the_tags( $post->ID ) as $tag ) {
					$tags_array[] = $tag->term_id;
				}
			}

			if ( get_the_category( $post->ID ) ) {
				foreach ( get_the_category( $post->ID ) as $category ) {
					$categories_array[] = $category->term_id;
				}
			}

			$query_results_tags = array();

			$all_taxonomies_ids = array_merge( $tags_array, $categories_array );
			$all_taxonomies_ids = implode( ', ', $all_taxonomies_ids );

			$tags_array_imploded = implode( ', ', $tags_array );
			$categories_array_imploded = implode( ', ', $categories_array );

			$query_results_categories_taxonomies = array();

			$this->founded = $post->ID;

			if ( ! empty( $tags_array ) && ! empty( $categories_array ) ) {
				$query_results_categories_taxonomies = $wpdb->get_results( "SELECT p.ID as object_id FROM {$wpdb->prefix}term_relationships AS tr LEFT JOIN {$wpdb->prefix}posts AS p ON tr.object_id = p.ID  WHERE tr.term_taxonomy_id IN ( {$tags_array_imploded} ) AND tr.term_taxonomy_id IN ( {$categories_array_imploded} ) AND tr.object_id != {$post->ID} AND p.post_status LIKE 'publish' ORDER BY p.post_modified DESC LIMIT 5" );
			}

			if ( count( $query_results_categories_taxonomies ) < 5 && ! empty( $tags_array_imploded ) ) {
				$limit = 5 - count( $query_results_categories_taxonomies );
				$founded = $this->get_founded_rows_string( $query_results_categories_taxonomies );
				$query_results_tags = $wpdb->get_results( "SELECT p.ID as object_id FROM {$wpdb->prefix}term_relationships AS tr LEFT JOIN {$wpdb->prefix}posts AS p ON tr.object_id = p.ID  WHERE tr.term_taxonomy_id IN ( {$tags_array_imploded} ) AND tr.object_id NOT IN ($founded) AND p.post_status LIKE 'publish' ORDER BY p.post_modified DESC LIMIT {$limit}" );
				$query_results_categories_taxonomies = array_merge( $query_results_categories_taxonomies, $query_results_tags );
			}

			if ( count( $query_results_categories_taxonomies ) < 5 && ! empty( $categories_array_imploded ) ) {
				$limit = 5 - count( $query_results_categories_taxonomies );
				$founded = $this->get_founded_rows_string( $query_results_categories_taxonomies );
				$query_results_tags = $wpdb->get_results( "SELECT p.ID as object_id FROM {$wpdb->prefix}term_relationships AS tr LEFT JOIN {$wpdb->prefix}posts AS p ON tr.object_id = p.ID  WHERE tr.term_taxonomy_id IN ( {$categories_array_imploded} ) AND tr.object_id NOT IN ($founded) AND p.post_status LIKE 'publish' ORDER BY p.post_modified DESC LIMIT {$limit}" );
				$query_results_categories_taxonomies = array_merge( $query_results_categories_taxonomies, $query_results_tags );
			}

			if ( count( $query_results_categories_taxonomies ) < 5 && ! empty( $all_taxonomies_ids ) ) {
				$limit = 5 - count( $query_results_categories_taxonomies );
				$founded = $this->get_founded_rows_string( $query_results_categories_taxonomies );
				$query_results_tags = $wpdb->get_results( "SELECT p.ID as object_id FROM {$wpdb->prefix}term_relationships AS tr LEFT JOIN {$wpdb->prefix}posts AS p ON tr.object_id = p.ID  WHERE tr.term_taxonomy_id IN ( {$all_taxonomies_ids} ) AND tr.object_id NOT IN ($founded) AND p.post_status LIKE 'publish' ORDER BY p.post_modified DESC LIMIT {$limit}" );
				$query_results_categories_taxonomies = array_merge( $query_results_categories_taxonomies, $query_results_tags );
			}

			$query_results = $query_results_categories_taxonomies;

			if ( empty( $query_results ) ) {
				$query_posts = new WP_Query(
					array(
						'post_type' => 'post',
						'post_status' => 'publish',
						'post__not_in' => array( $post->ID ),
						'posts_per_page' => 4,
						'order' => 'DESC',
						'orderby' => 'post_date'
					)
				);
			}

			$html = '';

			if ( ! empty( $query_results ) ) {
				if ( count( $query_results ) < 4 ) {
					$count = 4 - count( $query_results_categories_taxonomies );
					$items = '';
					foreach ( $query_results as $key => $post_item ) {
						$id = $post_item->object_id;
						$items .= "{$id},";
					}
					$items = rtrim( $items, ',' );
					$additional = $wpdb->get_results( "SELECT p.ID as object_id FROM {$wpdb->prefix}posts as p WHERE p.ID NOT IN ($items) AND post_type = 'post' AND post_status = 'publish' ORDER by p.ID DESC LIMIT 0, $count " );
					$query_results = array_merge( $query_results, $additional );
				}

				$html .= '<section id="wrapper-related-posts title-link-only"><h2>Related Content</h2><ul>';
				$items_s = array();
				foreach ( $query_results as $key => $post_item ) {
					if ( ! in_array( $post_item->object_id, $items_s ) ) {
						$items_s[] = $post_item->object_id;
						if ( $key < 4 ) {
							$title = get_the_title( $post_item->object_id );
							$permalink = get_permalink( $post_item->object_id );
							$image = get_the_post_thumbnail_url( $post_item->object_id, 'full' );
							$html .= "<li><a href=\"{$permalink}\">{$title}</a></li>";
						}
					}
				}
				$html .= '</ul></section>';
			} else {
				$html .= '<div class="wrapper-related-posts title-link-only"><h2>Related Content</h2><ul>';
				foreach ( $query_posts->posts as $key => $post_item ) {
					if ( $key < 4 ) {
						$post_item = get_post( $post_item );
						$permalink = get_permalink( $post_item->ID );
						$image = get_the_post_thumbnail_url( $post_item->ID, 'full' );
						$html .= "<li><a href=\"{$permalink}\">{$post_item->post_title}</a><li>";
					}
				}
				$html .= '</ul></div>';
			}
		}

		if ( get_post_type( $post->ID ) == 'post' ) {
			echo $html;
		}
	}

	/**
	 * Return a string with ids of founded posts.
	 *
	 * @param array $target_array
	 * @return string
	 */
	private function get_founded_rows_string( array $target_array ): string {
		$items = '';
		if ( ! empty( $target_array ) ) {
			foreach ( $target_array as $item ) {
				$items .= ', ' . $item->object_id;
			}
		}

		$this->founded .= $items;

		return $this->founded;
	}

	/**
	 * Return a front-side for the widget.
	 *
	 * @param $instance
	 * @return void
	 */
	public function form( $instance ): void {
		?>
										<p>Related posts (titles/links only)</p>
									<?php
	}
}


class Recent_Posts_AR extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'Recent_Posts_AR',
			__( 'Recent Content Widget by Addiction Recovery', 'ar_widget_domain' ),
			array( 'description' => __( 'Shows a list with recent posts.', 'ar_widget_domain' ), )
		);
		add_action('wp_enqueue_scripts', function() {
			if ( is_active_widget(false, false, $this->id_base, true) ) {
				if ( ! wp_script_is( 'addiction-recovery-js-instagram-lazy', 'enqueued' ) ) {
					if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
						wp_enqueue_script( 'addiction-recovery-js-instagram-jquery', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/jquery-2.1.3.min.js', [], [], true );
					}
					wp_enqueue_script( 'addiction-recovery-js-instagram-lazy', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/jquery.lazyload.min.js', [], [], true );
					wp_enqueue_script( 'addiction-recovery-js-instagram-lazy-loader', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/image-loader.js', [], [], true );
				}
				if ( ! wp_script_is( 'related-posts-styles', 'enqueued' ) ) {
					wp_enqueue_style( 'related-posts-styles', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/related-posts-styles.css', [], '1.3' );
				}
			}
		});
	}

	public function widget( $args, $instance ) {
		$query_posts = $this->get_recent_posts( 4 );

		$html = '';

		$html .= '<div class="wrapper-related-posts"><h2 class="widget-title">Recent Content</h2><div class="ar-connect-feed-wrapper"><div class="ar-connect-feed-row">';
		foreach ( $query_posts as $post_item ) {
			$post_item = get_post( $post_item );
			$permalink = get_permalink( $post_item->ID );
			$image = get_the_post_thumbnail_url( $post_item->ID, 'full' );
			$html .= "<a href=\"{$permalink}\" class=\"ar-connect-entry-1\"><img alt=\"\" class=\"ar-connect-image lazy\" data-original=\"{$image}\"><h2 class=\"ar-connect-heading\">{$post_item->post_title}</h2></a>";
		}
		$html .= '</div></div></div>';

		echo $html;
	}

	/**
	 * Return recent posts.
	 *
	 * @param integer $number_of_posts
	 * @return array
	 */
	private function get_recent_posts( int $number_of_posts ): array {
		global $post;
		$recent_posts_query = new WP_Query( array(
			'post_type' => 'post',
			'posts_per_page' => $number_of_posts,
			'post_status' => 'publish',
			'post__not_in' => array( $post->ID )
		) );

		return $recent_posts_query->posts;
	}

	/**
	 * Return a front-side for the widget.
	 *
	 * @param $instance
	 * @return void
	 */
	public function form( $instance ): void {
		?>
										<p>AR recent posts</p>
								<?php
	}
}

new Related_Creator;
new Related_Creator_Title_Link;
new Recent_Posts_AR( true );

// Register and load the widget
function ar_load_widget() {
	register_widget( 'Related_Creator' );
	register_widget( 'Recent_Posts_AR' );
	register_widget( 'Related_Creator_Title_Link' );
}
add_action( 'widgets_init', 'ar_load_widget' );