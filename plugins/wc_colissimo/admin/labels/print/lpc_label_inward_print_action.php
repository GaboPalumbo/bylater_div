<?php

defined('ABSPATH') || die('Restricted Access');
require_once LPC_FOLDER . DS . 'lib' . DS . 'MergePdf.class.php';


class LpcLabelInwardPrintAction extends LpcComponent {
	const AJAX_TASK_NAME    = 'label/inward/print';
	const ORDER_ID_VAR_NAME = 'lpc_order_id';

	/** @var LpcLabelDb */
	protected $labelDb;
	/** @var LpcAjax */
	protected $ajaxDispatcher;

	public function __construct(
		LpcAjax $ajaxDispatcher = null,
		LpcLabelDb $labelDb = null
	) {
		$this->ajaxDispatcher = LpcRegister::get('ajaxDispatcher', $ajaxDispatcher);
		$this->labelDb        = LpcRegister::get('labelDb', $labelDb);
	}

	public function getDependencies() {
		return ['ajaxDispatcher', 'labelDb'];
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
					'message' => 'unauthorized access to inward label print',
				)
			);
		}

		$orderId = intval(LpcHelper::getVar(self::ORDER_ID_VAR_NAME));
		try {
			$labelContent = $this->labelDb->getInwardLabelFor($orderId);
			if (empty($labelContent)) {
				throw new Exception('No label content');
			}
			$fileToDownloadName = get_temp_dir() . DS . 'Colissimo.inward(' . $orderId . ').pdf';
			$filesToMerge       = array();
			$labelContentFile   = fopen(sys_get_temp_dir() . DS . 'inward_label.pdf', 'w');
			fwrite($labelContentFile, $labelContent);
			fclose($labelContentFile);
			$filesToMerge[] = sys_get_temp_dir() . DS . 'inward_label.pdf';
			$cn23Content    = $this->labelDb->getInwardCn23For($orderId);
			if ($cn23Content) {
				$cn23ContentFile = fopen(sys_get_temp_dir() . DS . 'inward_cn23.pdf', 'w');
				fwrite($cn23ContentFile, $cn23Content);
				fclose($cn23ContentFile);
				$filesToMerge[] = sys_get_temp_dir() . DS . 'inward_cn23.pdf';
			}

			MergePdf::merge($filesToMerge, MergePdf::DESTINATION__INLINE, $fileToDownloadName);
			foreach ($filesToMerge as $fileToMerge) {
				unlink($fileToMerge);
			}
			unlink($fileToDownloadName);
		} catch (Exception $e) {
			header('HTTP/1.0 404 Not Found');

			return $this->ajaxDispatcher->makeAndLogError(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	public function getUrlForOrderId($orderId) {
		return $this->ajaxDispatcher->getUrlForTask(self::AJAX_TASK_NAME) . '&' . self::ORDER_ID_VAR_NAME . '=' . intval($orderId);
	}
}
