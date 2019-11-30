<?php

class LpcUnifiedTrackingApi extends LpcComponent {
	const API_WSDL_URL = 'https://ws.colissimo.fr/tracking-unified-ws/TrackingUnifiedServiceWS?wsdl';

	const CIPHER    = 'aes-128-cbc';
	const CRYPT_KEY = 'lpc_crypt_key';
	const QUERY_VAR = 'lpc_tracking_hash';

	const UPDATE_STATUS_PERIOD = '-15 days';

	const LAST_EVENT_CODE_META_KEY          = '_lpc_last_event_code';
	const LAST_EVENT_DATE_META_KEY          = '_lpc_last_event_date';
	const IS_DELIVERED_META_KEY             = '_lpc_is_delivered';
	const LAST_EVENT_INTERNAL_CODE_META_KEY = '_lpc_last_event_internal_code';

	const IS_DELIVERED_META_VALUE_TRUE  = '1';
	const IS_DELIVERED_META_VALUE_FALSE = '0';


	protected $soapClient;
	protected $ivSize;
	protected $shippingMethods;
	protected $ajaxDispatcher;

	protected $colissimoStatus;

	public function __construct(LpcShippingMethods $shippingMethods = null, LpcColissimoStatus $colissimoStatus = null, LpcAjax $ajaxDispatcher = null) {
		if (function_exists('openssl_cipher_iv_length')) {
			$this->ivSize = openssl_cipher_iv_length(self::CIPHER);
		}

		$this->shippingMethods = LpcRegister::get('shippingMethods', $shippingMethods);
		$this->colissimoStatus = LpcRegister::get('colissimoStatus', $colissimoStatus);
		$this->ajaxDispatcher  = LpcRegister::get('ajaxDispatcher', $ajaxDispatcher);
	}

	public function getDependencies() {
		return ['shippingMethods', 'colissimoStatus', 'ajaxDispatcher'];
	}

	protected function getSoapClient() {
		if (null === $this->soapClient) {
			$this->soapClient = new SoapClient(self::API_WSDL_URL);
		}

		return $this->soapClient;
	}

	public function getTrackingInfo(
		$trackingNumber,
		$ip,
		$lang = null,
		$login = null,
		$password = null
	) {
		if (empty($login)) {
			$login = LpcHelper::get_option('lpc_id_webservices');
		}

		if (empty($password)) {
			$password = LpcHelper::get_option('lpc_pwd_webservices');
		}

		if (null === $lang) {
			$lang = 'fr_FR';
		}

		$request = array(
			'login'        => $login,
			'password'     => $password,
			'parcelNumber' => $trackingNumber,
			'ip'           => $ip,
			'lang'         => $lang,
			'profil'       => 'TRACKING_PARTNER',
		);

		$response = $this->getSoapClient()->getTrackingMessagePickupAdressAndDeliveryDate($request);
		$response = $response->return;

		if (0 != $response->error->code) {
			LpcLogger::error(
				__METHOD__ . ' error in API response',
				['response' => $response]
			);
			throw new Exception(
				$response->error->message, $response->error->code
			);
		}

		if (!is_array($response->parcel->event)) {
			$response->parcel->event = array($response->parcel->event);
		}

		return $response;
	}

