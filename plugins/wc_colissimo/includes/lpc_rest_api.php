<?php

abstract class LpcRestApi extends LpcComponent {

	abstract protected function getApiUrl($action);

	public function query($action, $params = array()) {
		$dataJson = wp_json_encode($params);

		$url = $this->getApiUrl($action);

		LpcLogger::debug(__METHOD__, array('url' => $url));

		$ch = curl_init();
		curl_setopt_array(
			$ch,
			array(
				CURLOPT_URL            => $url,
				CURLOPT_HTTPHEADER     => array('Content-Type: application/json'),
				CURLOPT_POST           => 1,
				CURLOPT_POSTFIELDS     => $dataJson,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_BINARYTRANSFER => 1,
			)
		);

		$response = curl_exec($ch);
		if (!$response) {
			$curlError = curl_error($ch);
			$curlErrno = curl_errno($ch);
			LpcLogger::error(
				__METHOD__,
				array(
					'curl_errno' => $curlErrno,
					'curl_error' => $curlError,
				)
			);
			curl_close($ch);
			throw new Exception($curlError, $curlErrno);
		}

		$returnStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		return $this->parseResponse($returnStatus, $response);
	}

	protected function parseResponse($returnStatus, $response) {
		preg_match('/--(.*)\b/', $response, $boundary);

		$content = empty($boundary)
			? $this->parseMonoPartBody($response)
			: $this->parseMultiPartBody($response, $boundary[0]);

		switch ($returnStatus) {
			case 200:
				return $content;

			default:
				LpcLogger::warn(
					__METHOD__,
					array(
						'returnStatus' => $returnStatus,
						'jsonInfos'    => !empty($content['<jsonInfos>']) ? $content['<jsonInfos>'] : $content,
					)
				);

				if (!empty($content['<jsonInfos>'])) {
					$content = $content['<jsonInfos>'];
				}

				$message = $content['messages'][0]['id'] . ' : ' . $content['messages'][0]['messageContent'];
				throw new Exception('CURL error: ' . '(' . $returnStatus . ') ' . $message, $returnStatus);
		}
	}

	protected function parseMultiPartBody($body, $boundary) {
		$messages = array_filter(
			array_map(
				'trim',
				explode($boundary, $body)
			)
		);

		$parts = array();
		foreach ($messages as $message) {
			if ('--' === $message) {
				break;
			}

			$headers = [];
			list($headerLines, $body) = explode("\r\n\r\n", $message, 2);

			foreach (explode("\r\n", $headerLines) as $headerLine) {
				list($key, $value) = preg_split('/:\s+/', $headerLine, 2);
				$headers[strtolower($key)] = $value;
			}

			if ('application/json' === $headers['content-type']) {
				$body = json_decode($body, true);
			}

			$parts[$headers['content-id']] = '<jsonInfos>' === $headers['content-id']
				? json_decode($body, true)
				: $body;
		}

		return $parts;
	}

	protected function parseMonoPartBody($body) {
		return json_decode($body, true);
	}

}
