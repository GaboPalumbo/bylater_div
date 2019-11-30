<?php

class LpcLabelGenerationPayload {
	const MAX_INSURANCE_AMOUNT                = 1500;
	const FORCED_ORIGINAL_IDENT               = 'A';
	const RETURN_LABEL_LETTER_MARK            = 'R';
	const RETURN_TYPE_CHOICE_NO_RETURN        = 3;
	const PRODUCT_CODE_INSURANCE_AVAILABLE    = array('DOS', 'COL', 'BPR', 'A2P', 'CDS', 'CORE', 'CORI', 'COLI');
	const CUSTOMS_CATEGORY_RETURN_OF_ARTICLES = 6;

	protected $payload;
	protected $isReturnLabel;
	protected $capabilitiesPerCountry;

	public function __construct(
		LpcCapabilitiesPerCountry $capabilitiesPerCountry = null
	) {
		$this->capabilitiesPerCountry = LpcRegister::get('capabilitiesPerCountry', $capabilitiesPerCountry);

		$this->payload = array(
			'letter' => array(
				'service' => [],
				'parcel'  => [],
			),
		);

		$this->isReturnLabel = false;
	}

	public function withSender(array $sender = null) {
		if (null === $sender) {
			$sender = $this->getStoreAddress();
		}

		$this->payload['letter']['sender'] = array(
			'address' => array(
				'companyName' => @$sender['companyName'],
				'firstName'   => @$sender['firstName'],
				'lastName'    => @$sender['lastName'],
				'line2'       => @$sender['street'],
				'countryCode' => $sender['countryCode'],
				'city'        => $sender['city'],
				'zipCode'     => $sender['zipCode'],
				'email'       => @$sender['email'],
			),
		);

		if (!empty($sender['street2'])) {
			$this->payload['letter']['sender']['address']['line3'] = $sender['street2'];
		}

		return $this;
	}

	public function withCommercialName($commercialName = null) {
		if (empty($commercialName)) {
			unset($this->payload['letter']['service']['commercialName']);
		} else {
			$this->payload['letter']['service']['commercialName'] = $commercialName;
		}

		return $this;
	}

	public function withContractNumber($contractNumber = null) {
		if (null === $contractNumber) {
			$contractNumber = LpcHelper::get_option('lpc_id_webservices');
		}

		if (empty($contractNumber)) {
			unset($this->payload['contractNumber']);
		} else {
			$this->payload['contractNumber'] = $contractNumber;
		}

		return $this;
	}

	public function withPassword($password = null) {
		if (null === $password) {
			$password = LpcHelper::get_option('lpc_pwd_webservices');
		}

		if (empty($password)) {
			unset($this->payload['password']);
		} else {
			$this->payload['password'] = $password;
		}

		return $this;
	}

	public function withAddressee(array $addressee) {
		$this->payload['letter']['addressee'] = array(
			'address' => array(
				'companyName'  => @$addressee['companyName'],
				'firstName'    => @$addressee['firstName'],
				'lastName'     => @$addressee['lastName'],
				'line2'        => $addressee['street'],
				'countryCode'  => $addressee['countryCode'],
				'city'         => $addressee['city'],
				'zipCode'      => $addressee['zipCode'],
				'email'        => @$addressee['email'],
				'mobileNumber' => @$addressee['mobileNumber'],
			),
		);

		$this->setFtdGivenCountryCodeId($addressee['countryCode']);

		if (!empty($addressee['street2'])) {
			$this->payload['letter']['addressee']['address']['line3'] = $addressee['street2'];
		}

		return $this;
	}

	public function withPackage(WC_Order $order) {
		$totalWeight = 0;
		foreach ($order->get_items() as $item) {
			$data    = $item->get_data();
			$product = $item->get_product();
			$weight  = (float) $product->get_weight() * $data['quantity'];

			if ($weight < 0) {
				throw new \Exception(
					__('Weight cannot be negative!', 'wc_colissimo')
				);
			}

			$weightInKg = wc_get_weight($weight, 'kg');
			$totalWeight += $weightInKg;
		}

		if ($totalWeight < 0.01) {
			$totalWeight = 0.01;
		}

		$totalWeight = number_format($totalWeight, 2);

		$this->payload['letter']['parcel']['weight'] = $totalWeight;

		return $this;
	}

	public function withPickupLocationId($pickupLocationId) {
		if (null === $pickupLocationId) {
			unset($this->payload['letter']['parcel']['pickupLocationId']);
		} else {
			$this->payload['letter']['parcel']['pickupLocationId'] = $pickupLocationId;
		}

		return $this;
	}

