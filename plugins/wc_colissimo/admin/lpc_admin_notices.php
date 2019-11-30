<?php

class LpcAdminNotices extends LpcComponent {
	public function add_notice($notice, $class, $message) {
		if (PHP_SESSION_NONE === session_status()) {
			session_start();
		}
		$_SESSION[$notice][$class][] = $message;
	}

	public function get_notice($notice) {
		if (PHP_SESSION_NONE === session_status()) {
			session_start();
		}
		if (isset($_SESSION[$notice])) {
			$notice_content = '';
			foreach ($_SESSION[$notice] as $oneClass => $oneNotice) {
				$notice_content .= '<div class="notice is-dismissible ' . esc_attr($oneClass) . '"><p>' . implode('<br />', $oneNotice) . ' </p ></div > ';
			}
			unset($_SESSION[$notice]);

			return $notice_content;
		} else {
			return false;
		}
	}
}
