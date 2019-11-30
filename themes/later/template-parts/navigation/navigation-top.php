<?php
/**
 * Displays top navigation
 *
 * @package WordPress
 * @subpackage Later
 * @since 1.0
 * @version 1.2
 */
?>
<nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e('Top Menu', 'twentyseventeen'); ?>">
    <button class="menu-toggle" aria-controls="top-menu" aria-expanded="false">
        <?php
        echo twentyseventeen_get_svg(array('icon' => 'bars'));
        echo twentyseventeen_get_svg(array('icon' => 'close'));
        _e('Menu', 'twentyseventeen');
        ?>
    </button>

    <?php
    wp_nav_menu(
            array(
                'theme_location' => 'top',
                'menu_id' => 'top-menu',
            )
    );
    ?>
    <div class="languages_cont">
        <?php // outputs a list of languages names  ?>
        <ul><?php pll_the_languages(array( 'display_names_as' => 'slug','show_names' => 0 ) ); ?></ul>
    </div>
</nav><!-- #site-navigation -->