	public function withProductCode($productCode) {
		$allowedProductCodes = [
			'A2P',
			'ACCI',
			'BDP',
			'BPR',
			'CDS',
			'CMT',
			'COL',
			'COLD',
			'COLI',
			'COM',
			'CORE',
			'CORI',
			'DOM',
			'DOS',
			'ECO',
		];

		if (!in_array($productCode, $allowedProductCodes)) {
			LpcLogger::error(
				'Unknown productCode',
				array(
					'given' => $productCode,
					'known' => $allowedProductCodes,
				)
			);
			throw new \Exception('Unknown Product code!');
		}

		$this->payload['letter']['service']['productCode'] = $productCode;

		$this->payload['letter']['service']['returnTypeChoice'] = self::RETURN_TYPE_CHOICE_NO_RETURN;

		return $this;
	}

	protected function setFtdGivenCountryCodeId($destinationCountryId) {
		if (LpcHelper::get_option('lpc_customs_isFtd') === 'yes' && $this->capabilitiesPerCountry->getFtdRequiredForDestination($destinationCountryId) === true) {
			$this->payload['letter']['parcel']['ftd'] = true;
		} else {
			unset($this->payload['letter']['parcel']['ftd']);
		}
	}

	public function withDepositDate(\DateTime $depositDate) {
		$now = new \DateTime();
		if ($depositDate->getTimestamp() < $now->getTimestamp()) {
			LpcLogger::warn(
				'Given DepositDate is in the past, using today instead.',
				array(
					'given' => $depositDate,
					'now'   => $now,
				)
			);
			$depositDate = $now;
		}

		$this->payload['letter']['service']['depositDate'] = $depositDate->format('Y-m-d');

		return $this;
	}

	public function withPreparationDelay($delay = null) {
		if (null === $delay) {
			$delay = LpcHelper::get_option('lpc_preparation_time');
		}

		$depositDate = new \DateTime();

		$delay = (int) $delay;
		if ($delay > 0) {
			$depositDate->add(new \DateInterval("P{$delay}D"));
		} else {
			LpcLogger::warn(
				'Preparation delay was not applied because it was negative or zero!',
				['given' => $delay]
			);
		}

		return $this->withDepositDate($depositDate);
	}

	public function withOutputFormat($outputFormat = null) {
		if (null === $outputFormat) {
			$outputFormat = $this->getIsReturnLabel()
			? LpcHelper::get_option('lpc_returnLabelFormat')
			: LpcHelper::get_option('lpc_deliveryLabelFormat');
		}

		$this->payload['outputFormat'] = array(
			'x'                  => 0,
			'y'                  => 0,
			'outputPrintingType' => $outputFormat,
		);

		return $this;
	}

	public function withOrderNumber($orderNumber) {
		$this->payload['letter']['service']['orderNumber'] = $orderNumber;
		$this->payload['letter']['sender']['senderParcelRef'] = $orderNumber;

		return $this;
	}

	public function withInsuranceValue($amount, $productCode, $countryCode) {
		$usingInsurance = LpcHelper::get_option('lpc_using_insurance', 'no');

		if ('yes' !== $usingInsurance || !in_array($productCode, self::PRODUCT_CODE_INSURANCE_AVAILABLE) || ('DOS' == $productCode && 'FR' !== $countryCode)) {
			return $this;
		}

		$amount = (float) $amount;

		if ($amount > self::MAX_INSURANCE_AMOUNT) {
			LpcLogger::warn(
				'Given insurance value amount is too big, forced to ' . self::MAX_INSURANCE_AMOUNT,
				array(
					'given' => $amount,
					'max'   => self::MAX_INSURANCE_AMOUNT,
				)
			);

			$amount = self::MAX_INSURANCE_AMOUNT;
		}

		if ($amount > 0) {
			// payload want centi-euros for this field.
			$this->payload['letter']['parcel']['insuranceValue'] = (int) ($amount * 100);
		} else {
			LpcLogger::warn(
				'Insurance value was not applied because it was negative or zero!',
				array(
					'given' => $amount,
				)
			);
		}

		return $this;
	}

	public function withCODAmount($amount) {
		$amount = (float) $amount;

		if ($amount > 0) {
			$this->payload['letter']['parcel']['COD'] = true;
			// payload want centi-euros for this field.
			$this->payload['letter']['parcel']['CODAmount'] = (int) ($amount * 100);
		} else {
			LpcLogger::warn(
				'CODAmount was not applied because it was negative or zero!',
				array(
					'given' => $amount,
				)
			);
		}

		return $this;
	}

	public function withReturnReceipt($value = true) {
		if ($value) {
			$this->payload['letter']['parcel']['returnReceipt'] = true;
		} else {
			unset($this->payload['letter']['parcel']['returnReceipt']);
		}

		return $this;
	}

