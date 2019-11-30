<div id="lpc_layer_relays">
	<div class="content">
		<div id="lpc_search_address">
			<label id="lpc_modal_relays_search_address">
				<?= __('Address', 'wc_colissimo'); ?>
				<input type="text" class="lpc_modal_relays_search_input" value="<?= $args['ceAddress']; ?>">
			</label>
			<div id="lpc_modal_address_details">
				<label id="lpc_modal_relays_search_zipcode">
					<?= __('Zipcode', 'wc_colissimo'); ?>
					<input type="text" class="lpc_modal_relays_search_input" value="<?= $args['ceZipCode']; ?>">
				</label>
				<label id="lpc_modal_relays_search_city">
					<?= __('City', 'wc_colissimo'); ?>
					<input type="text" class="lpc_modal_relays_search_input" value="<?= $args['ceTown']; ?>">
				</label>
				<input type="hidden" id="lpc_modal_relays_country_id" value="<?= $args['ceCountryId'] ?>">
				<button id="lpc_layer_button_search" type="button"><?= __('Search', 'wc_colissimo'); ?></button>
			</div>
		</div>

		<div id="lpc_left">
			<div id="lpc_map"></div>
		</div>
		<div id="lpc_right">
			<div class="blockUI" id="lpc_layer_relays_loader" style="display: none;"></div>
			<div id="lpc_layer_error_message" style="display: none;"></div>
			<div id="lpc_layer_list_relays"></div>
		</div>
	</div>
</div>
