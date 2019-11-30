<?php

/**
 * The file that defines Shortcodes class
 *
 * A class definition that includes functions used for Shortcodes.
 *
 * @since      1.0.0
 *
 */

/**
 * Shortcodes class.
 *
 * This is used to define functions for Shortcodes.
 *
 * @since      1.0.0
 */
class Sassy_Social_Share_Shortcodes {

	/**
	 * Options saved in database.
	 *
	 * @since    1.0.0
	 */
	private $options;

	/**
	 * Member to assign object of Sassy_Social_Share_Public Class.
	 *
	 * @since    1.0.0
	 */
	private $public_class_object;

	/**
	 * Assign plugin options to private member $options.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $options, $public_class_object ) {

		$this->options = $options;
		$this->public_class_object = $public_class_object;

	}

	/** 
	 * Shortcode for Social Sharing.
	 */
	public function follow_icons_shortcode( $params ) {

		extract( shortcode_atts( array(
			'style' => '',
			'width' => '32',
			'height' => '32',
			'shape' => 'square',
			'social_networks' => '',
			'type' => 'standard',
			'theme' => '',
			'left' => '0',
			'right' => '0',
			'top' => '100',
			'align' => 'left',
			'title' => ''
		), $params ) ); 

		$html = '';

		if ( $social_networks ) {
			$networks = explode( ',', $social_networks );

			$icon_style = 'width:' . $width . 'px;height:' . $height . 'px;' . ( $shape == 'round' ? 'border-radius:999px;' : '' );

			if ( $theme == '' ) {
				$icon_theme = $theme;
			} elseif ( $theme == 'standard' ) {
				$icon_theme = $theme . '_';
			} elseif ( $theme == 'floating' ) {
				$icon_theme = $theme . '_';
			}

			$html .= '<div ' . ( $type == 'floating' ? 'style="position:fixed;top:' . $top . 'px;' . $align . ':' . $$align . 'px;width:' . $width . 'px;"'  : '' ) .  'class="heateor_sss_' . $icon_theme . 'follow_icons_container">';


			if ( ! empty( $title ) ) {
				if ( $type == 'floating' ) {
					$html .= '<div class="heateor_sss_follow_icons_title" style="text-align:center;font-size:' . $width*30/100 . 'px">';
				}
				$html .= $title;
				if ( $type == 'floating' ) {
					$html .= '</div>';
				}
			}

			$html .= '<ul class="heateor_sss_follow_ul">';
			
			// follow icons
			foreach ( $networks as $value ) {
				$networks_link = explode( '=', trim( $value ) );
				$html .= '<li class="heateorSssSharingRound"><i style="' . $icon_style . '" alt="' . ucfirst( trim( $networks_link[0] ) ) . '" title="' . ucfirst( trim( $networks_link[0] ) ) . '" class="heateorSssSharing heateorSss' . ucfirst( $networks_link[0] ) . 'Background"><a target="_blank" aria-label="' . ucfirst( trim( $networks_link[0] ) ) . '" href="' . trim( $networks_link[1] ) . '" rel="noopener"><ss style="display:block" class="heateorSssSharingSvg heateorSss' . ucfirst( trim( $networks_link[0] ) ) . 'Svg"></ss></a></i></li>';
			}
			$html .= '</ul>';
			$html .= '<div style="clear:both"></div>';
			$html .= '</div>';

		}

		return $html;

	}

