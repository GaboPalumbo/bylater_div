<?php
/* Template Name: divi 
 * Contains the front page.
 *  */
?>

<?php get_header(); ?> 
<div class="wrap">
    <div id="primary" class="content-area extra_page">
        <header class="entry-header">
                <div class="product_nav"><?php the_title( '<span>', '</span>' ); ?>
	</header>
        <div class="extra-title">
            <?php the_title(); ?>
        </div>
        <div class="entry-content">
            <?php the_content(); ?>
        </div>
    </div><!-- .content-area -->
</div>
<?php get_footer(); ?>
