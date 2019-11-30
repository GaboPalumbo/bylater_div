<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WordPress
 * @subpackage Later
 * @since 1.0
 * @version 1.2
 */
?>

</div><!-- #content -->
<footer id="colophon" class="site-footer" role="contentinfo">
    <div class="wrap">
        <?php
        get_template_part('template-parts/footer/footer', 'widgets');
        ?>
        <div class="extra_link">
            <?php
            $args = array('numberposts' => 20, 'order' => 'ASC', 'post_type' => 'extra_pages');
            $postslist = get_posts($args);
            foreach ($postslist as $post) : setup_postdata($post);
                ?>
            <div><a href="<?php echo get_permalink(); ?>">
                        <?php the_title(); ?>
                </a></div>
            <?php endforeach; ?>
        </div>
    </div><!-- .wrap -->
</footer><!-- #colophon -->
</div><!-- .site-content-contain -->
</div><!-- #page -->
<?php wp_footer(); ?>

</body>
</html>
