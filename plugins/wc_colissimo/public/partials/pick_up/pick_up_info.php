<?php $relay = $args['relay']; ?>
<blockquote id="lpc_pick_up_info" data-pickup-id="<?= esc_attr($relay['identifiant']); ?>">
<?php if (!empty($relay)) { ?>
	<?= esc_html($relay['nom']); ?><br />
	<?= esc_html($relay['adresse1']); ?><br />
	<?= esc_html($relay['codePostal']); ?> <?= esc_html($relay['localite']); ?><br />
	<?= esc_html($relay['libellePays']); ?>
<?php } ?>
</blockquote>
