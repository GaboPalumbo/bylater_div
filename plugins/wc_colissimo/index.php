<?php
/**
 * Plugin Name: Colissimo shipping methods for WooCommerce
 * Description: This extension gives you the possibility to use the Colissimo shipping methods in WooCommerce
 * Version: 1.0.2
 * Author: Colissimo
 * Author URI: https://www.colissimo.entreprise.laposte.fr/fr
 * Text Domain: wc_colissimo
 *
 * @package wc_colissimo
 *
 * License: GNU General Public License v3.0
 */

defined('ABSPATH') || die('Restricted Access');
// Make sure WooCommerce is active before declaring the shipping methods
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

	// Load defines
	if (!defined('DS')) {
		define('DS', DIRECTORY_SEPARATOR);
	}

	define('LPC_COMPONENT', 'wc_colissimo');
	define('LPC_FOLDER', WP_PLUGIN_DIR . DS . LPC_COMPONENT . DS);
	define('LPC_RESOURCE_FOLDER', LPC_FOLDER . DS . 'resources' . DS);
	define('LPC_INCLUDES', LPC_FOLDER . 'includes' . DS);
	define('LPC_ADMIN', LPC_FOLDER . 'admin' . DS);
	define('LPC_PUBLIC', LPC_FOLDER . 'public' . DS);

	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	$pluginData = get_plugin_data(LPC_FOLDER . DS . 'index.php');
	define('LPC_VERSION', $pluginData['Version']);

	class LpcInit {
		protected $register;

		/**
		 * LpcInit constructor.
		 */
		public function __construct() {
			// Load translations first
			load_plugin_textdomain('wc_colissimo', false, basename(dirname(__FILE__)) . '/languages/');

			require_once LPC_INCLUDES . 'lpc_register.php';
			$this->register = new LpcRegister();

			require_once LPC_INCLUDES . 'init.php';
			new LpcIncludeInit();

			// Load the logger class
			require_once LPC_INCLUDES . 'lpc_logger.php';
			require_once LPC_INCLUDES . 'lpc_helper.php';

			if (defined('WP_ADMIN') && WP_ADMIN) {
				require_once LPC_ADMIN . 'init.php';
				new LpcAdminInit();

				if (defined('DOING_AJAX') && DOING_AJAX) {
					// needed for ajax calls from front
					require_once LPC_PUBLIC . 'init.php';
					new LpcPublicInit();
				}
			} else {
				require_once LPC_PUBLIC . 'init.php';
				new LpcPublicInit();
			}

			$this->register->init();
			$this->register_rewrite_rules();
			$this->checkCompatibilty();
		}

		protected function register_rewrite_rules() {
			require_once LPC_PUBLIC . 'tracking' . DS . 'lpc_tracking_page.php';
			require_once LPC_PUBLIC . 'order' . DS . 'lpc_bal_return.php';

			add_action(
				'init',
				function () {
					LpcTrackingPage::addRewriteRule();
					LpcBalReturn::addRewriteRule();
				}
			);

			register_deactivation_hook(LPC_FOLDER . 'index.php', 'flush_rewrite_rules');
			register_activation_hook(
				LPC_FOLDER . 'index.php',
				function () {
					LpcTrackingPage::addRewriteRule();
					LpcBalReturn::addRewriteRule();

					flush_rewrite_rules();
				}
			);
		}

		protected function checkCompatibilty() {
			require_once LPC_ADMIN . 'lpc_compatibility.php';
			LpcCompatibility::checkCDI();
		}
	}

	new LpcInit();
}
