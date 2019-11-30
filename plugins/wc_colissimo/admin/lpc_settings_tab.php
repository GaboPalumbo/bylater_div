<?php

defined('ABSPATH') || die('Restricted Access');

require_once LPC_INCLUDES . 'lpc_modal.php';

/**
 * Class Lpc_Settings_Tab to handle Colissimo tab in Woocommerce settings
 */
class LpcSettingsTab extends LpcComponent {
	const LPC_SETTINGS_TAB_ID = 'lpc';

	/**
	 * @var array Options available
	 */
	protected $configOptions;

	protected $seeLogModal;

	public function init() {
		// Add configuration tab in Woocommerce
		add_filter('woocommerce_settings_tabs_array', array($this, 'configurationTab'), 70);
		// Add configuration tab content
		add_action('woocommerce_settings_tabs_' . self::LPC_SETTINGS_TAB_ID, array($this, 'settingsPage'));
		// Save settings page
		add_action('woocommerce_update_options_' . self::LPC_SETTINGS_TAB_ID, array($this, 'saveLpcSettings'));

		// Define the log modal field
		$this->initSeeLog();

		$this->initMultiSelectOrderStatus();

		$this->initSelectOrderStatusOnLabelGenerated();

		$this->initSelectOrderStatusOnPackageDelivered();

		$this->initSelectOrderStatusOnBordereauGenerated();

		$this->initConfigOptions();
	}

	protected function initSeeLog() {
		$modalContent      = '<pre>' . LpcLogger::get_logs() . '</pre>';
		$this->seeLogModal = new LpcModal($modalContent, 'Colissimo logs', 'lpc-debug-log');
		add_action('woocommerce_admin_field_seelog', array($this, 'displayDebugButton'));
	}

	protected function initMultiSelectOrderStatus() {
		add_action('woocommerce_admin_field_multiselectorderstatus', array($this, 'displayMultiSelectOrderStatus'));
	}

	protected function initSelectOrderStatusOnLabelGenerated() {
		add_action('woocommerce_admin_field_selectorderstatusonlabelgenerated', array($this, 'displaySelectOrderStatusOnLabelGenerated'));
	}

	protected function initSelectOrderStatusOnPackageDelivered() {
		add_action('woocommerce_admin_field_selectorderstatusonpackagedelivered', array($this, 'displaySelectOrderStatusOnPackageDelivered'));
	}

	protected function initSelectOrderStatusOnBordereauGenerated() {
		add_action('woocommerce_admin_field_selectorderstatusonbordereaugenerated', array($this, 'displaySelectOrderStatusOnBordereauGenerated'));
	}

	/**
	 * Define the "seelogs" field type for the main configuration page
	 *
	 * @param $field object containing parameters defined in the config_options.json
	 */
	public function displayDebugButton($field) {
		$modal = $this->seeLogModal;
		include LPC_FOLDER . 'admin' . DS . 'partials' . DS . 'settings' . DS . 'debug.php';
	}

	public function displayMultiSelectOrderStatus() {
		$args                    = array();
		$args['id_and_name']     = 'lpc_generate_label_on';
		$args['label']           = 'Generate label on';
		$args['order_statuses']  = wc_get_order_statuses();
		$args['selected_values'] = get_option($args['id_and_name']);
		$args['multiple']        = 'multiple';
		echo LpcHelper::renderPartial('settings' . DS . 'select_order_status.php', $args);
	}

	public function displaySelectOrderStatusOnLabelGenerated() {
		$args                    = array();
		$args['id_and_name']     = 'lpc_order_status_on_label_generated';
		$args['label']           = 'Order status once label is generated';
		$args['order_statuses']  = array_merge(['unchanged_order_status' => 'Keep order status as it is'], wc_get_order_statuses());
		$args['selected_values'] = get_option($args['id_and_name']);
		$args['multiple']        = '';
		echo LpcHelper::renderPartial('settings' . DS . 'select_order_status.php', $args);
	}

	public function displaySelectOrderStatusOnPackageDelivered() {
		$args                    = array();
		$args['id_and_name']     = 'lpc_order_status_on_package_delivered';
		$args['label']           = 'Order status once the package is delivered';
		$args['order_statuses']  = wc_get_order_statuses();
		$args['selected_values'] = get_option($args['id_and_name']);
		$args['multiple']        = '';
		echo LpcHelper::renderPartial('settings' . DS . 'select_order_status.php', $args);
	}

	public function displaySelectOrderStatusOnBordereauGenerated() {
		$args                    = array();
		$args['id_and_name']     = 'lpc_order_status_on_bordereau_generated';
		$args['label']           = 'Order status once bordereau is generated';
		$args['order_statuses']  = array_merge(['unchanged_order_status' => 'Keep order status as it is'], wc_get_order_statuses());
		$args['selected_values'] = get_option($args['id_and_name']);
		$args['multiple']        = '';
		echo LpcHelper::renderPartial('settings' . DS . 'select_order_status.php', $args);
	}

	/**
	 * Build tab
	 *
	 * @param $tab
	 *
	 * @return mixed
	 */
	public function configurationTab($tab) {
		$tab[self::LPC_SETTINGS_TAB_ID] = 'Colissimo Officiel';

		return $tab;
	}

	/**
	 * Content of the configuration page
	 */
	public function settingsPage() {
		WC_Admin_Settings::output_fields($this->configOptions);
	}

	/**
	 * Save using Woocomerce default method
	 */
	public function saveLpcSettings() {
		try {
			WC_Admin_Settings::save_fields($this->configOptions);
		} catch (Exception $exc) {
			LpcLogger::error("Can't save field setting.", $this->configOp);
		}
	}

	/**
	 * Initialize configuration options from resource file
	 */
	protected function initConfigOptions() {
		$configStructure = file_get_contents(LPC_RESOURCE_FOLDER . LpcHelper::CONFIG_FILE);
		$tempConfig      = json_decode($configStructure, true);
		foreach ($tempConfig as &$oneField) {
			if (!empty($oneField['title'])) {
				$oneField['title'] = __($oneField['title'], 'wc_colissimo');
			}
			if (!empty($oneField['desc'])) {
				$oneField['desc'] = __($oneField['desc'], 'wc_colissimo');
			}
		}

		$this->configOptions = $tempConfig;
	}
}
