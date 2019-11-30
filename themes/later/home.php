<?php
/* Template Name: Home 
 * Contains the front page.
 *  */
?>

<?php get_header(); ?> 
<div class="home_image">
    <?php
    $image = get_field('image');
    $image_mobile = get_field('image_mobile');
    ?>
    <img class="nomobile" src="<?php echo $image['url']; ?>" alt="<?php echo $image['alt']; ?>" />
    <img class="mobile" src="<?php echo $image_mobile['url']; ?>" alt="<?php echo $image_mobile['alt']; ?>" />
</div>
<div class="wrap wrap_home">
    <div id="primary" class="content-area">
        <main id="main" class="site-main main_home" role="main">
            <?php
            $args = array('numberposts' => 3, 'order' => 'ASC', 'post_type' => 'front_new');
            $postslist = get_posts($args);
            foreach ($postslist as $post) : setup_postdata($post);
                $link = get_field('link');
                ?>
                <a href="<?php echo $link; ?>" class="news_block">
                    <div class="new_image">
                        <?php the_post_thumbnail(); ?>
                    </div>
                    <div class="new_title">
                        <?php the_title(); ?>
                    </div>
                    <div class="new_extract">
                        <?php the_excerpt(); ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </main><!-- .site-main -->

    </div><!-- .content-area -->
</div>
<?php get_footer(); ?>


