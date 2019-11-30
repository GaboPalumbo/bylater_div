<?php

class LpcCompatibility extends LpcComponent {
	public static function checkCDI() {
		if (is_plugin_active('colissimo-delivery-integration/colissimo-delivery-integration.php')) {

			register_activation_hook(
				LPC_FOLDER . 'index.php',
				array(self::class, 'displayErrorCDI')
			);

			add_action(
				'load-woocommerce_page_wc-settings',
				array(self::class, 'displayErrorCDI')
			);

			add_action(
				'load-woocommerce_page_wc_colissimo_view',
				array(self::class, 'displayErrorCDI')
			);
		}
	}

	public static function displayErrorCDI() {
		$lpc_admin_notices = LpcRegister::get('lpcAdminNotices');
		$lpc_admin_notices->add_notice('cdi_warning', 'notice-warning', __('cdi_warning_message', 'wc_colissimo'));
	}
}
