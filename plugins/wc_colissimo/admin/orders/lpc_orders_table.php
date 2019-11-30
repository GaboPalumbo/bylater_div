<?php

if (!class_exists('WP_List_Table')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
require_once LPC_INCLUDES . 'orders' . DS . 'lpc_order_queries.php';

class LpcOrdersTable extends WP_List_Table {
	const BULK_ACTION_IDS_PARAM_NAME                = 'bulk-lpc_action_id';
	const BULK_BORDEREAU_GENERATION_ACTION_NAME     = 'bulk-bordereau_generation';
	const BULK_LABEL_DOWNLOAD_ACTION_NAME           = 'bulk-label_download';
	const BULK_LABEL_GENERATION_OUTWARD_ACTION_NAME = 'bulk-label_generation_outward';
	const BULK_LABEL_GENERATION_INWARD_ACTION_NAME  = 'bulk-label_generation_inward';

	protected $per_page;

	protected $bordereauGeneration;
	protected $unifiedTrackingApi;
	/** @var LpcBordereauDownloadAction */
	protected $bordereauDownloadAction;
	/** @var LpcLabelPackagerDownloadAction */
	protected $labelPackagerDownloadAction;
	/** @var LpcLabelGenerationOutward */
	protected $labelGenerationOutward;
	/** @var LpcLabelInwardDownloadAction */
	protected $labelInwardDownloadAction;
	/** @var LpcOrderGenerateInwardLabelAction */
	protected $orderGenerateInwardLabelAction;
	/** @var LpcLabelGenerationInward */
	protected $labelGenerationInward;
	/** @var LpcLabelOutwardDownloadAction */
	protected $labelOutwardDownloadAction;
	/** @var LpcOrderGenerateOutwardLabelAction */
	protected $orderGenerateOutwardLabelAction;
	/** @var LpcLabelOutwardPrintAction */
	protected $labelOutwardPrintAction;
	/** @var LpcLabelInwardPrintAction */
	protected $labelInwardPrintAction;

	protected $updateStatuses;

	protected $inward_label_email_manager;

	public function __construct($per_page = 25) {
		parent::__construct();
		$this->per_page = $per_page;

		$this->bordereauGeneration             = LpcRegister::get('bordereauGeneration');
		$this->unifiedTrackingApi              = LpcRegister::get('unifiedTrackingApi');
		$this->bordereauDownloadAction         = LpcRegister::get('bordereauDownloadAction');
		$this->labelPackagerDownloadAction     = LpcRegister::get('labelPackagerDownloadAction');
		$this->labelGenerationOutward          = LpcRegister::get('labelGenerationOutward');
		$this->labelInwardDownloadAction       = LpcRegister::get('labelInwardDownloadAction');
		$this->orderGenerateInwardLabelAction  = LpcRegister::get('orderGenerateInwardLabelAction');
		$this->labelGenerationInward           = LpcRegister::get('labelGenerationInward');
		$this->labelOutwardDownloadAction      = LpcRegister::get('labelOutwardDownloadAction');
		$this->orderGenerateOutwardLabelAction = LpcRegister::get('orderGenerateOutwardLabelAction');
		$this->labelInwardPrintAction          = LpcRegister::get('labelInwardPrintAction');
		$this->labelOutwardPrintAction         = LpcRegister::get('labelOutwardPrintAction');
		$this->inward_label_email_manager      = LpcRegister::get('lpcInwardLabelEmailManager');
		$this->updateStatuses                  = LpcRegister::get('updateStatusesAction');
	}


	public function get_columns() {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'id'              => __('ID', 'wc_colissimo'),
			'date'            => __('Date', 'wc_colissimo'),
			'customer'        => __('Customer', 'wc_colissimo'),
			'address'         => __('Address', 'wc_colissimo'),
			'country'         => __('Country', 'wc_colissimo'),
			'shipping-method' => __('Shipping Method', 'wc_colissimo'),
			'order-status'    => __('Status', 'wc_colissimo'),
			'label'           => __('Label (out / in)', 'wc_colissimo'),
			'bordereau'       => __('Bordereau', 'wc_colissimo'),
			'actions'         => __('Actions', 'wc_colissimo'),
		);

		return array_map(
			function ($v) {
				return <<<END_HTML
<span style="font-weight:bold;">$v</span>
END_HTML;
			},
			$columns
		);
	}

	public function prepare_items($args = array()) {
		$this->process_bulk_action();

		$columns      = $this->get_columns();
		$hidden       = array();
		$sortable     = $this->get_sortable_columns();
		$total_items  = LpcOrderQueries::countLpcOrders($args);
		$current_page = $this->get_pagenum();
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
			)
		);
		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items           = $this->get_data($current_page, $this->per_page, $args);
	}

	protected function column_default($item, $column_name) {
		return $item[$column_name];
	}

	protected function get_data($current_page = 0, $per_page = 0, $args = array()) {
		$data     = array();
		$raw_data = LpcOrderQueries::getLpcOrdersAndLabels($current_page, $per_page, $args);
		foreach ($raw_data as $raw_data_entry) {
			$wc_order = $raw_data_entry['order'];
			$address  = $wc_order->get_shipping_address_1();
			$address  .= !empty($wc_order->get_shipping_address_2()) ? '<br>' . $wc_order->get_shipping_address_2() : '';
			$address  .= '<br>' . $wc_order->get_shipping_postcode() . ' ' . $wc_order->get_shipping_city();

			$outwardLabel = $wc_order->get_meta(LpcLabelGenerationOutward::OUTWARD_PARCEL_NUMBER_META_KEY);
			$inwardLabel  = $wc_order->get_meta(LpcLabelGenerationInward::INWARD_PARCEL_NUMBER_META_KEY);
			$labels       = $outwardLabel . '<br/>' . $inwardLabel;

			$data[] = array(
				'cb'              => '<input type="checkbox" />',
				'id'              => $wc_order->get_id(),
				'date'            => $wc_order->get_date_created()->date('m-d-Y'),
				'customer'        => $wc_order->get_shipping_first_name() . ' ' . $wc_order->get_shipping_last_name(),
				'address'         => $address,
				'country'         => $wc_order->get_shipping_country(),
				'shipping-method' => $wc_order->get_shipping_method(),
				'order-status'    => $this->getColissimoStatus($wc_order, $outwardLabel),
				'label'           => $labels,
				'bordereau'       => $this->getBorderauDownloadLink($wc_order),
				'actions'         => $this->getRowAction($wc_order),
			);
		}

		return $data;
	}

	protected function getRowAction(WC_Order $wc_order) {
		$orderId = absint($wc_order->get_id());

		$i18n    = __('Choose', 'wc_colissimo');
		$rowHtml = <<<END_HTML
<select id="unitAction_$orderId" data-orderid="$orderId" class="lpc_unit_actions">
	<option data-action="" class="lpc_default_option" value="choose">$i18n</option>
END_HTML;

		$rowHtml .= $this->buildSelectOption(
			'See order',
			'link',
			admin_url("post.php?post=$orderId&action=edit")
		);
		$labelDB = LpcRegister::get('labelDb');
		if ($labelDB->getOutwardLabelFor($orderId)) {
			$rowHtml .= $this->buildSelectOption(
				'Download outward label',
				'link',
				$this->labelOutwardDownloadAction->getUrlForOrderId($orderId)
			);
		}
		if ($labelDB->getInwardLabelFor($orderId)) {
			$rowHtml .= $this->buildSelectOption(
				'Download inward label',
				'link',
				$this->labelInwardDownloadAction->getUrlForOrderId($orderId)
			);
		}

		if ($labelDB->getOutwardLabelFor($orderId)) {
			$rowHtml .= $this->buildSelectOption(
				'Print outward label',
				'outward',
				$this->labelOutwardPrintAction->getUrlForOrderId($orderId)
			);
		}
		if ($labelDB->getInwardLabelFor($orderId)) {
			$rowHtml .= $this->buildSelectOption(
				'Print inward label',
				'inward',
				$this->labelInwardPrintAction->getUrlForOrderId($orderId)
			);
		}

		if ($labelDB->getInwardLabelFor($orderId)) {
			$rowHtml .= $this->buildSelectOption(
				'Email Return Label',
				'link',
				$this->inward_label_email_manager->labelEmailingUrl($wc_order)
			);
		}

		$rowHtml .= <<<END_HTML
</select>
<div class="lpcspinner" id="lpcspinner_$orderId"></div>
END_HTML;

		return $rowHtml;
	}

	protected function buildSelectOption($label, $type, $specificAction) {
		$i18n       = __($label, 'wc_colissimo');
		$optionHtml = <<<END_HTML
<option data-type="$type" data-action="$specificAction">$i18n</option>
END_HTML;

		return $optionHtml;
	}

	protected function getColissimoStatus($order, $label) {
		if (!empty($label)) {
			$trackingLink = $this->unifiedTrackingApi->getTrackingPageUrlForOrder($order->get_id());

			$colissimoStatus = new LpcColissimoStatus();

			$internalEventCode = $order->get_meta(LpcUnifiedTrackingApi::LAST_EVENT_INTERNAL_CODE_META_KEY);

			$eventLabel = $colissimoStatus->getStatusInfo($internalEventCode)['label'];

			return '<a href="' . $trackingLink . '" target="_blank">' . $eventLabel . '</a>';
		}

		return '-';
	}

	protected function getBorderauDownloadLink(WC_Order $order) {
		$bordereauNumber = $order->get_meta(LpcBordereauGeneration::BORDEREAU_ID_META_KEY);
		if (!empty($bordereauNumber)) {
			$bordereauDownloadUrl = $this->bordereauDownloadAction->getUrlForBordereau($bordereauNumber);

			return <<<END_HTML
<a href="$bordereauDownloadUrl" target="_blank">$bordereauNumber</a>
END_HTML;
		}
	}

	public function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="%s[]" value="%s" />',
			self::BULK_ACTION_IDS_PARAM_NAME,
			$item['id']
		);
	}

	public function get_bulk_actions() {
		$actions = [
			self::BULK_BORDEREAU_GENERATION_ACTION_NAME     => __('Generate bordereau', 'wc_colissimo'),
			self::BULK_LABEL_DOWNLOAD_ACTION_NAME           => __('Download label information', 'wc_colissimo'),
			self::BULK_LABEL_GENERATION_INWARD_ACTION_NAME  => __('Generate inward labels', 'wc_colissimo'),
			self::BULK_LABEL_GENERATION_OUTWARD_ACTION_NAME => __('Generate outward labels', 'wc_colissimo'),
		];

		return $actions;
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'id'    => array('id', true),
			'date'  => array('date', false),
			'label' => array('label', false),
		);

		return $sortable_columns;
	}

	public function process_bulk_action() {
		if (isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])) {
			$nonce  = filter_input(INPUT_GET, '_wpnonce', FILTER_SANITIZE_STRING);
			$action = 'bulk-' . $this->_args['plural'];

			if (!wp_verify_nonce($nonce, $action)) {
				wp_die(__('Access denied! (Security check failed)', 'wc_colissimo'));
			}
		}

		$action = $this->current_action();
		$ids    = LpcHelper::getVar(self::BULK_ACTION_IDS_PARAM_NAME, array(), 'array');
		if (empty($ids)) {
			// no selectionned IDs on bulk actions => nothing to do.
			return;
		}

		switch ($action) {
			case self::BULK_BORDEREAU_GENERATION_ACTION_NAME:
				$this->bulkBordereauGeneration($ids);
				break;

			case self::BULK_LABEL_DOWNLOAD_ACTION_NAME:
				$this->bulkLabelDownload($ids);
				break;

			case self::BULK_LABEL_GENERATION_OUTWARD_ACTION_NAME:
				$this->bulkLabelGeneration($this->labelGenerationOutward, $ids);
				break;

			case self::BULK_LABEL_GENERATION_INWARD_ACTION_NAME:
				$this->bulkLabelGeneration($this->labelGenerationInward, $ids);
				break;
		}
	}

	protected function getOrdersByIds(array $ids) {
		return array_map(
			function ($id) {
				return new WC_Order($id);
			},
			$ids
		);
	}

	protected function bulkBordereauGeneration(array $ids) {
		$orders = $this->getOrdersByIds($ids);

		$bordereau = $this->bordereauGeneration->generate($orders);
		/** Special handling of the generation result :
		 *  - if its empty, certainly because multiple bordereaux were generated (remembering that one
		 *    bordereau can only have 50 tracking numbers), we prefer not to download any of the generate
		 *    bordereau, and thus only refresh/redict to the same listing page,
		 *  - else, i.e. if its *not* empty, it means that only one bordereau was generated, as a convenience
		 *    for the user, we directly initiate a download of it.
		 */
		if (!empty($bordereau)) {
			$bordereauId                  = $bordereau->bordereauHeader->bordereauNumber;
			$bordereauGenerationActionUrl = $this->bordereauDownloadAction->getUrlForBordereau($bordereauId);

			$i18n = __('Click here to download your created bordereau', 'wc_colissimo');

			echo <<<END_DOWNLOAD_LINK
<div class="updated"><p><a href="$bordereauGenerationActionUrl">$i18n</a></p></div>
END_DOWNLOAD_LINK;
		} else {
			wp_redirect(
				remove_query_arg(
					array('_wp_http_referer', '_wpnonce', self::BULK_ACTION_IDS_PARAM_NAME, 'action', 'action2'),
					wp_unslash(filter_input(INPUT_SERVER, 'REQUEST_URI'))
				)
			);
			exit;
		}
	}

	protected function bulkLabelDownload(array $ids) {
		$labelDownloadActionUrl = $this->labelPackagerDownloadAction->getUrlForOrderIds($ids);
		$i18n                   = __('Click here to download your created label package', 'wc_colissimo');

		echo <<<END_DOWNLOAD_LINK
<div class="updated"><p><a href="$labelDownloadActionUrl">$i18n</a></p></div>
END_DOWNLOAD_LINK;
	}

	protected function bulkLabelGeneration($generator, array $ids) {
		$orders = $this->getOrdersByIds($ids);

		try {
			foreach ($orders as $order) {
				$generator->generate($order);
			}

			wp_redirect(
				remove_query_arg(
					array('_wp_http_referer', '_wpnonce', self::BULK_ACTION_IDS_PARAM_NAME, 'action', 'action2'),
					wp_unslash(filter_input(INPUT_SERVER, 'REQUEST_URI'))
				)
			);
			exit;
		} catch (Exception $e) {
			add_action(
				'admin_notice',
				function () use ($e) {
					LpcHelper::displayNoticeException($e);
				}
			);
		}
	}

	public function displayHeaders() {
		$title                    = __('Colissimo Orders', 'wc_colissimo');
		$buttonUpdateStatusLabel  = __('Update Colissimo statuses', 'wc_colissimo');
		$buttonUpdateStatusAction = $this->updateStatuses->getUpdateAllStatusesUrl();

		echo <<<HEADERS
<h1 class="wp-heading-inline">$title</h1>
<a href="$buttonUpdateStatusAction" class="page-title-action">$buttonUpdateStatusLabel</a>
<hr class="wp-header-end">
HEADERS;
	}
}
