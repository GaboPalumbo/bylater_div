<?php
echo '= ' . esc_html($email_heading) . " =\n\n";
echo sprintf(__('Hi %1$s . '), esc_html($order->get_billing_first_name())) . "\n\n";
echo sprintf(__('The inward label for order #%1$s has been generated. ', 'wc_colissimo'), esc_html($order->get_order_number())) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')));
