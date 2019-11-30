<?php

require_once LPC_INCLUDES . 'lpc_modal.php';
require_once LPC_PUBLIC . 'pickup' . DS . 'lpc_pickup.php';

class LpcPickupWebService extends LpcPickup {
	protected $modal;
	protected $ajaxDispatcher;
	protected $lpcPickUpSelection;

	public function __construct(
		LpcAjax $ajaxDispatcher = null,
		LpcPickupSelection $lpcPickUpSelection = null
	) {
		$this->ajaxDispatcher     = LpcRegister::get('ajaxDispatcher', $ajaxDispatcher);
		$this->lpcPickUpSelection = LpcRegister::get('pickupSelection', $lpcPickUpSelection);
	}

	public function getDependencies() {
		return ['ajaxDispatcher', 'pickupSelection'];
	}

	public function init() {
		$this->ajaxDispatcher->register('pickupWS', array($this, 'pickupWS'));

		$args = [
			'chooseRelay'      => __('Choose this relay', 'wc_colissimo'),
			'confirmRelay'     => __('Confirm relay', 'wc_colissimo'),
			'confirmRelayDesc' => __('Do you confirm the shipment to this relay:', 'wc_colissimo'),
			'ajaxURL'          => $this->ajaxDispatcher->getUrlForTask('pickupWS'),
		];

		LpcHelper::enqueueScript('lpc_pickup', null, plugins_url('/js/pickup.js', LPC_PUBLIC . 'init.php'), ['jquery-core']);
		LpcHelper::enqueueScript('lpc_pick_up_ws', null, plugins_url('/js/pickup_ws.js', LPC_PUBLIC . 'init.php'), ['jquery', 'lpc_pickup'], 'lpcPickUpWS', $args);
		LpcHelper::enqueueStyle('lpc_pick_up_ws', null, plugins_url('/css/pickup_ws.css', LPC_PUBLIC . 'init.php'));

		add_action('woocommerce_after_shipping_rate', array($this, 'addGoogleMaps'));

		$this->modal = new LpcModal(null, 'Choose a PickUp point', 'lpc_pick_up_web_service');
	}

	/**
	 * Uses a WC hook to add a "Select pick up location" button on the checkout page
	 *
	 * @param     $method
	 * @param int $index
	 */
	public function addGoogleMaps($method, $index = 0) {
		if ($this->getMode($method->method_id) !== self::WEB_SERVICE) {
			return;
		}

		$wcSession = WC()->session;
		$customer  = $wcSession->customer;

		$map = LpcHelper::renderPartial(
			'pick_up' . DS . 'webservice_map.php',
			[
				'ceAddress'   => $customer['shipping_address'],
				'ceZipCode'   => $customer['shipping_postcode'],
				'ceTown'      => $customer['shipping_city'],
				'ceCountryId' => $customer['shipping_country'],
			]
		);
		$this->modal->setContent($map);

		$args = [
			'modal'        => $this->modal,
			'apiKey'       => LpcHelper::get_option('lpc_gmap_key', ''),
			'currentRelay' => $this->lpcPickUpSelection->getCurrentPickUpLocationInfo(),
		];
		echo LpcHelper::renderPartial('pick_up' . DS . 'webservice.php', $args);
	}

	public function pickupWS() {
		require_once LPC_INCLUDES . 'pick_up' . DS . 'lpc_relays_api.php';
		require_once LPC_INCLUDES . 'pick_up' . DS . 'lpc_generate_relays_payload.php';

		$address = array(
			'address'     => LpcHelper::getVar('address'),
			'zipCode'     => LpcHelper::getVar('zipCode'),
			'city'        => LpcHelper::getVar('city'),
			'countryCode' => LpcHelper::getVar('countryId'),
		);

		$generateRelaysPaypload = new LpcGenerateRelaysPayload();

		try {
			$generateRelaysPaypload->withLogin()->withPassword()->withAddress($address)->withShippingDate()->withOptionInter()->checkConsistency();

			$relaysApi = new LpcRelaysApi(['trace' => false]);

			$relaysPayload = $generateRelaysPaypload->assemble();

			$resultWs = $relaysApi->getRelays($relaysPayload);
		} catch (\SoapFault $fault) {
			return $this->ajaxDispatcher->makeAndLogError(['message' => $fault]);
		} catch (Exception $exception) {
			LpcLogger::error($exception);

			return $this->ajaxDispatcher->makeError(['message' => $exception->getMessage()]);
		}

		$return = $resultWs->return;

		if (0 == $return->errorCode) {
			if (empty($return->listePointRetraitAcheminement)) {
				LpcLogger::warn(__('The web service returned 0 relay', 'wc_colissimo'));

				return $this->ajaxDispatcher->makeError(['message' => __('No relay available', 'wc_colissimo')]);
			}

			$listRelaysWS = $return->listePointRetraitAcheminement;
			$html         = '';

			$i           = 0;
			$partialArgs = array(
				'relaysNb'    => count($listRelaysWS),
				'openingDays' => array(
					'Monday'    => 'horairesOuvertureLundi',
					'Tuesday'   => 'horairesOuvertureMardi',
					'Wednesday' => 'horairesOuvertureMercredi',
					'Thursday'  => 'horairesOuvertureJeudi',
					'Friday'    => 'horairesOuvertureVendredi',
					'Saturday'  => 'horairesOuvertureSamedi',
					'Sunday'    => 'horairesOuvertureDimanche',
				),
			);

			foreach ($listRelaysWS as $oneRelay) {
				$partialArgs['oneRelay'] = $oneRelay;
				$partialArgs['i']        = $i ++;

				$html .= LpcHelper::renderPartial('pick_up/relay.php', $partialArgs);
			}

			return $this->ajaxDispatcher->makeSuccess(['html' => $html]);
		} else if (in_array($return->errorCode, [301, 300, 203])) {
			LpcLogger::warn($return->errorCode . ' : ' . $return->errorMessage);

			return $this->ajaxDispatcher->makeError(['message' => __('No relay available', 'wc_colissimo')]);
		} else {
			// Error codes we want to display the related messages to the client, we'll only display a generic message for the other error codes
			$errorCodesWSClientSide = array(
				'104',
				'105',
				'117',
				'125',
				'129',
				'143',
				'144',
				'145',
				'146',
			);

			if (in_array($return->errorCode, $errorCodesWSClientSide)) {
				return $this->ajaxDispatcher->makeAndLogError(['message' => $return->errorCode . ' : ' . $return->errorMessage]);
			} else {
				LpcLogger::error($return->errorCode . ' : ' . $return->errorMessage);

				return $this->ajaxDispatcher->makeError(['message' => __('Error')]);
			}
		}
	}
}
