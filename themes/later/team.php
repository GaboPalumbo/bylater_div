<?php
/* Template Name: Team 
 * Contains the front page.
 *  */
?>

<?php get_header(); ?> 
<div class="team_image">
    <?php
    $image = get_field('image');
    ?>
    <img src="<?php echo $image['url']; ?>" alt="<?php echo $image['alt']; ?>" />
</div>
<div class="wrap wrap_home">
    <div id="primary" class="content-area">
        <header class="entry-header">
            <div class="product_nav"><?php the_title('<span>', '</span>'); ?>
        </header>
         <div class="extra-title">
            <?php the_title(); ?>
        </div>
        <div class="entry-content">
            <?php the_content(); ?>
        </div>
        <main id="main" class="site-main main_home" role="main">
            <?php
            $args = array('numberposts' => 3, 'order' => 'ASC', 'post_type' => 'team');
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
