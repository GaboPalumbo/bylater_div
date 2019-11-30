<?php
$i        = $args['i'];
$oneRelay = $args['oneRelay'];
?>

<div
		class="lpc_layer_relay"
		id="lpc_layer_relay_<?= $i; ?>"
		data-relayindex="<?= $i; ?>"
		data-lpc-relay-id="<?= $oneRelay->identifiant; ?>"
		data-lpc-relay-country_code="<?= $oneRelay->codePays; ?>"
		data-lpc-relay-latitude="<?= $oneRelay->coordGeolocalisationLatitude; ?>"
		data-lpc-relay-longitude="<?= $oneRelay->coordGeolocalisationLongitude; ?>">
	<div class="lpc_layer_relay_name"><?= $oneRelay->nom ?></div>
	<div class="lpc_layer_relay_address">
		<span class="lpc_layer_relay_type"><?= $oneRelay->typeDePoint; ?></span>
		<span class="lpc_layer_relay_id"><?= $oneRelay->identifiant; ?></span>
		<span class="lpc_layer_relay_address_street"><?= $oneRelay->adresse1; ?></span>
		<span class="lpc_layer_relay_address_zipcode"><?= $oneRelay->codePostal; ?></span>
		<span class="lpc_layer_relay_address_city"><?= $oneRelay->localite; ?></span>
		<span class="lpc_layer_relay_address_country"><?= $oneRelay->libellePays; ?></span>
		<span class="lpc_layer_relay_latitude"><?= $oneRelay->coordGeolocalisationLatitude; ?></span>
		<span class="lpc_layer_relay_longitude"><?= $oneRelay->coordGeolocalisationLongitude; ?></span>
		<div>
			<a href="#" class="lpc_show_relay_details"><?= __('Display', 'wc_colissimo') ?></a>
		</div>
		<div class="lpc_layer_relay_schedule">
			<table cellpadding="0" cellspacing="0">
				<?php
				foreach ($args['openingDays'] as $day => $oneDay) {
					if ('00:00-00:00 00:00-00:00' == $oneRelay->$oneDay) {
						continue;
					}
					?>

					<tr>
						<td><?= __($day); ?></td>
						<td class="opening_hours"><?= str_replace([' ', ' - 00:00-00:00'], [' - ', ''], $oneRelay->$oneDay); ?></td>
					</tr>

					<?php
				}
				?>
			</table>
		</div>
		<div class="lpc_layer_relay_distance"><?= __('At', 'wc_colissimo') . ' ' . $oneRelay->distanceEnMetre; ?> m
		</div>
	</div>
	<div class="lpc_relay_choose_btn">
		<button class="lpc_relay_choose" type="button" data-relayindex="<?= $i; ?>"><?= __('Choose', 'wc_colissimo'); ?></button>
	</div>
</div>

<?php if (($i + 1) < $args['relaysNb']) { ?>
	<hr class="lpc_relay_separator">
<?php } ?>
