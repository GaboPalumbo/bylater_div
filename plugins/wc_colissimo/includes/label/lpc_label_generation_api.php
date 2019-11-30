<?php

require_once LPC_INCLUDES . 'lpc_rest_api.php';

class LpcLabelGenerationApi extends LpcRestApi {
	const API_BASE_URL = 'https://ws.colissimo.fr/sls-ws/SlsServiceWSRest/';

	protected function getApiUrl($action) {
		return self::API_BASE_URL . $action;
	}

	public function generateLabel(LpcLabelGenerationPayload $payload) {
		try {
			$assembledPayload = $payload->assemble();

			$response = $this->query(
				'generateLabel',
				$assembledPayload
			);

			$jsonResponse = $response['<jsonInfos>'];

			if (0 != $jsonResponse['messages'][0]['id']) {
				throw new Exception($jsonResponse['messages'][0]['messageContent'], $jsonResponse['messages'][0]['id']);
			}

			return $response;
		} catch (Exception $e) {
			LpcLogger::error(
				'Error during label generation."',
				array(
					'payload'   => $assembledPayload,
					'exception' => $e->getMessage(),
				)
			);

			throw $e;
		}
	}

	public function listMailBoxPickingDates(array $payload) {
		return $this->query('getListMailBoxPickingDates', $payload);
	}

	public function planPickup(array $payload) {
		// Demo version
		return [
			'id'             => 0,
			'messageContent' => 'by-passed for tests',
			'type'           => 'INFOS',
		];

		// Live version
		// return $this->query('planPickup', $payload);
	}
}
