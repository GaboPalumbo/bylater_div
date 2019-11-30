<?php
$shippingMethod = $args['shippingMethod'];
$currentRates   = $shippingMethod->get_option('shipping_rates', []);
?>
<tr valign="top">
	<th scope="row" class="titledesc"><?php esc_html_e(__('Shipping rates', 'wc_colissimo')); ?></th>
	<td class="forminp" id="<?= $shippingMethod->id; ?>_shipping_rates">
		<table class="shippingrows widefat" cellspacing="0">
			<thead>
			<tr>
				<td class="check-column"><input type="checkbox"></td>
				<?php
				if ('yes' === $shippingMethod->get_option('use_cart_price', 'no')) {
					$classWeight = 'lpc_shipping_rates_title_hide';
					$classPrice  = '';
				} else {
					$classWeight = '';
					$classPrice  = 'lpc_shipping_rates_title_hide';
				}
				$currency    = get_woocommerce_currency();
				$currencyTxt = ' (' . $currency . ' ' . get_woocommerce_currency_symbol($currency) . ')';
				$weightUnit  = ' (' . LpcHelper::get_option('woocommerce_weight_unit', '') . ')';
				?>
				<th style="text-align: center;">
					<span id="lpc_shipping_rates_title_weight" class="<?php esc_attr_e($classWeight); ?>"><?php esc_html_e(__('From weight', 'wc_colissimo') . $weightUnit); ?></span>
					<span id="lpc_shipping_rates_title_price" class="<?php esc_attr_e($classPrice); ?>"><?php esc_html_e(__('From cart price', 'wc_colissimo') . $currencyTxt); ?></span>
				</th>
				<th style="text-align: center;"><?php esc_html_e(__('Price', 'wc_colissimo')); ?></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<th colspan="3">
					<a href="#" class="add button" id="lpc_shipping_rates_add" style="margin-left: 24px"><?php esc_html_e(__('Add rate', 'wc_colissimo')); ?></a>
					<a href="#" class="remove button" id="lpc_shipping_rates_remove"><?php esc_html_e(__('Delete selected', 'wc_colissimo')); ?></a>
				</th>
			</tr>
			</tfoot>
			<tbody class="table_rates">
			<?php foreach ($currentRates as $i => $rate) { ?>
				<tr>
					<td class="check-column"><input type="checkbox"/></td>
					<td style="text-align: right;"><input value="<?= esc_attr($rate['weight']); ?>" name="shipping_rates[<?= $i; ?>][weight]"/></td>
					<td style="text-align: right;"><input value="<?= esc_attr($rate['price']); ?>" name="shipping_rates[<?= $i; ?>][price]"/></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</td>
</tr>
<script type="text/javascript">
    window.lpc_i18n_delete_selected_rate = "<?= esc_attr('Delete the selected rates?'); ?>";
</script>
