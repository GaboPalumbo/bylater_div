<?php

require_once LPC_INCLUDES . 'lpc_rest_api.php';

class LpcPickUpWidgetApi extends LpcRestApi {
	const API_BASE_URL = 'https://ws.colissimo.fr/widget-point-retrait/rest/';

	public $token = null;

	protected function getApiUrl($action) {
		return self::API_BASE_URL . $action;
	}

	public function authenticate($login = null, $password = null) {
		if (empty($login)) {
			$login = LpcHelper::get_option('lpc_id_webservices');
		}

		if (empty($password)) {
			$password = LpcHelper::get_option('lpc_pwd_webservices');
		}

		try {
			$response = $this->query(
				'authenticate.rest',
				array(
					'login'    => $login,
					'password' => $password,
				)
			);

			if (!empty($response['token'])) {
				$this->token = $response['token'];
			}

			return $this->token;
		} catch (Exception $e) {
			LpcLogger::error('Error during authentication. Check your credentials."', ['exception' => $e]);

			return;
		}
	}

}