	public function withInstructions($instructions) {
		if (empty($instructions)) {
			unset($this->payload['letter']['parcel']['instructions']);
		} else {
			$this->payload['letter']['parcel']['instructions'] = $instructions;
		}

		return $this;
	}

	public function withCuserInfoText($info = null) {
		if (null === $info) {
			global $woocommerce;

			$woocommerceVersion = $woocommerce->version;
			$pluginData         = get_plugin_data(LPC_FOLDER . DS . 'index.php', false, false);
			$colissimoVersion   = $pluginData['Version'];

			$info = 'WC' . $woocommerceVersion . ';' . $colissimoVersion;
		}

		$customFields = array(
			array(
				'key'   => 'CUSER_INFO_TEXT',
				'value' => $info,
			),
		);

		$this->payload['fields'] = array(
			'customField' => $customFields,
		);

		return $this;
	}

	public function withCustomsDeclaration(WC_Order $order, $destinationCountryId) {

		// No need details if no CN23 required
		if (!$this->capabilitiesPerCountry->getIsCn23RequiredForDestination($destinationCountryId)) {
			return $this;
		}

		$defaultHsCode                 = LpcHelper::get_option('lpc_customs_defaultHsCode');
		$countryOfManufactureFieldName = LpcHelper::get_option('lpc_customs_countryOfManufactureFieldName');
		$hsCodeFieldName               = LpcHelper::get_option('lpc_customs_hsCodeFieldName');

		$customsArticles = array();

		foreach ($order->get_items() as $item) {
			$product     = $item->get_product();

			$unitaryValue = empty($item->get_quantity())
			? 1
			: $unitaryValue = $item->get_total() / $item->get_quantity();

			$customsArticle = array(
				'description'   => substr($item->get_name(), 0, 64),
				'quantity'      => $item->get_quantity(),
				'weight'        => $product->get_weight(), // unitary value
				'value'         => (int) $unitaryValue,
				'currency'      => $order->get_currency(),
				'artref'        => substr($product->get_sku(), 0, 44),
				'originalIdent' => self::FORCED_ORIGINAL_IDENT,
				'originCountry' => $product->get_attribute($countryOfManufactureFieldName),
				'hsCode'        => $product->get_attribute($hsCodeFieldName),
			);

			// Set default HS code if not defined on the product
			if (empty($customsArticle['hsCode'])) {
				$customsArticle['hsCode'] = $defaultHsCode;
			}

			$customsArticles[] = $customsArticle;
		}

		$this->payload['letter']['customsDeclarations'] = array(
			'includeCustomsDeclarations' => 1,
			'contents'                   => array(
				'article' => $customsArticles,
			),
			'invoiceNumber'              => $order->get_order_number(),
		);

		$transportationAmount = $order->get_shipping_total();

		// payload want centi-currency for these fields.
		$this->payload['letter']['service']['totalAmount']          = (int) ($transportationAmount * 100);
		$this->payload['letter']['service']['transportationAmount'] = (int) ($transportationAmount * 100);

		$customsCategory = $this->isReturnLabel
		? self::CUSTOMS_CATEGORY_RETURN_OF_ARTICLES
		: LpcHelper::get_option('lpc_customs_defaultCustomsCategory');

		$this->payload['letter']['customsDeclarations']['contents']['category'] = array(
			'value' => $customsCategory,
		);

		if ($this->getIsReturnLabel()) {
			$originalInvoiceDate = $order->get_date_created()
				->date('Y-m-d');

			$originalParcelNumber = $this->getOriginalParcelNumberFromInvoice($order);

			$this->payload['letter']['customsDeclarations']['contents']['original'] =
			array(
				array(
					'originalIdent'         => self::FORCED_ORIGINAL_IDENT,
					'originalInvoiceNumber' => $order->get_order_number(),
					'originalInvoiceDate'   => $originalInvoiceDate,
					'originalParcelNumber'  => $originalParcelNumber,
				),
			);
		}

		return $this;
	}

	public function isReturnLabel($isReturnLabel = true) {
		$this->isReturnLabel = $isReturnLabel;

		return $this;
	}

	public function getIsReturnLabel() {
		return $this->isReturnLabel;
	}

	public function checkConsistency() {
		$this->checkPickupLocationId();
		$this->checkCommercialName();

		if (!$this->getIsReturnLabel()) {
			$this->checkSenderAddress();
			$this->checkAddresseeAddress();
		}

		return $this;
	}

	public function assemble() {
		return array_merge($this->payload); // makes a copy
	}

