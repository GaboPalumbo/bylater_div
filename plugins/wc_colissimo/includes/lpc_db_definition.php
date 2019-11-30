<?php

class LpcDbDefinition extends LpcComponent {
	const LPC_DB_VERSION_OPTION_NAME = 'lpc_db_version';
	const DATA_VERSION               = '1.0.2';

	protected $labelDb;

	public function __construct(LpcLabelDb $labelDb = null) {
		$this->labelDb = LpcRegister::get('labelDb', $labelDb);
	}

	public function getDependencies() {
		return ['labelDb'];
	}

	public function init() {
		register_activation_hook(LPC_FOLDER . 'index.php', array($this, 'defineTables'));
	}

	public function defineTables() {
		$installedVersion = get_option(self::LPC_DB_VERSION_OPTION_NAME);

		if (version_compare($installedVersion, '1.0.2', '<')) {
			$this->defineTableLabel();
		}

		update_option(self::LPC_DB_VERSION_OPTION_NAME, self::DATA_VERSION);

		return null;
	}

	protected function defineTableLabel() {
		global $wpdb;

		$sql = $this->labelDb->getTableDefinition();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}
}
