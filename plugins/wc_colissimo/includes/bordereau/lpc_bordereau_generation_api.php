<?php

require_once LPC_INCLUDES . 'lpc_soap_api.php';

class LpcBordereauGenerationApi extends LpcSoapApi {
	const API_BASE_URL = 'https://ws.colissimo.fr/sls-ws/SlsServiceWS/?wsdl';

	public function getApiUrl() {
		return self::API_BASE_URL;
	}

	public function generateBordereau(array $parcelNumbers) {
		$request = array(
			'contractNumber'                    => LpcHelper::get_option('lpc_id_webservices'),
			'password'                          => LpcHelper::get_option('lpc_pwd_webservices'),
			'generateBordereauParcelNumberList' => $parcelNumbers,
		);

		$response = $this->getSoapClient()->generateBordereauByParcelsNumbers($request)->return;
		if (0 != $response->messages->id) {
			LpcLogger::error(
				__METHOD__ . 'error in API response',
				['response' => $response->messages]
			);
			throw new Exception('Error in API response');
		}
		return $response;
	}

	public function getBordereauByNumber($bordereauNumber) {
		$request = array(
			'contractNumber'  => LpcHelper::get_option('lpc_id_webservices'),
			'password'        => LpcHelper::get_option('lpc_pwd_webservices'),
			'bordereauNumber' => $bordereauNumber,
		);

		$response = $this->getSoapClient()->getBordereauByNumber($request)->return;
		if (0 != $response->messages->id) {
			LpcLogger::error(
				__METHOD__ . 'error in API response',
				['response' => $response->messages]
			);
			throw new Exception('Error in API response');
		}
		return $response;
	}
}
