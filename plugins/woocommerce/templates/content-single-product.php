<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.6.0
 */
defined('ABSPATH') || exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked wc_print_notices - 10
 */
do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form(); // WPCS: XSS ok.
    return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>

    <div class="product_accordeon">
        <?php
        $fields = get_field_objects();
        if ($fields):
            ?>
            <div id="accordion">
                <?php foreach ($fields as $field): ?>
                    <?php
                    if ($field['parent'] === 29120):
                        ?>
                        <h3><?php
                            $fruits_ar = explode('/', $field['label']);
// view result using var_dump
                            echo $fruits_ar[0];
                            ?></h3>
                        <div><?php echo $field['value']; ?></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php
    /**
     * Hook: woocommerce_before_single_product_summary.
     *
     * @hooked woocommerce_show_product_sale_flash - 10
     * @hooked woocommerce_show_product_images - 20
     */
    do_action('woocommerce_before_single_product_summary');
    ?>
    <div class="summary_right">
        <div class="summary entry-summary">
            <?php the_title('<div>', '</div>'); ?>
            <div class="product_price">
                <?php
                $p = $product->get_price();
                echo wc_price($p);
                ?>
            </div>
            <ul class="product-variations">
                <li class="selected-variation"> <div style="background: url(<?php echo $fields['fabric']['value'] ?>); background-position: center; background-size: contain;"></div></li>
            <?php foreach ($fields as $field): 
                if ($field['parent'] === 29548):
                    $related = $field['value'];
                    foreach ($related as $variation): 
                        $fabrci = get_field_objects($variation);
                         if ($fabrci['fabric']['value'][0] != '') {
                    ?>
                
                <li><a href="<?php echo get_page_link($variation); ?>" style="background: url(<?php echo $fabrci['fabric']['value'] ?>); background-position: center; background-size: contain;"></a></li>
                <?php  }
                endforeach;
                endif;
                endforeach; 
               ?>
            </ul>
            <?php
            $sizes = $product->get_attribute('size');
            $sizes = explode(", ", $sizes);
            ?>
            <?php if ($sizes[0] != '') { // if product sizes are available     ?>
                <ul class="product-size">
                    <?php foreach ($sizes as $size) { ?>
                        <li class="option_product"><?php echo $size; ?></li>
                        <?php } ?>
                </ul>
            <?php } ?>
            <?php
            /**
             * Hook: woocommerce_single_product_summary.
             *
             * @hooked woocommerce_template_single_title - 5
             * @hooked woocommerce_template_single_rating - 10
             * @hooked woocommerce_template_single_price - 10
             * @hooked woocommerce_template_single_excerpt - 20
             * @hooked woocommerce_template_single_add_to_cart - 30
             * @hooked woocommerce_template_single_meta - 40
             * @hooked woocommerce_template_single_sharing - 50
             * @hooked WC_Structured_Data::generate_product_data() - 60
            */
           do_action('woocommerce_single_product_summary');
            
            ?>

        </div>
    </div>
    <?php
    /**
     * Hook: woocommerce_after_single_product_summary.
     *
     * @hooked woocommerce_output_product_data_tabs - 10
     * @hooked woocommerce_upsell_display - 15
     * @hooked woocommerce_output_related_products - 20
     */
    do_action('woocommerce_after_single_product_summary');
    ?>


</div>
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
    $(function () {
        $("#accordion").accordion({
            collapsible: true,
            animate: 200,
            heightStyle: "content"
        });

        $(".option_product:first-child").addClass('selected');
        selectSize($(".option_product:first-child").html());

        $(".option_product").click(function () {

            $(".option_product").removeClass('selected');
            $(this).addClass('selected');
            var text = $(this).html();
            selectSize(text);

        }

        );


    });
    function selectSize(text) {
        var element = $('#pa_size  option').filter(function (i, e) {
            return $(e).text() == text
        });
        $(element).attr('selected', 'selected');

        $('#pa_size').trigger('change');
    }
</script>
<?php do_action('woocommerce_after_single_product'); ?>

