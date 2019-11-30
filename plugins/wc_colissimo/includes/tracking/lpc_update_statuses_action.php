<?php

defined('ABSPATH') || die('Restricted Access');

class LpcUpdateStatusesAction extends LpcComponent {
	const AJAX_TASK_NAME = 'tracking_update_all_statuses';

	protected $unifiedTrackingApi;

	protected $ajaxDispatcher;

	public function __construct(
		LpcAjax $ajaxDispatcher = null,
		LpcUnifiedTrackingApi $unifiedTrackingApi = null
	) {
		$this->ajaxDispatcher     = LpcRegister::get('ajaxDispatcher', $ajaxDispatcher);
		$this->unifiedTrackingApi = LpcRegister::get('unifiedTrackingApi', $unifiedTrackingApi);
	}

	public function getDependencies() {
		return ['ajaxDispatcher', 'unifiedTrackingApi'];
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
					'message' => 'unauthorized access to statuses update',
				)
			);
		}

		$result = $this->unifiedTrackingApi->updateAllStatuses();

		$lpc_admin_notices = LpcRegister::get('lpcAdminNotices');

		if (empty($result['failure'])) {
			$lpc_admin_notices->add_notice('inward_label_sent', 'notice-success', __('All statuses were updated', 'wc_colissimo'));
		} else {
			$lpc_admin_notices->add_notice('inward_label_sent', 'notice-error', __('Some statuses can\'t be updated. Please check log for more information', 'wc_colissimo'));
		}

		wp_redirect(admin_url('admin.php?page=wc_colissimo_view'));
	}

	public function getUpdateAllStatusesUrl() {
		return $this->ajaxDispatcher->getUrlForTask(self::AJAX_TASK_NAME);
	}
}
