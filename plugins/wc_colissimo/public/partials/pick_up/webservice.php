<?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
<script src="https://maps.googleapis.com/maps/api/js?key=<?= $args['apiKey']; ?>" async defer></script>

<div>
	<?php
	if (is_checkout()) {
		echo LpcHelper::renderPartial('pick_up' . DS . 'pick_up_info.php', ['relay' => $args['currentRelay']]);
		?>
		<div>
			<?php
			if (!empty($args['currentRelay'])) {
				$linkText = __('Change PickUp point', 'wc_colissimo');
			} else {
				$linkText = __('Choose PickUp point', 'wc_colissimo');
			}
			?>
			<a id="lpc_pick_up_web_service_show_map" data-lpc-template="lpc_pick_up_web_service" data-lpc-callback="lpcInitMapWebService"><?= $linkText ?></a>
		</div>
	<?php } ?>

	<?php $args['modal']->echo_modal(); ?>
</div>
