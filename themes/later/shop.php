<?php
/* Template Name: Shop 
 * Contains the front page.
 *  */
?>

<?php get_header(); ?> 
<div class="wrap">
    <div id="primary" class="content-area">
        <main id="main" class="site-main main_shop" role="main">
            <?php
            $args = array('numberposts' => 3, 'order' => 'ASC', 'post_type' => 'product');
            $postslist = get_posts($args);
            foreach ($postslist as $post) : setup_postdata($post);
                ?>
                <div class="news_block">
                    <div class="new_image">
                        <?php the_post_thumbnail(); ?>
                    </div>
                    <div class="new_title">
                        <?php the_title(); ?>
                    </div>
                    <div class="new_extract">
                        <?php the_excerpt(); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </main><!-- .site-main -->

    </div><!-- .content-area -->
</div>
<?php get_footer(); ?>
