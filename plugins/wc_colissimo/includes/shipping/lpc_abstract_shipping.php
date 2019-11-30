<?php

require_once LPC_INCLUDES . 'shipping' . DS . 'lpc_capabilities_per_country.php';

abstract class LpcAbstractShipping extends WC_Shipping_Method {
	protected $lpcCapabilitiesPerCountry;

	/**
	 * LpcAbstractShipping constructor.
	 *
	 * @param int $instance_id
	 */
	public function __construct($instance_id = 0) {
		$this->instance_id = absint($instance_id);
		$this->supports    = array(
			'shipping-zones',
			'instance-settings',
		);

		$this->lpcCapabilitiesPerCountry = new LpcCapabilitiesPerCountry();
		$this->init();
	}

	/**
	 * This method is used to initialize the configuration fields' values
	 */
	protected function init() {
		// Load the settings
		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option('title');

		add_action(
			'woocommerce_settings_page_init',
			function () {
				LpcHelper::enqueueScript(
					'lpc_shipping_rates',
					plugins_url('/wc_colissimo/admin/js/shipping/lpc_shipping_rates.js'),
					null,
					['jquery-core']
				);
			}
		);
	}

	/**
	 * This method allows you to define configuration fields shown in the shipping methdod's configuration page
	 */
	public function init_form_fields() {
		$this->instance_form_fields = array(
			'title'          => array(
				'title'       => __('Title', 'woocommerce'),
				'type'        => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
			'shipping_rates' => array(
				'title'       => __('Rates', 'wc_colissimo'),
				'type'        => 'shipping_rates',
				'description' => __('Rates by weight', 'wc_colissimo'),
				'default'     => '',
				'desc_tip'    => true,
			),
			'use_cart_price' => array(
				'title'       => __('Use cart price instead of weight', 'wc_colissimo'),
				'type'        => 'checkbox',
				'description' => __('Do you want to base your shipping fees on cart price instead of the cart weight?', 'wc_colissimo'),
				'default'     => '',
				'desc_tip'    => true,
			),
			'max_weight'     => array(
				'title'   => __('Maximum weight / price', 'wc_colissimo'),
				'type'    => 'number',
				'default' => '',
			),
		);
	}

	public function generate_shipping_rates_html() {
		return LpcHelper::renderPartial('shipping' . DS . 'shipping_rates_table.php', ['shippingMethod' => $this]);
	}

	public function validate_shipping_rates_field($key) {
		$result = array();
		foreach ($this->get_post_data()[$key] as $rate) {
			$weight = (float) str_replace(',', '.', $rate['weight']);

			if ($weight < 0) {
				$weight = 0;
			}

			$item = array(
				'weight' => $weight,
				'price'  => (float) str_replace(',', '.', $rate['price']),
			);

			$result[] = $item;
		};

		usort(
			$result,
			function ($a, $b) {
				$result = 0;

				if ($a['weight'] > $b['weight']) {
					$result = 1;
				} else if ($a['weight'] < $b['weight']) {
					$result = -1;
				}

				return $result;
			}
		);

		return $result;
	}

	public function getRates() {
		return $this->get_option('shipping_rates', []);
	}

	public function getMaximumWeight() {
		return $this->get_option('max_weight', null);
	}

	public function getUseCartPrice() {
		return $this->get_option('use_cart_price', 'no');
	}

	abstract public function isAlwaysFree();

	abstract public function freeFromOrderValue();

	public function calculate_shipping($package = array()) {
		$cost = null;

		if ($this->lpcCapabilitiesPerCountry->getInfoForDestination($package['destination']['country'], $this->id)) {
			$totalWeight = 0;
			$totalPrice  = 0;
			foreach ($package['contents'] as $item) {
				$product     = $item['data'];
				$totalWeight += $product->get_weight() * $item['quantity'];
				$totalPrice  += $product->get_price() * $item['quantity'];
			}

			// Should we compare to cart weight or cart price
			if ('yes' === $this->getUseCartPrice()) {
				$totalValue = $totalPrice;
			} else {
				$totalValue = $totalWeight;
			}

			// Maximum weight or price depending on option value
			$maximumWeight = $this->getMaximumWeight();
			if ($maximumWeight && $totalValue > $maximumWeight) {
				return; // no rates
			}

			if ('yes' === $this->isAlwaysFree()) {
				$cost = 0.0;
			} elseif ($this->freeFromOrderValue() > 0 && $package['contents_cost'] >= $this->freeFromOrderValue()) {
				$cost = 0.0;
			} else {

				$rates = $this->getRates();
				usort(
					$rates,
					function ($a, $b) {
						if ($a['weight'] == $b['weight']) {
							return 0;
						}

						return ($a['weight'] < $b['weight']) ? -1 : 1;
					}
				);

				foreach ($rates as $rate) {
					if ($rate['weight'] <= $totalValue) {
						$cost = $rate['price'];
					}
				}
			}

			if (null !== $cost) {
				$rate = array(
					'id'       => $this->id,
					'label'    => $this->title,
					'cost'     => $cost,
				);
				$this->add_rate($rate);
			}
		}
	}
}
