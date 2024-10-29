<?php

if (!defined('ABSPATH')) {
    exit;
}
global $post;

$name = get_post_meta($post->ID, 'name', 1);
$title = get_post_meta($post->ID, 'title', 1);
$description = get_post_meta($post->ID, 'bio', 1);
$credentials = get_post_meta($post->ID, 'credentials', 1);
$link_twitter = get_post_meta($post->ID, 'twitter', 1);
$link_facebook = get_post_meta($post->ID, 'facebook', 1);
$link_instagram = get_post_meta($post->ID, 'instagram', 1);
$link_linkedin = get_post_meta($post->ID, 'linkedin', 1);
$image = get_the_post_thumbnail_url($post->ID, 'full');

get_header();
?>
<div class="ar-reviewers-wrapper">

    <div class="ar-reviewer-page">
        <img class="ar-reviewer-image--lg" src="<?php echo $image; ?>" alt="Author Profile Image">
        <h1 class="ar-reviewer-name"><?php echo $name; ?></h1>
        <h2 class="ar-reviewer-title"><?php echo $title; ?></h2>
        <p class="ar-reviewer-description">
            <?php echo $description; ?>
        </p>
        <label class="ar-reviewer-credentials--label">Credentials:</label>
        <p class="ar-reviewer-credentials">
            <?php echo $credentials; ?>
        </p>
        <div class="ar-reviewer-social">
            <a href="<?php echo $link_twitter; ?>" class="ar-reviewer-social-link"><i class="fab fa-twitter"></i></a>
            <a href="<?php echo $link_facebook; ?>" class="ar-reviewer-social-link"><i class="fab fa-facebook"></i></a>
            <a href="<?php echo $link_instagram; ?>" class="ar-reviewer-social-link"><i class="fab fa-instagram"></i></a>
            <a href="<?php echo $link_linkedin; ?>" class="ar-reviewer-social-link"><i class="fab fa-linkedin"></i></a>
        </div>
    </div>

</div>
<?php
get_footer();
