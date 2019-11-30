<?php

class LpcOrderQueries {
	const LPC_ALIAS_TABLES_NAME = array(
		'woocommerce_order_items'    => 'wc_order_items',
		'lpc_label'                  => 'lpc_label',
		'woocommerce_order_itemmeta' => 'wc_order_itemmeta',
		'posts'                      => 'posts',
		'postmeta'                   => 'postmeta',

	);

	public static function getLpcOrdersAndLabels($current_page = 0, $per_page = 0, $args = array()) {
		// TODO: look if there is a better way to do (with WC Queries)
		global $wpdb;
		$lpc_shipping_method_names = self::getLpcShippingMethodsNameSqlReady();

		$andCriterion = '';
		if (isset($args['s']) && '' !== sanitize_text_field($args['s']) && isset($args['paged'])) {
			$search_filter = sanitize_text_field($args['s']);
			$andCriterion  = self::andCriterion($search_filter);
		}

		$query = "SELECT DISTINCT {$wpdb->prefix}woocommerce_order_items.order_id, {$wpdb->prefix}lpc_label.outward_label, {$wpdb->prefix}posts.post_date FROM {$wpdb->prefix}woocommerce_order_items 
                    JOIN {$wpdb->prefix}woocommerce_order_itemmeta ON {$wpdb->prefix}woocommerce_order_itemmeta.order_item_id={$wpdb->prefix}woocommerce_order_items.order_item_id 
                    JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID={$wpdb->prefix}woocommerce_order_items.order_id 
                    LEFT JOIN {$wpdb->prefix}lpc_label ON {$wpdb->prefix}lpc_label.order_id={$wpdb->prefix}woocommerce_order_items.order_id 
                	WHERE {$wpdb->prefix}woocommerce_order_itemmeta.meta_key='method_id' 
                    	AND {$wpdb->prefix}woocommerce_order_itemmeta.meta_value IN $lpc_shipping_method_names ";
		$query .= $andCriterion;
		$query .= self::getOrderBy($args);
		if (0 < $current_page && 0 < $per_page) {
			$offset = ($current_page - 1) * $per_page;
			$query  .= "LIMIT $per_page OFFSET $offset";
		}

		// phpcs:disable
		$results = $wpdb->get_results($query);
		// phpcs:enable

		$ordersAndLabels = array();
		if ($results) {
			foreach ($results as $result) {
				$ordersAndLabels[]['order']                            = new WC_Order($result->order_id);
				$ordersAndLabels[count($ordersAndLabels) - 1]['label'] = $result->outward_label;
			}
		}

		return $ordersAndLabels;
	}

	public static function countLpcOrders($args = array()) {
		global $wpdb;
		$lpc_shipping_methods = LpcRegister::get('shippingMethods')->getAllShippingMethods();
		array_walk($lpc_shipping_methods, array('self', 'formatTextForSql'));
		$lpc_shipping_method_names = '(' . implode(',', $lpc_shipping_methods) . ')';

		$andCriterion = '';
		if (isset($args['s']) && '' !== sanitize_text_field($args['s']) && isset($args['paged'])) {
			$search_filter = sanitize_text_field($args['s']);
			$andCriterion  = self::andCriterion($search_filter);
			$page          = (int) $args['paged'];
		}

		$query = "SELECT COUNT(DISTINCT {$wpdb->prefix}woocommerce_order_items.order_id) AS nb FROM {$wpdb->prefix}woocommerce_order_items 
                    JOIN {$wpdb->prefix}woocommerce_order_itemmeta ON {$wpdb->prefix}woocommerce_order_itemmeta.order_item_id={$wpdb->prefix}woocommerce_order_items.order_item_id 
                    JOIN {$wpdb->prefix}posts ON {$wpdb->prefix}posts.ID={$wpdb->prefix}woocommerce_order_items.order_id 
                    LEFT JOIN {$wpdb->prefix}lpc_label ON {$wpdb->prefix}lpc_label.order_id={$wpdb->prefix}woocommerce_order_items.order_id 
                	WHERE {$wpdb->prefix}woocommerce_order_itemmeta.meta_key='method_id' 
                    	AND {$wpdb->prefix}woocommerce_order_itemmeta.meta_value IN $lpc_shipping_method_names ";
		$query .= $andCriterion;

		// phpcs:disable
		$result = $wpdb->get_results($query);
		// phpcs:enable

		if (null !== $result) {
			return $result[0]->nb;
		}

		return 0;
	}

