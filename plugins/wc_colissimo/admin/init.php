<?php

defined('ABSPATH') || die('Restricted Access');

require_once LPC_ADMIN . 'lpc_settings_tab.php';
require_once LPC_ADMIN . 'pickup' . DS . 'lpc_pickup_relay_point_on_order.php';
require_once LPC_ADMIN . 'shipping' . DS . 'lpc_shipping_zones.php';
require_once LPC_ADMIN . 'labels' . DS . 'generation' . DS . 'lpc_order_generate_inward_label_action.php';
require_once LPC_ADMIN . 'labels' . DS . 'generation' . DS . 'lpc_order_generate_outward_label_action.php';
require_once LPC_ADMIN . 'labels' . DS . 'download' . DS . 'lpc_label_packager_download_action.php';
require_once LPC_ADMIN . 'labels' . DS . 'download' . DS . 'lpc_label_inward_download_action.php';
require_once LPC_ADMIN . 'labels' . DS . 'download' . DS . 'lpc_label_outward_download_action.php';
require_once LPC_ADMIN . 'labels' . DS . 'print' . DS . 'lpc_label_inward_print_action.php';
require_once LPC_ADMIN . 'labels' . DS . 'print' . DS . 'lpc_label_outward_print_action.php';
require_once LPC_ADMIN . 'orders' . DS . 'lpc_orders_table.php';
require_once LPC_ADMIN . 'bordereau' . DS . 'lpc_bordereau_download_action.php';
require_once LPC_ADMIN . 'lpc_admin_notices.php';
require_once LPC_ADMIN . 'lpc_compatibility.php';

class LpcAdminInit {

	public function __construct() {
		// Add left menu
		add_action('admin_menu', array($this, 'add_menus'), 99);

		LpcRegister::register('settingsTab', new LpcSettingsTab());
		LpcRegister::register('shippingZones', new LpcShippingZones());
		LpcRegister::register('pickupRelayPointOnOrder', new LpcPickupRelayPointOnOrder());

		LpcRegister::register('orderGenerateInwardLabelAction', new LpcOrderGenerateInwardLabelAction());
		LpcRegister::register('orderGenerateOutwardLabelAction', new LpcOrderGenerateOutwardLabelAction());
		LpcRegister::register('labelPackagerDownloadAction', new LpcLabelPackagerDownloadAction());
		LpcRegister::register('labelInwardDownloadAction', new LpcLabelInwardDownloadAction());
		LpcRegister::register('labelOutwardDownloadAction', new LpcLabelOutwardDownloadAction());
		LpcRegister::register('labelInwardPrintAction', new LpcLabelInwardPrintAction());
		LpcRegister::register('labelOutwardPrintAction', new LpcLabelOutwardPrintAction());
		LpcRegister::register('bordereauDownloadAction', new LpcBordereauDownloadAction());
		LpcRegister::register('lpcAdminNotices', new LpcAdminNotices());

		LpcHelper::enqueueStyle('lpc_styles', plugins_url('/css/lpc.css', __FILE__));

		add_action('admin_notices', array($this, 'lpc_notifications'));
	}

	/**
	 * Add Colissimo sub-menu to WC in the WP left menu
	 */
	public function add_menus() {
		add_submenu_page(
			'woocommerce',
			'Colissimo',
			'Colissimo',
			'read',
			'wc_colissimo_view',
			array($this, 'router')
		);
	}

	public function router() {
		$lpcOrdersTable = new LpcOrdersTable();
		$args           = array();
		$args['table']  = $lpcOrdersTable;
		$args['get']    = $_GET;
		echo LpcHelper::renderPartial('orders' . DS . 'lpc_orders_list_table.php', $args);
	}

	public function lpc_notifications() {
		$lpc_admin_notices = LpcRegister::get('lpcAdminNotices');
		$notifications     = array(
			'inward_label_sent',
			'outward_label_generate',
			'inward_label_generate',
			'cdi_warning',
		);
		foreach ($notifications as $oneNotification) {
			$notice_content = $lpc_admin_notices->get_notice($oneNotification);
			if ($notice_content) {
				echo $notice_content;
			}
		}
	}

}
