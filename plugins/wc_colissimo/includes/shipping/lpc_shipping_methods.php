<?php

class LpcShippingMethods extends LpcComponent {

	public function init() {
		add_action('woocommerce_shipping_init', function () {
			require_once LPC_INCLUDES . 'shipping' . DS . 'lpc_expert.php';
			require_once LPC_INCLUDES . 'shipping' . DS . 'lpc_nosign.php';
			require_once LPC_INCLUDES . 'shipping' . DS . 'lpc_relay.php';
			require_once LPC_INCLUDES . 'shipping' . DS . 'lpc_sign.php';
		});

		add_filter('woocommerce_shipping_methods', function ($shippingMethods) {
			if(class_exists('LpcExpert')){
				$shippingMethods[LpcExpert::ID] = LpcExpert::class;
			} else {
				$shippingMethods['lpc_expert'] = 'LpcExpert';
			}

			if(class_exists('LpcNoSign')){
				$shippingMethods[LpcNoSign::ID] = LpcNoSign::class;
			} else {
				$shippingMethods['lpc_nosign'] = 'LpcNoSign';
			}

			if(class_exists('LpcRelay')){
				$shippingMethods[LpcRelay::ID] = LpcRelay::class;
			} else {
				$shippingMethods['lpc_relay'] = 'LpcRelay';
			}

			if(class_exists('LpcSign')){
				$shippingMethods[LpcSign::ID] = LpcSign::class;
			} else {
				$shippingMethods['lpc_sign'] = 'LpcSign';
			}

			return $shippingMethods;
		});
	}

	public function getAllShippingMethods() {
		// can't use ::ID here because WC may not yet be defined
		return array(
			'lpc_expert',
			'lpc_nosign',
			'lpc_relay',
			'lpc_sign',
		);
	}

	public function getAllColissimoShippingMethodsOfOrder(WC_Order $order) {
		$shipping_methods  = $order->get_shipping_methods();
		$shippingMethodIds = array_map(function (WC_Order_item_Shipping $v) {
			return ($v->get_method_id());
		}, $shipping_methods);

		return array_intersect($this->getAllShippingMethods(), $shippingMethodIds);
	}

	public function getColissimoShippingMethodOfOrder(WC_Order $order) {
		$shippingMethod = $this->getAllColissimoShippingMethodsOfOrder($order);
		return reset($shippingMethod);
	}

}