	protected function checkPickupLocationId() {
		$productCodesNeedingPickupLocationIdSet = [
			'A2P',
			'BPR',
			'ACP',
			'CDI',
			'CMT',
			'BDP',
			'PCS',
		];

		if (in_array($this->payload['letter']['service']['productCode'], $productCodesNeedingPickupLocationIdSet)
			&& (!isset($this->payload['letter']['parcel']['pickupLocationId'])
				|| empty($this->payload['letter']['parcel']['pickupLocationId']))) {
			throw new Exception(
				__('The ProductCode used requires that a pickupLocationId is set!', 'wc_colissimo')
			);
		}

		if (!in_array($this->payload['letter']['service']['productCode'], $productCodesNeedingPickupLocationIdSet)
			&& isset($this->payload['letter']['parcel']['pickupLocationId'])) {
			throw new Exception(
				__('The ProductCode used requires that a pickupLocationId is *not* set!', 'wc_colissimo')
			);
		}
	}

	protected function checkCommercialName() {
		$productCodesNeedingCommercialName = [
			'A2P',
			'BPR',
		];

		if (in_array($this->payload['letter']['service']['productCode'], $productCodesNeedingCommercialName)
			&& (!isset($this->payload['letter']['service']['commercialName'])
				|| empty($this->payload['letter']['service']['commercialName']))) {
			throw new Exception(
				__('The ProductCode used requires that a commercialName is set!', 'wc_colissimo')
			);
		}
	}

	protected function checkSenderAddress() {
		$address = $this->payload['letter']['sender']['address'];

		if (empty($address['companyName'])) {
			throw new Exception(
				__('companyName must be set in Sender address!', 'wc_colissimo')
			);
		}

		if (empty($address['line2'])) {
			throw new Exception(
				__('line2 must be set in Sender address!', 'wc_colissimo')
			);
		}

		if (empty($address['countryCode'])) {
			throw new Exception(
				__('countryCode must be set in Sender address!', 'wc_colissimo')
			);
		}

		if (empty($address['zipCode'])) {
			throw new Exception(
				__('zipCode must be set in Sender address!', 'wc_colissimo')
			);
		}

		if (empty($address['city'])) {
			throw new Exception(
				__('city must be set in Sender address!', 'wc_colissimo')
			);
		}
	}

	protected function checkAddresseeAddress() {
		$productCodesNeedingMobileNumber = [
			'A2P',
			'BPR',
		];

		$address = $this->payload['letter']['addressee']['address'];

		if (empty($address['companyName'])
			&& (empty($address['firstName']) || empty($address['lastName']))
		) {
			throw new Exception(
				__('companyName or (firstName + lastName) must be set in Addressee address!', 'wc_colissimo')
			);
		}

		if ($this->isReturnLabel) {
			if (empty($address['companyName'])) {
				throw new \Exception(
					__('companyName must be set in Addressee address for return label!', 'wc_colissimo')
				);
			}
		}

		if (empty($address['line2'])) {
			throw new \Exception(
				__('line2 must be set in Addressee address!', 'wc_colissimo')
			);
		}

		if (empty($address['countryCode'])) {
			throw new \Exception(
				__('countryCode must be set in Addressee address!', 'wc_colissimo')
			);
		}

		if (empty($address['zipCode'])) {
			throw new \Exception(
				__('zipCode must be set in Addressee address!', 'wc_colissimo')
			);
		}

		if (empty($address['city'])) {
			throw new \Exception(
				__('city must be set in Addressee address!', 'wc_colissimo')
			);
		}

		if (in_array($this->payload['letter']['service']['productCode'], $productCodesNeedingMobileNumber)
			&& (!isset($address['mobileNumber'])
				|| empty($address['mobileNumber']))) {
			throw new \Exception(
				__('The ProductCode used requires that a mobile number is set!', 'wc_colissimo')
			);
		}
	}

	public function getStoreAddress() {
		// woocommerce_default_country may be the sole country code or the format 'US:IL' (i.e. with the state / province)
		$countryWithState = explode(':', WC_Admin_Settings::get_option('woocommerce_default_country'));
		$countryCode      = reset($countryWithState);

		return array(
			'companyName' => LpcHelper::get_option('lpc_company_name'),
			'street'      => WC_Admin_Settings::get_option('woocommerce_store_address'),
			'street2'     => WC_Admin_Settings::get_option('woocommerce_store_address_2'),
			'countryCode' => $countryCode,
			'city'        => WC_Admin_Settings::get_option('woocommerce_store_city'),
			'zipCode'     => WC_Admin_Settings::get_option('woocommerce_store_postcode'),
			'email'       => WC_Admin_Settings::get_option('woocommerce_email_from_address'),
		);
	}

	protected function getOriginalParcelNumberFromInvoice(WC_Order $order) {
		return get_post_meta($order->get_id(), LpcLabelGenerationOutward::OUTWARD_PARCEL_NUMBER_META_KEY, true);
	}
}
