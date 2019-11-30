<?php

require_once LPC_INCLUDES . 'label' . DS . 'lpc_label_generation_payload.php';

class LpcLabelGenerationOutward extends LpcComponent {
	const OUTWARD_PARCEL_NUMBER_META_KEY = 'lpc_outward_parcel_number';

	protected $capabilitiesPerCountry;
	protected $labelDb;
	protected $labelGenerationApi;
	protected $labelGenerationInward;
	protected $shippingMethods;

	public function __construct(
		LpcCapabilitiesPerCountry $capabilitiesPerCountry = null,
		LpcLabelDb $labelDb = null,
		LpcLabelGenerationApi $labelGenerationApi = null,
		LpcLabelGenerationInward $labelGenerationInward = null,
		LpcShippingMethods $shippingMethods = null
	) {
		$this->capabilitiesPerCountry = LpcRegister::get('capabilitiesPerCountry', $capabilitiesPerCountry);
		$this->labelDb                = LpcRegister::get('labelDb', $labelDb);
		$this->labelGenerationApi     = LpcRegister::get('labelGenerationApi', $labelGenerationApi);
		$this->labelGenerationInward  = LpcRegister::get('labelGenerationInward', $labelGenerationInward);
		$this->shippingMethods        = LpcRegister::get('shippingMethods', $shippingMethods);
	}

	public function getDependencies() {
		return ['capabilitiesPerCountry', 'labelDb', 'labelGenerationApi', 'labelGenerationInward', 'shippingMethods'];
	}

	public function generate(WC_Order $order) {
		if (is_admin()) {
			$lpc_admin_notices = LpcRegister::get('lpcAdminNotices');
		}

		try {
			$payload  = $this->buildPayload($order);
			$response = $this->labelGenerationApi->generateLabel($payload);
			if (is_admin()) {
				$lpc_admin_notices->add_notice('outward_label_generate', 'notice-success', sprintf(__('Order %s : Outward label generated', 'wc_colissimo'), $order->get_order_number()));
			}
		} catch (Exception $e) {
			if (is_admin()) {
				$lpc_admin_notices->add_notice('outward_label_generate', 'notice-error', sprintf(__('Order %s : Outward label was not generated:', 'wc_colissimo'), $order->get_order_number()) . ' ' . $e->getMessage());
			}

			return;
		}

		$parcelNumber = $response['<jsonInfos>']['labelResponse']['parcelNumber'];
		$label        = $response['<label>'];
		$cn23         = @$response['<cn23>'];

		update_post_meta($order->get_id(), self::OUTWARD_PARCEL_NUMBER_META_KEY, $parcelNumber);

		// PDF label is too big to be stored in a post_meta
		$this->labelDb->upsertOutward($order->get_id(), $label, $cn23);
		$this->applyStatusAfterLabelGeneration($order);

		$email_outward_label = LpcHelper::get_option(LpcOutwardLabelEmailManager::EMAIL_OUTWARD_TRACKING_OPTION, 'no');
		if ('yes' === $email_outward_label) {
			do_action(
				'lpc_outward_label_generated_to_email',
				array('order' => $order)
			);
		}

		if ('yes' === LpcHelper::get_option('lpc_createReturnLabelWithOutward')) {
			$this->labelGenerationInward->generate($order);
		}
	}

	protected function buildPayload(WC_Order $order) {
		$recipient = array(
			'companyName'  => $order->get_shipping_company(),
			'firstName'    => $order->get_shipping_first_name(),
			'lastName'     => $order->get_shipping_last_name(),
			'street'       => $order->get_shipping_address_1(),
			'street2'      => $order->get_shipping_address_2(),
			'city'         => $order->get_shipping_city(),
			'zipCode'      => $order->get_shipping_postcode(),
			'countryCode'  => $order->get_shipping_country(),
			'email'        => $order->get_billing_email(),
			'mobileNumber' => $order->get_billing_phone(),
		);

		$productCode = $this->capabilitiesPerCountry->getProductCodeForOrder($order);
		if (empty($productCode)) {
			LpcLogger::error('Not allowed for this destination', ['order' => $order]);
			throw new \Exception(__('Not allowed for this destination', 'wc_colissimo'));
		}

		$payload = new LpcLabelGenerationPayload();
		$payload
			->withContractNumber()
			->withPassword()
			->withCommercialName(LpcHelper::get_option('lpc_company_name'))
			->withCuserInfoText()

			->withSender()
			->withAddressee($recipient)

			->withPackage($order)

			->withPreparationDelay()
			->withInstructions($order->get_customer_note())

			->withCustomsDeclaration($order, $order->get_shipping_country())

			->withProductCode($productCode)
			->withOutputFormat()

			->withOrderNumber($order->get_order_number());

		if ('lpc_relay' === $this->shippingMethods->getColissimoShippingMethodOfOrder($order)) {
			$relayId = get_post_meta($order->get_id(), LpcPickupSelection::PICKUP_LOCATION_ID_META_KEY, true);
			$payload->withPickupLocationId($relayId);
		}

		$payload->withInsuranceValue($order->get_subtotal(), $productCode, $order->get_shipping_country());

		return $payload->checkConsistency();
	}

	protected function applyStatusAfterLabelGeneration(WC_Order $order) {
		$statusToApply = LpcHelper::get_option('lpc_order_status_on_label_generated', null);

		if (!empty($statusToApply) && 'unchanged_order_status' !== $statusToApply) {
			$order->set_status($statusToApply);
			$order->save();
		}
	}

}
