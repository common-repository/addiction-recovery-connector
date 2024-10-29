<?php

/**
 * Instagram feed creator.
 */
class AR_Instagram_Feed {
	public function __construct() {
		add_action( 'plugin_loaded', array( $this, 'add_new_size_for_thumbnail' ) );
		add_action( 'template_redirect', array( $this, 'add_custom_redirect' ) );
		add_action( 'wp_ajax_ar_get_page', array( $this, 'send_posts_row' ) );
		add_action( 'wp_ajax_nopriv_ar_get_page', array( $this, 'send_posts_row' ) );
		add_action( 'ar_show_instagram_feed', array( $this, 'show_feed_page' ) );
		add_action( 'ar_display_feed_header', array( $this, 'display_header' ) );
		add_action( 'ar_display_feed_footer', array( $this, 'display_footer' ) );
	}

	/**
	 * Includes scripts.
	 *
	 * @return void
	 */
	public function add_scripts_and_styles(): void {
		if ( $this->isGeneralPage() ) {
			if ( ! wp_script_is( 'addiction-recovery-js-instagram-lazy', 'enqueued' ) ) {
				if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
					wp_enqueue_script( 'addiction-recovery-js-instagram-jquery', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/jquery-2.1.3.min.js', [], [], true );
				}
				wp_enqueue_script( 'addiction-recovery-js-instagram-lazy', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/jquery.lazyload.min.js', [], [], true );
				wp_enqueue_script( 'addiction-recovery-js-instagram-lazy-loader', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/image-loader.js', [], [], true );
			}
			wp_enqueue_script( 'addiction-recovery-js-instagram', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/addiction-instagram-creator.js', [], [], true );
			wp_enqueue_style( 'addiction-recovery-css-instagram', plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/addiction-instagram-creator.css', [], [] );
		}
	}

	/**
	 * Checks if the page is a general page.
	 *
	 * @return boolean
	 */
	private function isGeneralPage(): bool {
		$slug = explode( '/', $_SERVER['REQUEST_URI'] )[1];
		if ( ! empty( get_option( 'addiction_recovery_instagram_feed_enabled' ) ) && ! empty( get_option( 'addiction_recovery_instagram_feed_slug' ) ) && ! is_admin() ) {
			if ( $slug == get_option( 'addiction_recovery_instagram_feed_slug' ) || $slug == 'instagram' ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns a page with the feed. If Instagram Feed is enabled in the admin, it will redirect a user to the home page.
	 *
	 * @return void
	 */
	public function add_custom_redirect(): void {
		$slug = explode( '/', $_SERVER['REQUEST_URI'] )[1];
		if ( ! empty( get_option( 'addiction_recovery_instagram_feed_enabled' ) ) && ! empty( get_option( 'addiction_recovery_instagram_feed_slug' ) ) && ! is_admin() ) {
			if ( $slug == get_option( 'addiction_recovery_instagram_feed_slug' ) || $slug == 'instagram' ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_and_styles' ), 99 );
				add_filter( 'pre_get_document_title', function ($title) {
					return 'Instagram feed';
				}, 10 );
				$this->display_custom_template();
				exit;
			}
		} elseif ( empty( get_option( 'addiction_recovery_instagram_feed_enabled' ) ) && ! empty( get_option( 'addiction_recovery_instagram_feed_slug' ) ) && ! is_admin() ) {
			if ( $slug == get_option( 'addiction_recovery_instagram_feed_slug' ) ) {
				wp_redirect( home_url( '/' ), 302 );
				exit;
			}
		}
	}

	/**
	 * Adds a new size for thumbnails which will be used in the feed.
	 *
	 * @return void
	 */
	public function add_new_size_for_thumbnail(): void {
		add_image_size( 'addiction_recovery_feed_thumbnail', 700, 465 );
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
	 * Displays a block with the Instagram feed.
	 *
	 * @return void
	 */
	public function show_feed_page(): void {
		http_response_code( 200 );
		echo $this->get_header();
		echo $this->create_feed();
		echo $this->get_footer();
	}

	/**
	 * Return a template path of the custom page.
	 *
	 * @return string
	 */
	private function get_template_theme_path(): string {
		$template_dir = get_stylesheet_directory() . '/ar/';
		$template_path = $template_dir . 'instagram-feed-template.php';

		return $template_path;
	}

	/**
	 * Display a template.
	 *
	 * @return void
	 */
	private function display_custom_template() {
		$template_path = $this->get_template_theme_path();

		if ( file_exists( $template_path ) ) {
			include( $template_path );
		} else {
			include( plugin_dir_path( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'templates/instagram-feed-template.php' );
		}
	}

	/**
	 * Creates a feed.
	 *
	 * @return string
	 */
	private function create_feed(): string {
		$html = "";

		$all_posts = $this->get_posts_array();

		$html = $this->generate_rows( $all_posts );

		return $html;
	}

	/**
	 * Returns a markup with post's rows. The second parameter is not necessary, it just adds a class 'fresh' to the img tag.
	 *
	 * @param array $posts
	 * @param integer|null $is_fresh
	 * @return string
	 */
	private function generate_rows( array $all_posts, ?int $is_fresh = null ): string {
		$html = "";

		if ( ! empty( $all_posts ) ) {
			foreach ( $all_posts as $index => $item_post ) {
				if ( $index === 0 || $index === 3 ) {
					$html .= "<div class=\"ar-connect-feed-row\">";
				}

				$image_url = get_the_post_thumbnail_url( $item_post->ID, "addiction_recovery_feed_thumbnail" );
				$post_link = get_permalink( $item_post->ID );
				$fresh_class = '';

				if ( null !== $is_fresh ) {
					$fresh_class = "fresh";
				}

				if ( empty( $image_url ) ) {
					$image_url = plugin_dir_url( ADDICTION_RECOVERY_PLUGIN_DIR_PATH ) . 'assets/addiction-recovery-placeholder.png';
				}

				$html .= "<a href=\"{$post_link}\" class=\"ar-connect-entry-1\"><div class=\"my-image-wrapper\" style=\"background-image: url({$image_url});background-size: cover;background-position: center;\"><img class=\"ar-connect-image lazy {$fresh_class}\" style=\"opacity: 0\" data-original=\"https://cf.kind.co/transparent.png\"></div><h2 class=\"ar-connect-heading\">{$item_post->post_title}</h2></a href=\"{$post_link}\">";

				if ( $index === 2 || $index === 5 ) {
					$html .= "</div>";
				}
			}
		}

		return $html;
	}

	/**
	 * Returns an array with posts.
	 *
	 * @param ?int $page
	 * @return array
	 */
	private function get_posts_array( ?int $page = null ): array {
		$all_posts = new WP_Query( array(
			"post_type" => "post",
			"post_status" => "publish",
			"order" => "DESC",
			"orderBy" => "post_date",
			"posts_per_page" => 6,
			"paged" => ( $page ? $page : 1 ),
		) );

		return $all_posts->posts;
	}

	/**
	 * Ajax function. Sends post rows. Needs a post parameter as page_id.
	 *
	 * @return void
	 */
	public function send_posts_row(): void {
		$page = $_POST["page"];
		$html = '';

		$all_posts = $this->get_posts_array( (int) $_POST["page"] );

		$html = $this->generate_rows( $all_posts, true );

		echo $html;
		exit;
	}

	/**
	 * Returns a header.
	 *
	 * @return string
	 */
	private function get_header(): string {
		$header = '<h1 class="ar-connect-main-heading">Instagram Feed</h1><div class="ar-connect-feed-wrapper">';

		return $header;
	}

	/**
	 * Returns a footer.
	 *
	 * @return string
	 */
	private function get_footer(): string {
		$footer = '</div><div id="ar-page-counter"><input id="ar-page" type="hidden" value="2"></div>';

		return $footer;
	}
}

new AR_Instagram_Feed;