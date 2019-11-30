<?php

class LpcLabelPurge extends LpcComponent {

	protected $lpcLabelDb;

	public function __construct(LpcLabelDb $lpcLabelDb = null) {
		$this->lpcLabelDb = LpcRegister::get('labelDb', $lpcLabelDb);
	}

	public function getDependencies() {
		return ['labelDb'];
	}

	public function purgeReadyLabels() {
		$nbDays = LpcHelper::get_option('lpc_day_purge', 0);

		if ('0' == $nbDays) {
			return;
		}

		$matchingOrdersId = LpcOrderQueries::getLpcOrdersIdsForPurge();

		foreach ($matchingOrdersId as $orderId) {

			LpcLogger::debug(
				__METHOD__ . ' purge labels for',
				array(
					'orderId' => $orderId,
				)
			);

			$this->lpcLabelDb->purgeLabelsByOrderId($orderId);
		}
	}
}
