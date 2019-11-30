<?php

/**
 * Class LpcHelper
 */
class LpcHelper {

	const CONFIG_FILE = 'config_options.json';

	protected static $configOptions;

	public static function renderPartialInLayout($name, $args = array()) {
		$content = self::renderPartial($name, $args);
		return self::renderPartial('layout.php', ['content' => $content]);
	}

	public static function renderPartial($name, $args = array()) {
		$prefix = is_admin() ? 'admin' : 'public';
		$prefix = LPC_FOLDER . $prefix . DS . 'partials' . DS;

		$file = $prefix . $name;
		if (!file_exists($file)) {
			$prefix = LPC_INCLUDES . 'partials' . DS;
			$file   = $prefix . $name;
		}

		if (!file_exists($file) && defined('DOING_AJAX') && DOING_AJAX) {
			$prefix = LPC_PUBLIC . 'partials' . DS;
			$file   = $prefix . $name;
		}

		if (!file_exists($file)) {
			LpcLogger::warn('No such partial!', ['partial' => $name]);

			return null;
		}

		ob_start();
		include $file;

		return ob_get_clean();
	}

	public static function enqueueScript($handle, $srcAdmin = null, $srcFront = null, $dep = array(), $localizeObject = '', $localizeVars = array()) {
		if (null !== $srcAdmin) {
			self::enqueueScripts('admin_enqueue_scripts', $handle, $srcAdmin, $dep, $localizeObject, $localizeVars);
		}
		if (null !== $srcFront) {
			self::enqueueScripts('wp_enqueue_scripts', $handle, $srcFront, $dep, $localizeObject, $localizeVars);
		}
	}

	private static function enqueueScripts($hook, $handle, $src, $dep, $localizeObject, $localizeVars) {
		add_action(
			$hook,
			function () use ($handle, $src, $dep, $localizeObject, $localizeVars) {
				wp_register_script($handle, $src, $dep, LPC_VERSION, true);
				if (!empty($localizeObject)) {
					wp_localize_script($handle, $localizeObject, $localizeVars);
				}
				wp_enqueue_script($handle);
			}
		);
	}

	public static function enqueueStyle($handle, $srcAdmin = null, $srcFront = null, $dep = array()) {
		if (null !== $srcAdmin) {
			add_action(
				'admin_enqueue_scripts',
				function () use ($handle, $srcAdmin, $dep) {
					wp_enqueue_style($handle, $srcAdmin, $dep, LPC_VERSION);
				}
			);
		}
		if (null !== $srcFront) {
			add_action(
				'wp_enqueue_scripts',
				function () use ($handle, $srcFront, $dep) {
					wp_enqueue_style($handle, $srcFront, $dep, LPC_VERSION);
				}
			);
		}
	}

	public static function displayNotice($type, $message) {
		self::renderPartial(
			'notice.php',
			array(
				'message' => $message,
				'type'    => $type,
			)
		);
	}

	public static function displayNoticeException(Exception $e) {
		self::displayNotice('error', $e->getMessage());
	}

	/**
	 * @param        $var
	 * @param        $default
	 * @param string $type
	 * @param string $hash
	 *
	 * @return array|string
	 */
	public static function getVar($var, $default = '', $type = 'string', $hash = 'REQUEST') {

		// TODO: Handle the hash if needed
		$input = $_REQUEST;

		$result = isset($input[$var]) ? $input[$var] : $default;

		// TODO: Handle the type filtering
		if ('string' == $type) {
			$result = (string) $result;
		}

		return wp_unslash($result);
	}

	/**
	 * @param        $option
	 * @param string $default
	 *
	 * @return array|string
	 */
	public static function get_option($option, $default = '') {
		if (get_option($option)) {
			return get_option($option);
		}
		if ('' !== $default) {
			return $default;
		}
		if (null === self::$configOptions) {
			$configStructure     = file_get_contents(LPC_RESOURCE_FOLDER . self::CONFIG_FILE);
			self::$configOptions = new ArrayObject(json_decode($configStructure, true));
		}
		foreach (self::$configOptions as $configOption) {
			if (array_key_exists('id', $configOption) && $configOption['id'] === $option) {
				if (array_key_exists('default', $configOption)) {
					return $configOption['default'];
				} else {
					return $default;
				}
			}
		}

		return $default;
	}
}
