<?php

require_once LPC_INCLUDES . 'pick_up' . DS . 'lpc_pick_up_widget_api.php';
require_once LPC_INCLUDES . 'lpc_modal.php';
require_once LPC_PUBLIC . 'pickup' . DS . 'lpc_pickup.php';

class LpcPickupWidget extends LpcPickup {
	const BASE_URL   = 'https://ws.colissimo.fr/';
	const WEB_JS_URL = self::BASE_URL . 'widget-point-retrait/resources/js/jquery.plugin.colissimo.js';

	protected $pickUpWidgetApi;
	protected $lpcPickUpSelection;
	protected $lpcCapabilitiesPerCountry;

	public function __construct(
		LpcPickUpWidgetApi $pickUpWidgetApi = null,
		LpcPickupSelection $lpcPickUpSelection = null,
		LpcCapabilitiesPerCountry $lpcCapabilitiesPerCountry = null
	) {
		$this->pickUpWidgetApi           = LpcRegister::get('pickupWidgetApi', $pickUpWidgetApi);
		$this->lpcPickUpSelection        = LpcRegister::get('pickupSelection', $lpcPickUpSelection);
		$this->lpcCapabilitiesPerCountry = LpcRegister::get('capabilitiesPerCountry', $lpcCapabilitiesPerCountry);
	}

	public function getDependencies() {
		return ['pickupWidgetApi', 'pickupSelection', 'capabilitiesPerCountry'];
	}

	public function init() {
		$this->addWidgetOnCart();
	}

	protected function addWidgetOnCart() {
		add_action(
			'wp_enqueue_scripts',
			function () {
				wp_register_script('lpc_widgets_web_js_url', self::WEB_JS_URL, ['jquery'], '0.1', true);

				$args = [
					'pickUpSelectionUrl' => $this->lpcPickUpSelection->getAjaxUrl(),
				];
				wp_localize_script('lpc_widgets_web_js_url', 'lpcPickUpSelection', $args);
			}
		);
		LpcHelper::enqueueScript('lpc_pickup', null, plugins_url('/js/pickup.js', LPC_PUBLIC . 'init.php'), ['jquery-core']);
		LpcHelper::enqueueScript('lpc_widget', null, plugins_url('/js/pickup_widget.js', LPC_PUBLIC . 'init.php'), ['jquery-ui-autocomplete', 'lpc_widgets_web_js_url', 'lpc_pickup']);
		LpcHelper::enqueueScript('wc-backbone-modal', null, plugins_url('woocommerce/assets/js/admin/backbone-modal.min.js'), ['wp-backbone']); // we can't use module modale.js here, because of the ajax refreshes
		LpcHelper::enqueueStyle('lpc_pickup_widget', null, plugins_url('/css/pickup_widget.css', LPC_PUBLIC . 'init.php'));

		add_action('woocommerce_after_shipping_rate', array($this, 'showWidgetInHooks'));
	}

	public function showWidgetInHooks($method, $index = 0) {
		if ($this->getMode($method->method_id) !== self::WIDGET) {
			return;
		}

		$WcSession = WC()->session;
		$customer  = $WcSession->customer;

		$availableCountries = $this->getWidgetListCountry();
		if (empty($availableCountries)) {
			$availableCountries = array('FR');
		}

		$widgetInfo = wp_json_encode(
			array(
				'ceCountryList'     => implode(',', $availableCountries),
				'ceLang'            => defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'FR',
				'ceAddress'         => $customer['shipping_address'],
				'ceZipCode'         => $customer['shipping_postcode'],
				'ceTown'            => $customer['shipping_city'],
				'ceCountry'         => $customer['shipping_country'],
				'URLColissimo'      => self::BASE_URL,
				'token'             => $this->pickUpWidgetApi->authenticate(),
				'dyPreparationTime' => LpcHelper::get_option('lpc_preparation_time', 1),
			)
		);

		$currentRelay = $this->lpcPickUpSelection->getCurrentPickUpLocationInfo();

		$lpcAddressTextColor = null;
		$lpcListTextColor    = null;
		$lpcWidgetFont       = null;
		if (LpcHelper::get_option('lpc_prCustomizeWidget', 'no') == 'yes') {
			$lpcAddressTextColor = LpcHelper::get_option('lpc_prAddressTextColor', null);
			$lpcListTextColor    = LpcHelper::get_option('lpc_prListTextColor', null);
			$lpcWidgetFont       = LpcHelper::get_option('lpc_prDisplayFont', null);
		}

		$modalContent = '<div id="lpc_widget_container"></div>';
		$modal        = new LpcModal($modalContent, __('Choose a PickUp point', 'wc_colissimo'), 'lpc_pick_up_widget_container');

		include LPC_PUBLIC . '/partials/pick_up/widget.php';
	}

	/**
	 * Get list of enabled countries for relay method
	 *
	 * @return array
	 */
	public function getWidgetListCountry() {
		// Get theoric countries available for relay method
		$countriesOfMethod = $this->lpcCapabilitiesPerCountry->getCountriesForMethod(LpcRelay::ID);

		// Get zones where relay method is enabled in configuration
		$allZones               = WC_Shipping_Zones::get_zones();
		$zonesWithMethodEnabled = array();
		foreach ($allZones as $oneZone) {
			foreach ($oneZone['shipping_methods'] as $oneMethod) {
				if (LpcRelay::ID === $oneMethod->id && 'yes' === $oneMethod->enabled) {
					$zonesWithMethodEnabled[$oneZone['id']] = 1;
					break;
				}
			}
		}
		$zoneIds = array_keys($zonesWithMethodEnabled);

		// Get country codes from both
		$countries = array();
		foreach ($zoneIds as $oneZone) {
			$currentZone = new WC_Shipping_Zone($oneZone);
			$zoneLoc     = $currentZone->get_zone_locations();
			foreach ($zoneLoc as $oneLoc) {
				if ('country' === $oneLoc->type && in_array($oneLoc->code, $countriesOfMethod)) {
					$countries[] = $oneLoc->code;
				}
			}
		}

		return $countries;
	}
}
