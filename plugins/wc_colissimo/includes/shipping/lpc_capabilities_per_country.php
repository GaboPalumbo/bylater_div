<?php

require_once LPC_PUBLIC . 'pickup' . DS . 'lpc_pickup_selection.php';

class LpcCapabilitiesPerCountry extends LpcComponent {
	const PATH_TO_COUNTRIES_PER_ZONE_JSON_FILE = LPC_FOLDER . 'resources' . DS . 'capabilitiesByCountry.json';

	private $countriesPerZone;
	private $capabilitiesByCountry;
	private $shippingMethods;

	public function __construct(LpcShippingMethods $shippingMethods = null) {
		$this->shippingMethods = LpcRegister::get('shippingMethods', $shippingMethods);
	}

	public function getDependencies() {
		return ['shippingMethods'];
	}

	public function init() {
		// only at plugin installation
		register_activation_hook(
			'wc_colissimo/index.php',
			function () {
				$this->saveCapabilitiesPerCountryInDatabase();
			}
		);
	}

	public function saveCapabilitiesPerCountryInDatabase() {
		update_option('lpc_capabilities_per_country', $this->getCountriesPerZone(), false);
	}

	public function getCapabilitiesPerCountry() {
		return get_option('lpc_capabilities_per_country');
	}

	protected function getCountriesPerZone() {
		if (null === $this->countriesPerZone) {
			$this->countriesPerZone = json_decode(
				file_get_contents(self::PATH_TO_COUNTRIES_PER_ZONE_JSON_FILE),
				true
			);
		}

		return $this->countriesPerZone;
	}

	public function getCapabilitiesForCountry($countryCode) {
		if (null === $this->capabilitiesByCountry) {
			foreach ($this->getCountriesPerZone() as $zoneId => $zone) {
				foreach ($zone['countries'] as $countryId => $countryCapabilities) {
					$this->capabilitiesByCountry[$countryId] = array_merge(
						['zone' => $zoneId],
						$countryCapabilities
					);
				}
			}
		}

		return $this->capabilitiesByCountry[$countryCode];
	}

	public function getProductCodeForOrder(WC_Order $order) {
		$countryCode = $order->get_shipping_country();
		$shippingMethod = $this->shippingMethods->getColissimoShippingMethodOfOrder($order);

		$productCode = $this->getInfoForDestination($countryCode, $shippingMethod);

		if (true === $productCode) {
			switch ($shippingMethod) {
				case 'lpc_relay':
					return get_post_meta($order->get_id(), LpcPickupSelection::PICKUP_PRODUCT_CODE_META_KEY, true);
				case 'lpc_expert':
					return 'COLI';
			}
		}

		return $productCode;
	}

	public function getIsCn23RequiredForDestination($countryCode) {
		return $this->getInfoForDestination($countryCode, 'cn23');
	}

	public function getFtdRequiredForDestination($countryCode) {
		return $this->getInfoForDestination($countryCode, 'ftd');
	}

	public function getReturnProductCodeForDestination($countryCode) {
		return $this->getInfoForDestination($countryCode, 'return');
	}

	public function getInfoForDestination($countryCode, $info) {
		$productInfo = $this->getCapabilitiesForCountry($countryCode);

		return $productInfo[$info];
	}

	/**
	 * Get all countries available for a delivery method
	 *
	 * @param $method
	 *
	 * @return array
	 */
	public function getCountriesForMethod($method) {
		$countriesOfMethod = array();
		$countriesPerZone = $this->getCountriesPerZone();

		foreach ($countriesPerZone as &$oneZone) {
			foreach ($oneZone['countries'] as $countryCode => &$oneCountry) {
				if (false !== $oneCountry[$method]) {
					$countriesOfMethod[] = $countryCode;
				}
			}
		}

		return $countriesOfMethod;
	}
}
