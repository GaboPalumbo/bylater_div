<?php

defined('ABSPATH') || die('Restricted Access');

class LpcLabelPackagerDownloadAction extends LpcComponent {
	const AJAX_TASK_NAME = 'label/packager/download';
	const ORDER_IDS_VAR_NAME = 'lpc_order_id';

	/** @var LpcLabelPackager */
	protected $labelPackager;
	/** @var LpcAjax */
	protected $ajaxDispatcher;

	public function __construct(
		LpcAjax $ajaxDispatcher = null,
		LpcLabelPackager $labelPackager = null
	) {
		$this->ajaxDispatcher = LpcRegister::get('ajaxDispatcher', $ajaxDispatcher);
		$this->labelPackager  = LpcRegister::get('labelPackager', $labelPackager);
	}

	public function getDependencies() {
		return ['ajaxDispatcher', 'labelPackager'];
	}

	public function init() {
		$this->listenToAjaxAction();
	}

	protected function listenToAjaxAction() {
		$this->ajaxDispatcher->register(self::AJAX_TASK_NAME, array($this, 'control'));
	}

	public function control() {
		if (!current_user_can('edit_posts')) {
			header('HTTP/1.0 401 Unauthorized');

			return $this->ajaxDispatcher->makeAndLogError(
				array(
					'message' => 'unauthorized access to labels package download',
				)
			);
		}

		$orderIds = explode(',', LpcHelper::getVar(self::ORDER_IDS_VAR_NAME));
		try {
			$filename = basename('Colissimo.' . date('Y-m-d_H-i') . '.zip');
			header('Content-Type: application/octet-stream');
			header('Content-Transfer-Encoding: Binary');
			header("Content-disposition: attachment; filename=\"$filename\"");

			wp_die($this->labelPackager->generateZip($orderIds));
			$this->labelPackager->generateZip($orderIds);
		} catch (Exception $e) {
			header('HTTP/1.0 404 Not Found');

			return $this->ajaxDispatcher->makeAndLogError(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	public function getUrlForOrderIds(array $orderIds) {
		return $this->ajaxDispatcher->getUrlForTask(self::AJAX_TASK_NAME) . '&' . self::ORDER_IDS_VAR_NAME . '=' . implode(',', $orderIds);
	}
}
