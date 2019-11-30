<?php
/*
Plugin Name: WP Post Type Template
Plugin URI: http://cybercraftit.com/product/wp-post-type-template/
Description: A plugin to set template for any post type like pages.
Version: 1.0.3
Author: CyberCraft
Author URI: http://cybercraftit.com/
License: GPLv2
Tags : custom post template, custom post type, custom template, custom template for post., custom theme template, post from template, post template, posts, Simple Post Templates, single post templates, template, templates, theme template, wordpress post template, wp custom post template, wp post template
*/

class WPPT_Init {

    public function __construct() {
        add_action('add_meta_boxes', array( $this, 'wp_add_post_custom_template' ) );
        add_action('save_post', array( $this, 'wp_save_custom_post_template' ),10,2);
        add_filter('single_template', array( $this, 'wp_load_custom_post_template' ));

        $this->includes();
    }

    function includes(){
        include_once 'cc-products-page.php';
        include_once 'news.php';
    }

    function wp_add_post_custom_template( $post_type ) {

        if( $post_type == 'page') return;

        add_meta_box(
            'postparentdiv',
            __('WP Post Template','wppt'),
            array( $this, 'wp_custom_post_template_meta_box' ),
            $post_type,
            'side',
            'core'
        );
    }

    function wp_custom_post_template_meta_box( $post ) {

        $templates = get_page_templates();
        $sel_template = get_post_meta( $post->ID, '_wp_page_template', true );
        ?>
        <p><strong>Template</strong></p>
        <label class="screen-reader-text" for="page_template">Page Template</label>
        <?php if( is_array( $templates ) ) :?>
        <select name="page_template" id="page_template">
            <option value="default">Default Template</option>
            <?php foreach( $templates as $name => $path ):?>
                <option value="<?php echo $path; ?>" <?php echo $sel_template == $path ? 'selected' : ''; ?>><?php echo $name; ?></option>
            <?php endforeach;?>
        </select>
        <?php endif; ?>
    <?php
    }

    function wp_load_custom_post_template( $single_template ) {
        global $post;
        $template_path = get_post_meta( $post->ID , '_wp_page_template', true );

        if( $template_path && $template_path != 'default' ) {
            if( $custom_template = locate_template($template_path) )
                return $custom_template;
        }

        return $single_template;
    }

    function wp_save_custom_post_template( $post_id, $post ) {

        if ( get_post_type( $post_id ) == 'page' ) return;

        if( !current_user_can( 'edit_post' ) ) return;

        $post_template = sanitize_text_field( $_POST['page_template'] );

        if ( $post->post_type !='page' && !empty( $post_template ) ) {
            update_post_meta( $post_id, '_wp_page_template', $post_template );
        }
    }

    public static function init() {
        new WPPT_Init();
    }

}

WPPT_Init::init();
