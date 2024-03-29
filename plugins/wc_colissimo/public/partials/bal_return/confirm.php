<div class="lpc_balreturn">
	<h1 class="entry-title"><?= esc_html__('MailBox picking return', 'wc_colissimo'); ?></h1>

	<h2 class="lpc_balreturn_subtitle"><?= esc_html__('Retrieval address', 'wc_colissimo'); ?></h2>
	<div class="lpc_balreturn_shipping lpc_balreturn_withseparator">
		<div>
			<?= esc_html__('Address from which the return will be from:', 'wc_colissimo'); ?>
		</div>
		<div class="lpc_balreturn_shipping_address">
			<?= WC()->countries->get_formatted_address($args['addressDisplay']); ?>
		</div>
	</div>
	<div>
		<?php
		if ($args['pickupConfirmation']) {
			?>
			<div>
				<b><?= esc_html__('Your PickUp has been confirmed.', 'wc_colissimo'); ?></b>
			</div>
			<div>
				<?= esc_html__('Your return tracking number is:', 'wc_colissimo'); ?>
				<?= $args['returnTrackingNumber']; ?>
			</div>
			<?php
		} else {
			?>
			<p class="lpc_balreturn_error"><b><?= esc_html__('An error occured while confirming the mailBox pick-up.', 'wc_colissimo'); ?></b></p>
			<?php
		}
		?>
	</div>
</div>