	/** 
	 * Shortcode for Social Sharing
	 *
	 * @since    1.0.0
	 */ 
	public function sharing_shortcode( $params ) {
		
		extract( shortcode_atts( array(
			'style' => '',
			'type' => 'standard',
			'left' => '0',
			'right' => '0',
			'top' => '100',
			'url' => '',
			'count' => 0,
			'align' => 'left',
			'title' => '',
			'total_shares' => 'OFF'
		), $params ) );
		
		$type = strtolower( $type );

		if ( ( $type == 'standard' && ! isset( $this->options['hor_enable'] ) ) || ( $type == 'floating' && ! isset( $this->options['vertical_enable'] ) ) || ( ! isset( $this->options['amp_enable'] ) && $this->public_class_object->is_amp_page() ) ) {
			return;
		}
		global $post;
		if ( ! is_object( $post ) ) {
	        return;
		}
		if ( $url ) {
			$target_url = $url;
			$post_id = 0;
		} elseif ( is_front_page() ) {
			$target_url = esc_url( home_url() );
			$post_id = 0;
		} elseif ( ! is_singular() && $type == 'vertical' ) {
			$target_url = html_entity_decode( esc_url( $this->public_class_object->get_http_protocol() . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ) );
			$post_id = 0;
		} elseif ( isset( $_SERVER['QUERY_STRING'] ) && $_SERVER['QUERY_STRING'] ) {
			$target_url = html_entity_decode( esc_url( $this->public_class_object->get_http_protocol() . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ) );
			$post_id = $post -> ID;
		} elseif ( get_permalink( $post -> ID ) ) {
			$target_url = get_permalink( $post -> ID );
			$post_id = $post -> ID;
		} else {
			$target_url = html_entity_decode( esc_url( $this->public_class_object->get_http_protocol() . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ) );
			$post_id = 0;
		}
		$share_count_url = $target_url;
		if ( $url == '' && is_singular() ) {
			$share_count_url = get_permalink( $post -> ID );
		}
		$custom_post_url = $this->public_class_object->apply_target_share_url_filter( $target_url, $type, false );
		if ( $custom_post_url != $target_url ) {
			$target_url = $custom_post_url;
			$share_count_url = $target_url;
		}
		// generate short url
		$short_url = $this->public_class_object->get_short_url( $target_url, $post_id );
		$alignment_offset = 0;
		if ( $left) {
			$alignment_offset = $left;
		} elseif ( $right) {
			$alignment_offset = $right;
		}

		// share count transient ID
		$this->public_class_object->share_count_transient_id = $this->public_class_object->get_share_count_transient_id( $target_url );
		$cached_share_count = $this->public_class_object->get_cached_share_count( $this->public_class_object->share_count_transient_id );

		$html = '<div class="heateor_sss_sharing_container heateor_sss_' . ( $type == 'standard' ? 'horizontal' : 'vertical' ) . '_sharing' . ( $type == 'floating' && isset( $this->options['hide_mobile_sharing'] ) ? ' heateor_sss_hide_sharing' : '' ) . ( $type == 'floating' && isset( $this->options['bottom_mobile_sharing'] ) ? ' heateor_sss_bottom_sharing' : '' ) . '" ss-offset="' . $alignment_offset . '" ' . ( $this->public_class_object->is_amp_page() ? "" : "heateor-sss-data-href='" . ( isset( $share_count_url ) && $share_count_url ? $share_count_url : $target_url ) . "'" ) . ( ( $cached_share_count === false || $this->public_class_object->is_amp_page() ) ? "" : 'heateor-sss-no-counts="1" ' );
		$vertical_offsets = '';
		if ( $type == 'floating' ) {
			$vertical_offsets = $align . ': ' . $$align . 'px; top: ' . $top . 'px;width:' . ( ( $this->options['vertical_sharing_size'] ? $this->options['vertical_sharing_size'] : '35' ) + 4 ) . "px;";
		}
		// style 
		if ( $style != "" || $vertical_offsets != '' ) {
			$html .= 'style="';
			if ( strpos( $style, 'background' ) === false ) { $html .= '-webkit-box-shadow:none;box-shadow:none;'; }
			$html .= $vertical_offsets;
			$html .= $style;
			$html .= '"';
		}
		$html .= '>';
		if ( $type == 'standard' && $title != '' ) {
			$html .= '<div class="heateor_sss_sharing_title" style="font-weight:bold">' . ucfirst( $title ) . '</div>';
		}
		
		$html .= $this->public_class_object->prepare_sharing_html( $short_url ? $short_url : $target_url, $type == 'standard' ? 'horizontal' : 'vertical', $count, $total_shares == 'ON' ? 1 : 0 );
		$html .= '</div>';
		if ( ( $count || $total_shares == 'ON' )  && $cached_share_count === false ) {
			$html .= '<script>heateorSssLoadEvent(function(){heateorSssCallAjax(function(){heateorSssGetSharingCounts();});});</script>';
		}
		return $html;
	}

}
