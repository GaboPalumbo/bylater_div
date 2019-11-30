<script type="text/javascript">
    window.lpc_widget_info = <?= $widgetInfo ?>;
</script>

<style type="text/css">
	<?php if (!empty($lpcAddressTextColor)) { ?>
	#lpc_widget_container div#colissimo-container .couleur1 {
		color: <?= $lpcAddressTextColor ?>;
	}

	<?php } ?>

	<?php if (!empty($lpcListTextColor)) { ?>
	#lpc_widget_container div#colissimo-container .couleur2 {
		color: <?= $lpcListTextColor ?>
	}

	<?php } ?>

	<?php if (!empty($lpcListTextColor)) { ?>
	#lpc_widget_container div#colissimo-container .police {
		font-family: <?= $lpcWidgetFont ?>
	}

	<?php } ?>
</style>


<?php $modal->echo_modal(); ?>


<?php if (is_checkout()) { ?>
	<div id="lpc_layer_error_message"></div>
	<?= LpcHelper::renderPartial('pick_up' . DS . 'pick_up_info.php', ['relay' => $currentRelay]); ?>
	<div>
		<?php
		if (!empty($currentRelay)) {
			$linkText = __('Change PickUp point', 'wc_colissimo');
		} else {
			$linkText = __('Choose PickUp point', 'wc_colissimo');
		}
		?>
		<a id="lpc_pick_up_widget_show_map" class="lpc_pick_up_widget_show_map"><?= $linkText ?></a>
	</div>
<?php } ?>
