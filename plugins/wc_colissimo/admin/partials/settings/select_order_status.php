<?php
$id_and_name     = $args['id_and_name'];
$label           = $args['label'];
$multiple        = $args['multiple'];
$selected_values = ($args['selected_values']) ? $args['selected_values'] : array();
$order_statuses  = $args['order_statuses'];
?>
<tr valign="top">
	<th scope="row">
		<label for="<?= esc_attr_e($id_and_name); ?>"><?php esc_html_e($label, 'wc_colissimo'); ?></label>
	</th>
	<td>
		<select <?= $multiple; ?> name="<?= $id_and_name . (('multiple' === $multiple) ? '[]' : ''); ?>" id="<?= $id_and_name; ?>" style="height:100%;">
			<?php
			$order_statuses = array(LpcOrderStatuses::WC_LPC_NO_CHANGE => __(LpcOrderStatuses::WC_LPC_NO_CHANGE_LABEL, 'wc_colissimo')) + $order_statuses;
			foreach (
				$order_statuses

				as $name => $label
			) { ?>
				<option value="<?= esc_attr($name); ?>"
					<?= (('multiple' === $multiple && in_array($name, $selected_values)) || (('' === $multiple && $name === $selected_values))) ? 'selected' : ''; ?>><?= esc_attr($label); ?>
				</option>
			<?php } ?>
		</select>
	</td>
</tr>

