<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 * check later
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WordPress
 * @subpackage Later
 * @since 1.0
 * @version 1.0
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js no-svg">
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="http://gmpg.org/xfn/11">
       <link href="https://fonts.googleapis.com/css?family=Montserrat:100,300,300i,400,500,600,700,800,900&display=swap" rel="stylesheet">

        <?php wp_head(); ?>
        <link href="<?php echo get_template_directory_uri(); ?>/assets/css/header.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo get_template_directory_uri(); ?>/assets/css/footer.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo get_template_directory_uri(); ?>/assets/css/home.css" rel="stylesheet" type="text/css"/>
        <link href="<?php echo get_template_directory_uri(); ?>/assets/css/product.css" rel="stylesheet" type="text/css"/>
    </head>
    <body <?php body_class(); ?>>
        <?php wp_body_open(); ?>
        <div id="page" class="site">
            <header id="masthead" class="site-header" role="banner">
                <?php if (has_nav_menu('top')) : ?>
                    <div class="navigation-top">
                        <div class="logo"><a href="<?php echo get_home_url(); ?>"><?php
                            $custom_logo_id = get_theme_mod('custom_logo');
                            $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                            if (has_custom_logo()) {
                                echo '<img src="' . esc_url($logo[0]) . '" alt="' . get_bloginfo('name') . '">';
                            } else {
                                echo '<h1>' . get_bloginfo('name') . '</h1>';
                            }
                            ?></a></div>
                        <div class="wrap">
                            <?php get_template_part('template-parts/navigation/navigation', 'top'); ?>
                        </div>
                        <!-- .wrap -->
                    </div><!-- .navigation-top -->
                <?php endif; ?>
            </header><!-- #masthead -->
            <div class="site-content-contain">
                <div id="content" class="site-content">
