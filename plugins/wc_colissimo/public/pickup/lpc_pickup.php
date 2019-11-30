<?php

abstract class LpcPickup extends LpcComponent {
	const WEB_SERVICE = 'web_service';
	const WIDGET = 'widget';

	protected function getMode($methodId) {

		if ('lpc_relay' !== $methodId) {
			return;
		}

		$WcSession = WC()->session;

		// Add the pick up selection button only when this shipping method is selected
		if (!in_array('lpc_relay', $WcSession->chosen_shipping_methods)) {
			return;
		}

		if ('yes' === LpcHelper::get_option('lpc_prUseWebService', 'no')) {
			return self::WEB_SERVICE;
		} else {
			return self::WIDGET;
		}
	}
}
