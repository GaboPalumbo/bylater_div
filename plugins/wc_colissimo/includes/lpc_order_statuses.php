<?php


class LpcOrderStatuses extends LpcComponent {
	const WC_LPC_TRANSIT = 'wc-lpc_transit';
	const WC_LPC_DELIVERED = 'wc-lpc_delivered';
	const WC_LPC_ANOMALY = 'wc-lpc_anomaly';
	const WC_LPC_READY_TO_SHIP = 'wc-lpc_ready_to_ship';

	const WC_LPC_NO_CHANGE = 'lpc_no_change';
	const WC_LPC_NO_CHANGE_LABEL = 'No change';

	const WC_LPC_UNKNOWN_STATUS_INTERNAL_CODE = -1;

	const WC_LPC_TRANSIT_LABEL = 'Colissimo In-Transit';
	const WC_LPC_DELIVERED_LABEL = 'Colissimo Delivered';
	const WC_LPC_ANOMALY_LABEL = 'Colissimo Anomaly';
	const WC_LPC_READY_TO_SHIP_LABEL = 'Colissimo Ready to ship';

	public function init() {
		add_action('init', array($this, 'register_lpc_post_statuses'));
		add_filter('wc_order_statuses', array($this, 'register_lpc_order_statuses'));
	}

	public function register_lpc_order_statuses($order_statuses) {
		$order_statuses[self::WC_LPC_TRANSIT]       = self::WC_LPC_TRANSIT_LABEL;
		$order_statuses[self::WC_LPC_DELIVERED]     = self::WC_LPC_DELIVERED_LABEL;
		$order_statuses[self::WC_LPC_ANOMALY]       = self::WC_LPC_ANOMALY_LABEL;
		$order_statuses[self::WC_LPC_READY_TO_SHIP] = self::WC_LPC_READY_TO_SHIP_LABEL;

		return $order_statuses;
	}

	public function register_lpc_post_statuses() {
		register_post_status(
			self::WC_LPC_TRANSIT,
			array(
				'label'  => self::WC_LPC_TRANSIT_LABEL,
				'public' => true,
			)
		);
		register_post_status(
			self::WC_LPC_DELIVERED,
			array(
				'label'  => self::WC_LPC_DELIVERED_LABEL,
				'public' => true,
			)
		);
		register_post_status(
			self::WC_LPC_ANOMALY,
			array(
				'label'  => self::WC_LPC_ANOMALY_LABEL,
				'public' => true,
			)
		);
		register_post_status(
			self::WC_LPC_READY_TO_SHIP,
			array(
				'label'  => self::WC_LPC_READY_TO_SHIP_LABEL,
				'public' => true,
			)
		);
	}

}
