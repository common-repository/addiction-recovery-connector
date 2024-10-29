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
            <?php
            if (!empty($link_twitter)) :
            ?>
                <a href="<?php echo $link_twitter; ?>" class="ar-reviewer-social-link"><i class="fab fa-twitter"></i></a>
            <?php
            endif;
            ?>
            <?php
            if (!empty($link_facebook)) :
            ?>
                <a href="<?php echo $link_facebook; ?>" class="ar-reviewer-social-link"><i class="fab fa-facebook"></i></a>
            <?php
            endif;
            ?>
            <?php
            if (!empty($link_instagram)) :
            ?>
                <a href="<?php echo $link_instagram; ?>" class="ar-reviewer-social-link"><i class="fab fa-instagram"></i></a>
            <?php
            endif;
            ?>
            <?php
            if (!empty($link_linkedin)) :
            ?>
                <a href="<?php echo $link_linkedin; ?>" class="ar-reviewer-social-link"><i class="fab fa-linkedin"></i></a>
            <?php
            endif;
            ?>
        </div>
    </div>
    <div class="other-reviewers">
        <h2 class="ar-reviewers-list-main-heading">Clinical Reviewers</h2>
        <?php
        $reviewers_all = new WP_Query(
            array(
                'post_type' => 'ar_reviewer_h',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'post__not_in' => array($post->ID),
                'order' => 'DESC',
                'orderby' => 'date'
            )
        );

        $html = '<div class="ar-reviewers-list-feed-wrapper" style="">';

        if (!empty($reviewers_all->posts)) {
            foreach ($reviewers_all->posts as $reviewer) {
                $name = get_post_meta($reviewer->ID, 'name', 1);
                $title = get_post_meta($reviewer->ID, 'title', 1);
                $description = get_post_meta($reviewer->ID, 'bio', 1);
                if (strlen($description) > 250) {
                    $description = substr($description, 0, 250) . '...';
                }
                $link = get_permalink($reviewer->ID);
                $link_twitter = get_post_meta($reviewer->ID, 'twitter', 1);
                $link_facebook = get_post_meta($reviewer->ID, 'facebook', 1);
                $link_instagram = get_post_meta($reviewer->ID, 'instagram', 1);
                $link_linkedin = get_post_meta($reviewer->ID, 'linkedin', 1);
                $image = get_the_post_thumbnail_url($reviewer->ID, 'full');

                $html .= '<div class="ar-reviewers-list-entry-1"><span class="ar-reviewers-list--reviewer-card">';

                $html .= '<a href="' . $link . '" class="ar-reviewers-list-image-wrapper"><img class="ar-reviewers-list-image lazy" data-original="' . $image . '"></a><a href="' . $link . '" class="ar-reviewers-list-heading">' . $name . '</a><h4 class="ar-reviewers-list-subheading">' . $title . '</h4><p class="ar-reviewer-description">' . $description . '</p><span class="ar-reviewer-social">';

                if (!empty($link_twitter)) {
                    $html .= '<a href="' . $link_twitter . '" class="ar-reviewer-social-link"><i class="fab fa-twitter"></i></a>';
                }
                if (!empty($link_facebook)) {
                    $html .= '<a href="' . $link_facebook . '" class="ar-reviewer-social-link"><i class="fab fa-facebook"></i></a>';
                }
                if (!empty($link_instagram)) {
                    $html .= '<a href="' . $link_instagram . '" class="ar-reviewer-social-link"><i class="fab fa-instagram"></i></a>';
                }
                if (!empty($link_linkedin)) {
                    $html .= '<a href="' . $link_linkedin . '" class="ar-reviewer-social-link"><i class="fab fa-linkedin"></i></a>';
                }

                $html .= '</span></span></div>';
            }
        }

        $html .= '</div>';

        echo $html;
        ?>
    </div>
</div>
<?php
get_footer();
