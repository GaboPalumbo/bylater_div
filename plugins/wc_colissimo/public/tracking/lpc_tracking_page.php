<?php

defined('ABSPATH') || die('Restricted Access');

class LpcTrackingPage extends LpcComponent {
	const ROUTE     = '^lpc/tracking/(.+)/?';
	const QUERY_VAR = 'lpc_tracking_hash';

	protected $lpcUnifiedTrackingApi;

	public function __construct(LpcUnifiedTrackingApi $lpcUnifiedTrackingApi = null) {
		$this->lpcUnifiedTrackingApi = LpcRegister::get('unifiedTrackingApi', $lpcUnifiedTrackingApi);
	}

	public function getDependencies() {
		return ['unifiedTrackingApi'];
	}

	public function init() {
		add_filter('query_vars', function (array $rules) {
			$rules[] = self::QUERY_VAR;
			return $rules;
		});

		add_action('parse_request', function (WP $wp) {
			if (!empty($wp->query_vars[self::QUERY_VAR])) {
				$this->control($wp);
			}
		});
	}

	public function control(WP $wp) {
		$trackingHash = $wp->query_vars[self::QUERY_VAR];
		$orderId      = $this->lpcUnifiedTrackingApi->decrypt(urldecode($trackingHash));

		try {
			$order          = new WC_Order($orderId);
			$trackingNumber = get_post_meta($orderId, 'lpc_outward_parcel_number', true);

			try {
				if (isset($_SERVER['REMOTE_ADDR'])) {
					$trackingInfo = $this->lpcUnifiedTrackingApi->getTrackingInfo(
						$trackingNumber,
						wc_clean(wp_unslash($_SERVER['REMOTE_ADDR'])),
						null,
						null
					);
				}
			} catch (Exception $e) {
				header('HTTP/1.0 500 Internal Server Error');
				wp_die(sprintf(__('An error occured while retrieving tracking info (%1$s [%2$u])... <a href="%3$s">get back to the home page</a>.', 'wc_colissimo'), $e->getMessage(), $e->getCode(), get_home_url()));
			}
			$trackingInfo->mainStatus = $this->getMainStatus($trackingInfo);

			die(LpcHelper::renderPartialInLayout(
				'tracking' . DS . 'tracking_page.php',
				array(
					'order'        => $order,
					'logoUrl'      => plugins_url('/images/colissimo.png', LPC_PUBLIC . 'init.php'),
					'trackingInfo' => $trackingInfo,
				)
			));
		} catch (Exception $e) {
			header('HTTP/1.0 404 Not Found');
			wp_die(sprintf(__('Not found... <a href="%s">get back to the home page</a>.', 'wc_colissimo'), get_home_url()));
		}
	}

	public static function addRewriteRule() {
		add_rewrite_rule(
			self::ROUTE,
			'index.php?' . self::QUERY_VAR . '=$matches[1]',
			'bottom'
		);
	}

	protected function getMainStatus(stdClass $trackingInfo) {
		try {
			if ($trackingInfo->parcel->statusDelivery) {
				return __('Delivered', 'wc_colissimo');
			}

			return $trackingInfo->parcel->eventLastLabel;
		} catch (\Exception $e) {
			return '';
		}
	}
}
