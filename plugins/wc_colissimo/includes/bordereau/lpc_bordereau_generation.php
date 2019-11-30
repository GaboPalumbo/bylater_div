<?php

class LpcBordereauGeneration extends LpcComponent {
	const MAX_LABEL_PER_BORDEREAU = 50;
	const BORDEREAU_ID_META_KEY   = 'lpc_bordereau_id';

	protected $bordereauGenerationApi;

	public function __construct(LpcBordereauGenerationApi $bordereauGenerationApi = null) {
		$this->bordereauGenerationApi = LpcRegister::get('bordereauGenerationApi', $bordereauGenerationApi);
	}

	public function getDependencies() {
		return ['bordereauGenerationApi'];
	}

	/**
	 * @param WC_Order[] $orders
	 * @return string|null Return the bodereau if only one bodereau was generated, else null.
	 */
	public function generate(array $orders) {
		$ordersWithTrackingNumbers = array_filter($orders, function (WC_Order $order) {
			return !empty($order->get_meta(LpcLabelGenerationOutward::OUTWARD_PARCEL_NUMBER_META_KEY));
		});

		$ordersPerBatch = $this->prepareBatch($ordersWithTrackingNumbers);

		foreach ($ordersPerBatch as $batchOfOrders) {
			$batchOfParcelNumbers = array_map(function (WC_Order $order) {
				return $order->get_meta(LpcLabelGenerationOutward::OUTWARD_PARCEL_NUMBER_META_KEY);
			}, $batchOfOrders);

			$retrievedBordereau = $this->bordereauGenerationApi->generateBordereau($batchOfParcelNumbers);

			$bordereau   = $retrievedBordereau->bordereau;
			$bordereauId = $bordereau->bordereauHeader->bordereauNumber;

			$newStatus = LpcHelper::get_option('lpc_order_status_on_bordereau_generated');

			foreach ($batchOfOrders as $order) {
				update_post_meta($order->get_id(), self::BORDEREAU_ID_META_KEY, $bordereauId);
				if (!empty($newStatus)) {
					$order->update_status($newStatus);
				}
			}
		}

		if (1 === count($ordersPerBatch)) {
			// when only 1 bordereau is generated, we return it
			return $this->bordereauGenerationApi->getBordereauByNumber($bordereauId)->bordereau;
		}
	}

	protected function prepareBatch(array $parcelNumbers) {
		return array_chunk($parcelNumbers, self::MAX_LABEL_PER_BORDEREAU);
	}
}