	public function updateAllStatuses($login = null, $password = null, $ip = null, $lang = null) {
		$fromDate = date('Y-m-d', strtotime(self::UPDATE_STATUS_PERIOD));

		$params = array(
			LpcOrderQueries::LPC_ALIAS_TABLES_NAME['posts'] . ".post_date > '" . $fromDate . "'",
			'(' . LpcOrderQueries::LPC_ALIAS_TABLES_NAME['postmeta'] . '.meta_value' . ' IS NULL OR ' . LpcOrderQueries::LPC_ALIAS_TABLES_NAME['postmeta'] . ".meta_value  = '0')",
		);

		$matchingOrdersId = LpcOrderQueries::getLpcOrdersIdsByPostMeta($params);

		$result = array(
			'success' => [],
			'failure' => [],
		);

		foreach ($matchingOrdersId as $orderId) {
			$order          = wc_get_order($orderId);
			$trackingNumber = $order->get_meta(LpcLabelGenerationOutward::OUTWARD_PARCEL_NUMBER_META_KEY);

			if (empty($trackingNumber)) {
				continue;
			}

			try {
				LpcLogger::debug(
					__METHOD__ . ' updating status for',
					array(
						'orderId'        => $orderId,
						'trackingNumber' => $trackingNumber,
					)
				);

				if (null === $ip) {
					$ip = WC_Geolocation::get_ip_address();
				}

				$currentState = $this->getTrackingInfo($trackingNumber, $ip, $lang, $login, $password);

				$eventLastCode = $currentState->parcel->eventLastCode;
				$eventLastDate = $currentState->parcel->eventLastDate;
				$isDelivered   = $currentState->parcel->statusDelivery;

				$currentStateInternalCode = $this->colissimoStatus->getInternalCodeForClp($eventLastCode);

				if (null === $currentStateInternalCode) {
					$currentStateInternalCode = LpcOrderStatuses::WC_LPC_UNKNOWN_STATUS_INTERNAL_CODE;
				}

				update_post_meta($orderId, self::LAST_EVENT_CODE_META_KEY, $eventLastCode);
				update_post_meta($orderId, self::LAST_EVENT_DATE_META_KEY, strtotime($eventLastDate));
				update_post_meta($orderId, self::IS_DELIVERED_META_KEY, $isDelivered ? self::IS_DELIVERED_META_VALUE_TRUE : self::IS_DELIVERED_META_VALUE_FALSE);
				update_post_meta($orderId, self::LAST_EVENT_INTERNAL_CODE_META_KEY, $currentStateInternalCode);

				if ($isDelivered) {
					$change_order_status = LpcOrderStatuses::WC_LPC_DELIVERED;
				} else {
					$currentStateInfo    = $this->colissimoStatus->getStatusInfo($currentStateInternalCode);
					$change_order_status = $currentStateInfo['change_order_status'];
				}

				if (!empty($change_order_status)) {
					$order->set_status($change_order_status);
					$order->save();
				}

				$result['success'][$orderId] = $eventLastCode;
			} catch (Exception $e) {
				LpcLogger::error(
					__METHOD__ . ' can\'t update status',
					array(
						'orderId'        => $orderId,
						'trackingNumber' => $trackingNumber,
						'errorMessage'   => $e->getMessage(),
					)
				);

				$result['failure'][$orderId] = $e->getMessage();
			}
		}

		return $result;
	}

	public function encrypt($trackNumber) {
		if (function_exists('openssl_encrypt')) {
			$iv         = openssl_random_pseudo_bytes($this->ivSize);
			$cyphertext = openssl_encrypt($trackNumber, self::CIPHER, self::CRYPT_KEY, 0, $iv);

			return urlencode(base64_encode($iv . $cyphertext));
		} else {
			return $this->xorText(self::CRYPT_KEY, $trackNumber);
		}
	}

	public function decrypt($trackHash) {
		if (function_exists('openssl_decrypt')) {
			$cypher     = base64_decode(urldecode($trackHash));
			$iv         = substr($cypher, 0, $this->ivSize);
			$cyphertext = substr($cypher, $this->ivSize);

			return openssl_decrypt($cyphertext, self::CIPHER, self::CRYPT_KEY, 0, $iv);
		} else {
			return $this->xorText(self::CRYPT_KEY, $trackHash);
		}
	}

	public function xorText($key, $text) {
		$keyLength  = strlen($key);
		$textLength = strlen($text);

		for ($i = 0; $i < $textLength; $i++) {
			$asciiValue = ord($text[$i]);
			$xored      = $asciiValue ^ ord($key[$i % $keyLength]);
			$text[$i]   = chr($xored);
		}

		return $text;
	}

	public function getTrackingPageUrlForOrder($orderId) {
		$trackingHash = $this->encrypt($orderId);

		return empty(get_option('permalink_structure')) ? '/' . self::QUERY_VAR . '=' . $trackingHash : '/lpc/tracking/' . $trackingHash;
	}
}
