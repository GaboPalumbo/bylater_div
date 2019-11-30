<?php

/**
 * Class LpcModal
 */
class LpcModal {
	protected $templateId;
	protected $content;
	protected $title;

	public function __construct($content, $title = null, $templateId = null) {
		if (empty($templateId)) {
			$templateId = uniqid();
		}
		$this->templateId = $templateId;

		$this->content = $content;
		$this->title   = $title;

		LpcHelper::enqueueScript('wc-backbone-modal', null, plugins_url('woocommerce/assets/js/admin/backbone-modal.min.js'), ['wp-backbone']);
		$modalJS = plugins_url('/js/modal.js', __FILE__);
		LpcHelper::enqueueScript('lpc_modal', $modalJS, $modalJS, ['wc-backbone-modal']);

		$modalCSS = plugins_url('/css/modal.css', __FILE__);
		LpcHelper::enqueueStyle('lpc_modal', $modalCSS, $modalCSS);
	}

	public function setContent($content) {
		$this->content = $content;
		return $this;
	}

	public function echo_modal() {
		include LPC_INCLUDES . 'partials' . DS . 'modal' . DS . 'modal.php';

		return $this;
	}

	public function echo_button($buttonContent = null, $callback = null) {
		if (null === $buttonContent) {
			$buttonContent = __('Apply', 'wc_colissimo');
		}

		if (!empty($callback)) {
			$callback = 'data-lpc-callback="' . $callback . '"';
		}

		include LPC_INCLUDES . 'partials' . DS . 'modal' . DS . 'button.php';

		return $this;
	}

	public function echo_modalAndButton($buttonContent = null) {
		return $this->echo_button($buttonContent)->echo_modal();
	}

}
