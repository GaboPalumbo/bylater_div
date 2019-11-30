<?php

require_once LPC_INCLUDES . 'lpc_db.php';

class LpcLabelDb extends LpcDb {
	const TABLE_NAME = 'lpc_label';

	public function getTableName() {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_NAME;
	}

	public function getTableDefinition() {
		global $wpdb;

		$table_name = $this->getTableName();

		$charset_collate = $wpdb->get_charset_collate();

		return <<<END_SQL
CREATE TABLE $table_name (
		order_id BIGINT(20) UNSIGNED NOT NULL,
		outward_label MEDIUMBLOB NULL,
		outward_label_created_at DATETIME NULL,
		outward_cn23 MEDIUMBLOB NULL,
		inward_label MEDIUMBLOB NULL,
		inward_label_created_at DATETIME NULL,
		inward_cn23 MEDIUMBLOB NULL,
		PRIMARY KEY (order_id)
) $charset_collate;
END_SQL;
	}

	public function upsertOutward($orderId, $label, $cn23 = null) {
		global $wpdb;

		$tableName = $this->getTableName();
		// phpcs:disable
		$sql = <<<END_SQL
INSERT INTO $tableName SET
  order_id = %d,
  outward_label = %s,
  outward_label_created_at = %s,
  outward_cn23 = %s
ON DUPLICATE KEY
UPDATE
  outward_label = %s,
  outward_label_created_at = %s,
  outward_cn23 = %s
END_SQL;

		$sql = $wpdb->prepare(
			$sql,

			$orderId,
			$label,
			current_time('mysql'),
			$cn23,

			$label,
			current_time('mysql'),
			$cn23
		);

		return $wpdb->query($sql);
		// phpcs:enable
	}

	public function getOutwardLabelFor($orderId) {
		return $this->getField('outward_label', $orderId);
	}

	public function getOutwardCn23For($orderId) {
		return $this->getField('outward_cn23', $orderId);
	}

	public function upsertInward($orderId, $label, $cn23 = null) {
		global $wpdb;

		$tableName = $this->getTableName();
		// phpcs:disable
		$sql = <<<END_SQL
INSERT INTO $tableName SET
  order_id = %d,
  inward_label = %s,
  inward_label_created_at = %s,
  inward_cn23 = %s
ON DUPLICATE KEY
UPDATE
  inward_label = %s,
  inward_label_created_at = %s,
  inward_cn23 = %s
END_SQL;

		$sql = $wpdb->prepare(
			$sql,

			$orderId,
			$label,
			current_time('mysql'),
			$cn23,

			$label,
			current_time('mysql'),
			$cn23
		);

		return $wpdb->query($sql);
		// phpcs:enable
	}

	public function getInwardLabelFor($orderId) {
		return $this->getField('inward_label', $orderId);
	}

	public function getInwardCn23For($orderId) {
		return $this->getField('inward_cn23', $orderId);
	}

	public function purgeLabelsByOrderId($orderId) {
		global $wpdb;

		$tableName = $this->getTableName();

		// phpcs:disable
		$sql = <<<END_SQL
		UPDATE $tableName
		SET 
		outward_label = null,
		outward_cn23 = null,
		outward_label_created_at = null,
		inward_label = null,
		inward_cn23 = null,
		inward_label_created_at = null
		WHERE order_id = $orderId
END_SQL;

		return $wpdb->query($sql);
		// phpcs:enable
	}

	protected function getField($fieldName, $orderId) {
		global $wpdb;

		$tableName = $this->getTableName();
		$fieldName = addcslashes($fieldName, '`');

		// phpcs:disable
		$sql = <<<END_SQL
SELECT `$fieldName` FROM $tableName WHERE `order_id` = %d;
END_SQL;

		$sql         = $wpdb->prepare($sql, $orderId);
		$queryResult = $wpdb->get_col($sql);

		return reset($queryResult);
		// phpcs:enable
	}
}
