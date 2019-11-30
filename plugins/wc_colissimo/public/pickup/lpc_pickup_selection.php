<?php

class LpcPickupSelection extends LpcComponent {
	const AJAX_TASK_NAME = 'pickup_selection';

	const PICKUP_LOCATION_ID_META_KEY    = '_lpc_meta_pickUpLocationId';
	const PICKUP_LOCATION_LABEL_META_KEY = '_lpc_meta_pickUpLocationLabel';
	const PICKUP_PRODUCT_CODE_META_KEY   = '_lpc_meta_pickUpProductCode';

	const PICKUP_LOCATION_SESSION_VAR_NAME = 'lpc_pickUpInfo';

	protected $ajaxDispatcher;

	public function __construct(LpcAjax $ajaxDispatcher = null) {
		$this->ajaxDispatcher = LpcRegister::get('ajaxDispatcher', $ajaxDispatcher);
	}

	public function getDependencies() {
		return ['ajaxDispatcher'];
	}

	public function init() {
		$this->listenToPickUpSelection();
		$this->savePickUpSelectionOnOrderProcessed();
	}

	protected function listenToPickUpSelection() {
		$this->ajaxDispatcher->register(self::AJAX_TASK_NAME, array($this, 'pickUpLocationListener'));
	}

	public function pickUpLocationListener() {
		$pickUpInfo = LpcHelper::getVar(self::PICKUP_LOCATION_SESSION_VAR_NAME, null, 'array');
		WC()->session->set(self::PICKUP_LOCATION_SESSION_VAR_NAME, $pickUpInfo);

		return $this->ajaxDispatcher->makeSuccess(
			array(
				'html' => LpcHelper::renderPartial(
					'pick_up' . DS . 'pick_up_info.php',
					['relay' => $pickUpInfo]
				),
			)
		);
	}

	public function getCurrentPickUpLocationInfo() {
		return WC()->session->get(self::PICKUP_LOCATION_SESSION_VAR_NAME);
	}

	public function setCurrentPickUpLocationInfo(array $pickUpInfo) {
		WC()->session->set(self::PICKUP_LOCATION_SESSION_VAR_NAME, $pickUpInfo);

		return $this;
	}

	public function getAjaxUrl() {
		return $this->ajaxDispatcher->getUrlForTask(self::AJAX_TASK_NAME);
	}

	public function savePickUpSelectionOnOrderProcessed() {
		add_action(
			'woocommerce_checkout_order_processed',
			function ($orderId) {
				$shippings = wc_get_order($orderId)->get_shipping_methods();
				$shipping  = current($shippings);
				if (!empty($shipping)) {
					$shippingMethod = $shipping->get_method_id();
					if (LpcRelay::ID == $shippingMethod) {
						$pickUpInfo = $this->getCurrentPickUpLocationInfo();

						update_post_meta($orderId, self::PICKUP_LOCATION_ID_META_KEY, $pickUpInfo['identifiant']);
						update_post_meta($orderId, self::PICKUP_LOCATION_LABEL_META_KEY, $pickUpInfo['nom']);
						update_post_meta($orderId, self::PICKUP_PRODUCT_CODE_META_KEY, $pickUpInfo['typeDePoint']);
						WC()->session->set(self::PICKUP_LOCATION_SESSION_VAR_NAME, null);
					}
				}
			}
		);
	}
}
