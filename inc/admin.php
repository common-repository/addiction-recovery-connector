<?php
add_action( 'admin_menu', 'addiction_recovery_options_page' );
function addiction_recovery_options_page() {
	add_menu_page(
		'Addiction Recovery Connector',
		'AR Connector',
		'manage_options',
		'addition_recovery_api',
		'addition_recovery_api_page_html'
	);
}

function addition_recovery_api_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! get_option( 'addiction_recovery_instagram_feed_slug' ) || empty( get_option( 'addiction_recovery_instagram_feed_slug' ) ) ) {
		update_option( 'addiction_recovery_instagram_feed_slug', 'instagram' );
	}

	if ( ! get_option( 'addiction_recovery_reviewers_slug' ) || empty( get_option( 'addiction_recovery_reviewers_slug' ) ) ) {
		update_option( 'addiction_recovery_reviewers_slug', 'reviewers' );
	}

	if ( ! get_option( 'addiction_recovery_authors_slug' ) || empty( get_option( 'addiction_recovery_authors_slug' ) ) ) {
		update_option( 'addiction_recovery_authors_slug', 'authors' );
	}

	if ( ! get_option( 'addiction_recovery_instagram_feed_enabled' ) ) {
		update_option( 'addiction_recovery_instagram_feed_enabled', null );
	}

	if ( ! get_option( 'addiction_recovery_reviews_enabled' ) ) {
		update_option( 'addiction_recovery_reviews_enabled', null );
	}

	if ( ! get_option( 'addiction_recovery_authors_enabled' ) ) {
		update_option( 'addiction_recovery_authors_enabled', null );
	}

	if ( ! get_option( 'addiction_recovery_basic_markup' ) ) {
		update_option( 'addiction_recovery_basic_markup', null );
	}

	if ( isset( $_POST['settings-updated'] ) ) {
		update_option( 'addiction_recovery_custom_api_token', sanitize_text_field( $_POST['addiction_recovery_custom_api_token'] ) );
		update_option( 'addiction_recovery_main_user', sanitize_text_field( $_POST['addiction_recovery_main_user'] ) );
		update_option( 'addiction_recovery_web_portal_endpoint', sanitize_text_field( $_POST['addiction_recovery_web_portal_endpoint'] ) );
		update_option( 'addiction_recovery_web_portal_token', sanitize_text_field( $_POST['addiction_recovery_web_portal_token'] ) );
		update_option( 'addiction_recovery_instagram_feed_slug', sanitize_text_field( $_POST['addiction_recovery_instagram_feed_slug'] ) );

		if ( isset( $_POST['addiction_recovery_instagram_feed_enabled'] ) ) {
			update_option( 'addiction_recovery_instagram_feed_enabled', sanitize_text_field( $_POST['addiction_recovery_instagram_feed_enabled'] ) );
		} else {
			update_option( 'addiction_recovery_instagram_feed_enabled', null );
		}

		if ( isset( $_POST['addiction_recovery_anchors_creator_enabled'] ) ) {
			update_option( 'addiction_recovery_anchors_creator_enabled', sanitize_text_field( $_POST['addiction_recovery_anchors_creator_enabled'] ) );
		} else {
			update_option( 'addiction_recovery_anchors_creator_enabled', null );
		}

		if ( isset( $_POST['addiction_recovery_authors_enabled'] ) ) {
			update_option( 'addiction_recovery_authors_enabled', sanitize_text_field( $_POST['addiction_recovery_authors_enabled'] ) );
		} else {
			update_option( 'addiction_recovery_authors_enabled', null );
		}

		if ( isset( $_POST['addiction_recovery_reviewers_slug'] ) ) {
			flush_rewrite_rules();
			update_option( 'addiction_recovery_reviewers_slug', sanitize_text_field( $_POST['addiction_recovery_reviewers_slug'] ) );
		} else {
			flush_rewrite_rules();
			update_option( 'addiction_recovery_reviewers_slug', null );
		}


		if ( isset( $_POST['addiction_recovery_authors_slug'] ) ) {
			update_option( 'addiction_recovery_authors_slug', sanitize_text_field( $_POST['addiction_recovery_authors_slug'] ) );
		} else {
			update_option( 'addiction_recovery_authors_slug', null );
		}

		if ( isset( $_POST['addiction_recovery_basic_markup'] ) ) {
			update_option( 'addiction_recovery_basic_markup', sanitize_text_field( $_POST['addiction_recovery_basic_markup'] ) );
		} else {
			update_option( 'addiction_recovery_basic_markup', null );
		}

		if ( isset( $_POST['addiction_recovery_reviews_enabled'] ) ) {
			update_option( 'addiction_recovery_reviews_enabled', sanitize_text_field( $_POST['addiction_recovery_reviews_enabled'] ) );
		} else {
			update_option( 'addiction_recovery_reviews_enabled', null );
		}

		if ( isset( $_POST['addiction_recovery_reviews_default_author'] ) ) {
			update_option( 'addiction_recovery_reviews_default_author', sanitize_text_field( $_POST['addiction_recovery_reviews_default_author'] ) );
		} else {
			update_option( 'addiction_recovery_reviews_default_author', null );
		}

		if ( isset( $_POST['addiction_historical_review_date'] ) ) {
			update_option( 'addiction_historical_review_date', sanitize_text_field( $_POST['addiction_historical_review_date'] ) );
		}


		// add settings saved message with the class of "updated"
		add_settings_error( 'wporg_messages', 'wporg_message', __( 'Settings Saved', 'wporg' ), 'updated' );
	}

	$list_users = get_users();

	$changed = false;
	if ( ! empty( $_POST ) ) {
		if ( ! isset( $_POST['flush_permalinks'] ) ) {
			$changed = true;
		}
	}
	?>
				<div class="wrap">
					<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
					<form action="" method="post">
						<input type="hidden" name="settings-updated" value="Y" />
						<h2><?php esc_html_e( 'Security settings', 'Addition Recovery' ); ?></h2>
						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row"><?php esc_html_e( 'Token', 'Addition Recovery' ); ?></th>
									<td id="front-static-pages">
										<input type="text" name="addiction_recovery_custom_api_token" id="addiction_recovery_custom_api_token" value="<?php echo esc_attr( get_option( 'addiction_recovery_custom_api_token' ) ); ?>" style="width: 600px;" />
										<br />
										<a class="generate_token" href="#"><?php esc_html_e( 'Generate token', 'Addition Recovery' ); ?></a>
									</td>
								</tr>
							</tbody>
						</table>

						<h2><?php esc_html_e( 'Extra settings', 'Addition Recovery' ); ?></h2>

						<table class="form-table" role="presentation">
							<tbody>
								<tr>
									<th scope="row"><?php esc_html_e( 'The publishing user', 'Addition Recovery' ); ?></th>
									<td id="front-static-pages">
										<select name="addiction_recovery_main_user">
											<option><?php esc_html_e( 'Select a user', 'Addition Recovery' ); ?></option>

											<?php foreach ( $list_users as $user ) { ?>
															<option value="<?php echo $user->ID; ?>" <?php if ( get_option( 'addiction_recovery_main_user' ) == $user->ID ) { ?> selected <?php } ?>>
																<?php echo esc_html( $user->user_nicename ); ?>
															</option>
											<?php } ?>
										</select>
									</td>
								</tr>
							</tbody>

							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row"><?php esc_html_e( 'AR Web Portal Endpoint', 'Addition Recovery' ); ?></th>
										<td id="front-static-pages">
											<input type="text" name="addiction_recovery_web_portal_endpoint" id="web_portal_endpoint" value="<?php echo esc_attr( get_option( 'addiction_recovery_web_portal_endpoint' ) ); ?>" style="width: 600px;" />
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'AR Web Portal Bearer Token', 'Addition Recovery' ); ?></th>
										<td id="front-static-pages">
											<input type="text" name="addiction_recovery_web_portal_token" id="web_portal_token" value="<?php echo esc_attr( get_option( 'addiction_recovery_web_portal_token' ) ); ?>" style="width: 600px;" />
										</td>
									</tr>
								</tbody>
							</table>

							<h2><?php esc_html_e( 'AR Instagram Feed Slug', 'Addition Recovery' ); ?></h2>

							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row"><?php esc_html_e( 'Enable Instagram Feed?', 'Addition Recovery' ); ?></th>
										<td id="addiction_recovery_instagram_feed_enabled">
											<input type="checkbox" name="addiction_recovery_instagram_feed_enabled" id="addiction_recovery_instagram_feed_enabled" value="1" <?php if ( ! empty( get_option( 'addiction_recovery_instagram_feed_enabled' ) ) ) : ?> checked <?php endif; ?> />
										</td>
									</tr>
								</tbody>
							</table>

							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row"><?php esc_html_e( 'Display table of contents', 'Addition Recovery' ); ?></th>
										<td id="addiction_recovery_anchors_creator_enabled">
											<input type="checkbox" name="addiction_recovery_anchors_creator_enabled" id="addiction_recovery_anchors_creator_enabled" value="1" <?php if ( ! empty( get_option( 'addiction_recovery_anchors_creator_enabled' ) ) ) : ?> checked <?php endif; ?> />
										</td>
									</tr>
								</tbody>
							</table>
							<table class="form-table" role="presentation">
								<tbody>
									<tr>
											<th scope="row"><?php esc_html_e( 'Enable the reviewed by workflow?', 'Addition Recovery' ); ?></th>
											<td id="addiction_recovery_reviews_enabled_e">
												<input type="checkbox" name="addiction_recovery_reviews_enabled" id="addiction_recovery_reviews_enabled" value="1" <?php if ( ! empty( get_option( 'addiction_recovery_reviews_enabled' ) ) ) : ?> checked <?php endif; ?> />
											</td>
										</tr>
										<tr id="historical-review" <?php if ( empty( get_option( 'addiction_recovery_reviews_enabled' ) ) ) : ?>style="display:none;<?php endif; ?>">
											<th scope="row"><?php esc_html_e( 'Historical review date', 'Addition Recovery' ); ?></th>
											<td id="addiction_historical_review_date">
												<input type="text" name="addiction_historical_review_date" id="addiction_historical_review_date" value="<?php echo get_option( 'addiction_historical_review_date' ); ?>" placeholder="Example: 22-12-2023" />
											</td>
										</tr>
									
										<tr <?php if ( empty( get_option( 'addiction_recovery_reviews_enabled' ) ) ) : ?>style="display:none;"<?php endif; ?> id="default-reviewer-select">
											<th scope="row"><?php esc_html_e( 'Default reviewer', 'Addition Recovery' ); ?></th>
											<td id="addiction_recovery_reviews_default_author">
												<select style="width:200px;" name="addiction_recovery_reviews_default_author" id="addiction_recovery_reviews_default_author">
													<option value="">Choose a default reviewer</option>
													<?php
													$users = new WP_Query( array( 'post_type' => 'ar_reviewer_h', 'post_status' => 'publish', 'postsperpage' => -1 ) );
													if ( ! empty( $users->posts ) ) :
														foreach ( $users->posts as $user ) :
															if ( $user->ID == get_option( 'addiction_recovery_reviews_default_author' ) ) :
																?>
																												<option selected value="<?php echo $user->ID; ?>"><?php echo ( ! empty( get_post_meta( $user->ID, 'name', 1 ) ) ? get_post_meta( $user->ID, 'name', 1 ) : $user->post_title ); ?></option>
																											<?php
															else :
																?>
																												<option value="<?php echo $user->ID; ?>"><?php echo ( ! empty( get_post_meta( $user->ID, 'name', 1 ) ) ? get_post_meta( $user->ID, 'name', 1 ) : $user->post_title ); ?></option>
																									<?php
															endif;
														endforeach;
													endif;
													?>
												</select>
											</td>
										</tr>

										<script>
											const reviewWorkflowEnabled = document.querySelector('#addiction_recovery_reviews_enabled');
											if (reviewWorkflowEnabled) {
												reviewWorkflowEnabled.addEventListener('change', function(e) {
													const histReview = document.querySelector('#historical-review');
													const defaultReviewerSelect = document.querySelector('#default-reviewer-select');
													if (e.currentTarget.checked) {
														histReview.removeAttribute('style');
														defaultReviewerSelect.removeAttribute('style');
													} else {
														histReview.setAttribute('style', 'display:none;');
														defaultReviewerSelect.setAttribute('style', 'display:none;');
													}
												});
											}
										</script>
								</tbody>
							</table>

							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row"><?php esc_html_e( 'Enable a page for authors?', 'Addition Recovery' ); ?></th>
										<td id="addiction_recovery_authors_enabled">
											<input type="checkbox" name="addiction_recovery_authors_enabled" id="addiction_recovery_authors_enabled" value="1" <?php if ( ! empty( get_option( 'addiction_recovery_authors_enabled' ) ) ) : ?> checked <?php endif; ?> />
										</td>
									</tr>
								</tbody>
							</table>

							<table class="form-table" role="presentation">
								<tbody>
									<tr>
										<th scope="row"><?php esc_html_e( 'Add basic schema markup', 'Addition Recovery' ); ?></th>
										<td id="addiction_recovery_basic_markup">
											<input type="checkbox" name="addiction_recovery_basic_markup" id="addiction_recovery_basic_markup" value="1" <?php if ( ! empty( get_option( 'addiction_recovery_basic_markup' ) ) ) : ?> checked <?php endif; ?> />
										</td>
									</tr>
								</tbody>
							</table>

							<table class="form-table" role="presentation">
								<tbody>
									<tr id="feed-slug" <?php if ( empty( get_option( 'addiction_recovery_instagram_feed_enabled' ) ) ) : ?> style="display:none;" <?php endif; ?>>
										<th scope="row"><?php esc_html_e( 'Slug Instagram Page', 'Addition Recovery' ); ?></th>
										<td id="front-static-pages">
											<input type="text" name="addiction_recovery_instagram_feed_slug" id="addiction_recovery_instagram_feed_slug" value="<?php echo esc_attr( get_option( 'addiction_recovery_instagram_feed_slug' ) ); ?>" style="width: 600px;" />
										</td>
									</tr>
								</tbody>
							</table>

							<table class="form-table" role="presentation">
								<tbody>
									<tr id="reviewers-slug" <?php if ( empty( get_option( 'addiction_recovery_reviews_enabled' ) ) ) : ?> style="display:none;" <?php endif; ?>>
										<th scope="row"><?php esc_html_e( 'Slug Reviewers Page', 'Addition Recovery' ); ?></th>
										<td id="front-static-pages">
											<input type="text" name="addiction_recovery_reviewers_slug" id="addiction_recovery_reviewers_slug" value="<?php echo esc_attr( get_option( 'addiction_recovery_reviewers_slug' ) ); ?>" style="width: 600px;" />
										</td>
									</tr>
								</tbody>
							</table>

							<table class="form-table" role="presentation">
								<tbody>
									<tr id="authors-slug" <?php if ( empty( get_option( 'addiction_recovery_authors_enabled' ) ) ) : ?> style="display:none;" <?php endif; ?>>
										<th scope="row"><?php esc_html_e( 'Slug Reviewers Authors', 'Addition Recovery' ); ?></th>
										<td id="front-static-pages">
											<input type="text" name="addiction_recovery_authors_slug" id="addiction_recovery_authors_slug" value="<?php echo esc_attr( get_option( 'addiction_recovery_authors_slug' ) ); ?>" style="width: 600px;" />
										</td>
									</tr>
								</tbody>
							</table>

							<?php submit_button( 'Save Settings' ); ?>

							<div class="ref-info">
								<h2><?php esc_html_e( 'API endpoints', 'Addition Recovery' ); ?></h2>

								<p>
									<b><?php esc_html_e( 'Create/update post', 'Addition Recovery' ); ?>:</b>
									your-domain/wp-json/custom_posts/v1/create/
								</p>
								<p>
									<b><?php esc_html_e( 'Get users list', 'Addition Recovery' ); ?>:</b>
									your-domain/wp-json/custom_posts/v1/get_users/
								</p>
								<p>
									<b><?php esc_html_e( 'Get categories list', 'Addition Recovery' ); ?>:</b>
									your-domain/wp-json/custom_posts/v1/get_categories/
								</p>
							</div>

							<script type="text/javascript">
								jQuery('.generate_token').click(function() {
									var token_value = generate_token(40);
									jQuery('#addiction_recovery_custom_api_token').val(token_value);

									return false;
								});

								const addictionRecoveryFeedEnabler = document.querySelector('#addiction_recovery_instagram_feed_enabled');

								if (addictionRecoveryFeedEnabler) {
									addictionRecoveryFeedEnabler.addEventListener('click', function(e) {
										const addictionRecoveryFeedSlugRow = document.querySelector('#feed-slug');
										if (addictionRecoveryFeedSlugRow) {
											if (e.currentTarget.checked) {
												addictionRecoveryFeedSlugRow.style.display = 'block';
											} else {
												addictionRecoveryFeedSlugRow.style.display = 'none';
											}
										}
									});
								}

								const addictionRecoveryReviewersEnabler = document.querySelector('#addiction_recovery_reviews_enabled');

								if (addictionRecoveryReviewersEnabler) {
									addictionRecoveryReviewersEnabler.addEventListener('click', function(e) {
										const addictionRecoveryReviewerSlugRow = document.querySelector('#reviewers-slug');
										if (addictionRecoveryReviewerSlugRow) {
											if (e.currentTarget.checked) {
												addictionRecoveryReviewerSlugRow.style.display = 'block';
											} else {
												addictionRecoveryReviewerSlugRow.style.display = 'none';
											}
										}
									});
								}

								const addictionRecoveryAuthorsEnabler = document.querySelector('#addiction_recovery_authors_enabled');

								if (addictionRecoveryAuthorsEnabler) {
									addictionRecoveryAuthorsEnabler.addEventListener('click', function(e) {
										const addictionRecoveryAuthorsSlugRow = document.querySelector('#authors-slug');
										if (addictionRecoveryAuthorsSlugRow) {
											if (e.currentTarget.checked) {
												addictionRecoveryAuthorsSlugRow.style.display = 'block';
											} else {
												addictionRecoveryAuthorsSlugRow.style.display = 'none';
											}
										}
									});
								}

								function generate_token(length) {
									//edit the token allowed characters
									var a = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890".split("");
									var b = [];
									for (var i = 0; i < length; i++) {
										var j = (Math.random() * (a.length - 1)).toFixed(0);
										b[i] = a[j];
									}
									return b.join("");
								}
							</script>
							<?php
							if ( $changed ) :
								?>
											<input type="hidden" name="flush_permalinks" value="1">
											<script>
												const btnSub = document.querySelector('#submit');
												if (document) {
													btnSub.click();
												}
											</script>
										<?php
							endif;
							?>
					</form>
				</div>
<?php }