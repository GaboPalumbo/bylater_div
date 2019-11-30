<?php
do_action('woocommerce_email_header', $email_heading, $email); ?>
	<p><?php printf(__('Hi %s. '), $order->get_billing_first_name()); ?></p>
	<p><?php printf(__('Your order #%s is being prepared and will soon be taken care of for shipping.', 'wc_colissimo'), $order->get_order_number()); ?></p>
<?php
$begining = __('You can follow up your order', 'wc_colissimo');
$linkText = __('here', 'wc_colissimo');
?>
	<p><?php printf('%s <a href="%s" target="_blank"> %s </a>', $begining, get_site_url() . $tracking_link, $linkText); ?> </p>
<?php
do_action('woocommerce_email_footer', $email);
