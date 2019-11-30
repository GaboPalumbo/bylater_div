<?php

class LpcInwardLabelEmailManager extends LpcComponent {

	protected $ajaxDispatcher;

	const AJAX_TASK_NAME            = 'inward_label_emailing';
	const ORDER_ID_VAR_NAME         = 'order_id';
	const EMAIL_RETURN_LABEL_OPTION = 'lpc_email_return_label';

	protected $mailer;

	public function __construct() {

		$this->ajaxDispatcher = LpcRegister::get('ajaxDispatcher');
	}

	public function getDependencies() {
		return array('ajaxDispatcher');
	}

	public function send_email($order_data) {

		$lpcInwardLabelGenerationEmail = WC()->mailer()->emails['lpc_generate_inward_label'];
		if (isset($order_data['label_filename'])) {
			$lpcInwardLabelGenerationEmail->trigger($order_data['order'], $order_data['label'], $order_data['label_filename']);
		} else {
			$lpcInwardLabelGenerationEmail->trigger($order_data['order'], $order_data['label']);
		}
	}

	public function generate_inward_label_woocommerce_email($emails) {
		require_once 'lpc_inward_label_generation_email.php';
		$emails['lpc_generate_inward_label'] = new LpcInwardLabelGenerationEmail();

		return $emails;
	}

	public function init() {
		add_action('lpc_inward_label_generated_to_email', array($this, 'send_email'));
		$this->listenToAjaxAction();
	}

	protected function listenToAjaxAction() {
		$this->ajaxDispatcher->register(self::AJAX_TASK_NAME, array($this, 'control'));
	}

	public function control() {
		$order_id = (int) LpcHelper::getVar(self::ORDER_ID_VAR_NAME);
		if (!current_user_can('edit_posts')) {
			header('HTTP/1.0 401 Unauthorized');

			return $this->ajaxDispatcher->makeAndLogError(
				array(
					'message' => 'unauthorized access to inward label sending',
				)
			);
		}
		try {
			WC()->mailer();
			$lpcInwardLabelGenerationEmail = new LpcInwardLabelGenerationEmail();
			$order                         = new WC_Order($order_id);
			$labelDb                       = LpcRegister::get('labelDb');
			$label                         = $labelDb->getInwardLabelFor($order_id);
			$sent                          = $lpcInwardLabelGenerationEmail->trigger($order, $label);
			// TODO : Try to find out a better way for the admin_notices
			$lpc_admin_notices = LpcRegister::get('lpcAdminNotices');
			if ($sent) {
				$lpc_admin_notices->add_notice('inward_label_sent', 'notice-success', __('Label sent', 'wc_colissimo'));
			} else {
				$lpc_admin_notices->add_notice('inward_label_sent', 'notice-error', __('Label was not sent', 'wc_colissimo'));
			}
			wp_redirect(admin_url('admin.php?page=wc_colissimo_view'));
		} catch (Exception $e) {
			return $e->getCode();
		}
	}


	public function labelEmailingUrl(WC_Order $order) {
		return $this->ajaxDispatcher->getUrlForTask(self::AJAX_TASK_NAME) . '&' . self::ORDER_ID_VAR_NAME . '=' . $order->get_id();
	}

}
