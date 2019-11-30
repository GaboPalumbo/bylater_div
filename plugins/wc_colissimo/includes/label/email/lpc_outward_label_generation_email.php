<?php


class LpcOutwardLabelGenerationEmail extends WC_Email {
	public function __construct() {
		$this->id             = 'lpc_outward_label_generation';
		$this->title          = 'Outward label generated';
		$this->description    = 'An email is sent to the customer when the outward label is generated, if this option is set up';
		$this->customer_email = true;
		$this->heading        = __('Your order status changed', 'wc_colissimo');
		$this->subject        = sprintf('[%s] ' . __('Your order status changed', 'wc_colissimo'), '{blogname}');
		$this->template_html  = 'lpc_outward_label_generated.php';
		$this->template_plain = 'plain' . DS . 'lpc_outward_label_generated.php';
		$this->template_base  = untrailingslashit(plugin_dir_path(__FILE__)) . DS . 'templates' . DS;

		add_action('lpc_outward_label_generated', array($this, 'trigger'));

		parent::__construct();
	}

	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'         => $this->object,
				'tracking_link' => LpcRegister::get('unifiedTrackingApi')->getTrackingPageUrlForOrder($this->object->get_id()),
				'email_heading' => $this->heading,
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this,

			),
			'',
			$this->template_base
		);
	}

	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'         => $this->object,
				'tracking_link' => LpcRegister::get('unifiedTrackingApi')->getTrackingPageUrlForOrder($this->object->get_id()),
				'email_heading' => $this->heading,
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this,

			),
			'',
			$this->template_base
		);
	}

	public function trigger(WC_Order $order) {
		$this->object    = $order;
		$this->recipient = $order->get_billing_email();
		$sending         = $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), array());

		return $sending;
	}
}