	public static function getLpcOrdersIdsByPostMeta($params = array()) {
		global $wpdb;

		$lpc_shipping_method_names = self::getLpcShippingMethodsNameSqlReady();
		$prefix                    = $wpdb->prefix;

		$query = "SELECT DISTINCT wc_order_items.order_id FROM {$prefix}woocommerce_order_items AS wc_order_items
                    JOIN {$prefix}woocommerce_order_itemmeta  AS wc_order_itemmeta ON wc_order_itemmeta.order_item_id = wc_order_items.order_item_id
                    JOIN {$prefix}posts AS posts ON posts.ID = wc_order_items.order_id
                    LEFT JOIN {$prefix}lpc_label AS lpc_label ON lpc_label.order_id = wc_order_items.order_id
                    LEFT JOIN {$prefix}postmeta AS postmeta ON postmeta.post_id = wc_order_items.order_id AND postmeta.meta_key='_lpc_is_delivered'";

		$params[] = "wc_order_itemmeta.meta_key='method_id' AND wc_order_itemmeta.meta_value IN $lpc_shipping_method_names";

		$query .= ' WHERE (' . implode(') AND (', $params) . ') ';

		// phpcs:disable
		$results = $wpdb->get_results($query);
		// phpcs:enable

		$ordersId = array();

		if ($results) {
			foreach ($results as $result) {
				$ordersId[] = $result->order_id;
			}
		}

		return $ordersId;
	}

	public static function getLpcOrdersIdsForPurge() {
		global $wpdb;

		$nbDays = LpcHelper::get_option('lpc_day_purge');

		$fromDate = time() - $nbDays * DAY_IN_SECONDS;

		$lastEventDateMetaKey = LpcUnifiedTrackingApi::LAST_EVENT_DATE_META_KEY;
		$isDeliveredMetaKey   = LpcUnifiedTrackingApi::IS_DELIVERED_META_KEY;

		$isDelivered = LpcUnifiedTrackingApi::IS_DELIVERED_META_VALUE_TRUE;

		$metaQuery = array(
			array(
				'key'     => $lastEventDateMetaKey,
				'value'   => $fromDate,
				'compare' => '<',
			),
			array(
				'key'     => $isDeliveredMetaKey,
				'value'   => $isDelivered,
				'compare' => '=',
			),
		);

		$metaSql = get_meta_sql($metaQuery, 'post', $wpdb->posts, 'ID');

		$lpc_shipping_method_names = self::getLpcShippingMethodsNameSqlReady();
		$prefix                    = $wpdb->prefix;

		$query = "SELECT DISTINCT wc_order_items.order_id FROM {$prefix}woocommerce_order_items AS wc_order_items
                    JOIN {$prefix}woocommerce_order_itemmeta  AS wc_order_itemmeta ON wc_order_itemmeta.order_item_id = wc_order_items.order_item_id
                    JOIN wp_posts on wc_order_items.order_id=wp_posts.ID";

		$query .= $metaSql['join'];

		$query .= " WHERE (wc_order_itemmeta.meta_key='method_id' AND wc_order_itemmeta.meta_value IN $lpc_shipping_method_names) " . $metaSql['where'];

		// phpcs:disable
		$results = $wpdb->get_results($query);
		// phpcs:enable

		$ordersId = array();

		if ($results) {
			foreach ($results as $result) {
				$ordersId[] = $result->order_id;
			}
		}

		return $ordersId;
	}

	public static function getLpcShippingMethodsNameSqlReady() {
		$lpc_shipping_methods = LpcRegister::get('shippingMethods')->getAllShippingMethods();
		array_walk($lpc_shipping_methods, array('self', 'formatTextForSql'));

		return '(' . implode(',', $lpc_shipping_methods) . ')';
	}

	protected static function formatTextForSql(&$text) {
		$text = "'" . $text . "'";
	}

	protected static function andCriterion($criterion) {
		global $wpdb;

		return " AND {$wpdb->prefix}woocommerce_order_items.order_id IN 
			                   (SELECT {$wpdb->prefix}postmeta.post_id FROM {$wpdb->prefix}postmeta WHERE
			                        (meta_key='_shipping_first_name' AND meta_value LIKE '%$criterion%')
			                        OR (meta_key='_shipping_last_name' AND meta_value LIKE '%$criterion%')
			                        OR (meta_key='_shipping_postcode' AND meta_value = '$criterion')
			                        OR (meta_key='_shipping_city' AND meta_value LIKE '%$criterion%')
			                        OR (meta_key='lpc_outward_parcel_number' AND meta_value LIKE '%$criterion%')
			                        OR (meta_key='_shipping_country' AND meta_value = '$criterion')
			                        OR (post_id LIKE '%$criterion%')) ";
	}

	protected static function getOrderBy($args) {
		global $wpdb;
		if (empty($args['orderby'])) {
			return " ORDER BY {$wpdb->prefix}posts.post_date DESC ";
		}

		switch ($args['orderby']) {
			case 'date':
				$ord = 'posts.post_date';
				break;
			case 'id':
				$ord = 'woocommerce_order_items.order_id';
				break;
			case 'label':
				$ord = 'lpc_label.outward_label';
				break;
			default:
				$ord = 'posts.post_date';
				break;
		}

		$ord = " ORDER BY {$wpdb->prefix}" . $ord . ' ';
		if (!empty($args['order'])) {
			$ord .= $args['order'] . ' ';
		}

		return $ord;
	}

}
