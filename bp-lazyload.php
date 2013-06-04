<?php
/*
Plugin Name: BP Lazy Load Avatars
Description: Only load avatars once they are visible.
Author: r-a-y
Author URI: http://buddypress.org/community/members/r-a-y/
Version: 0.1
License: GPLv2 or later
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'BP_LazyLoad' ) ) :

class BP_LazyLoad {

	public $version = '0.1';

	/**
	 * Init method.
	 */
	public function init() {
		return new self();
	}

	/**
	 * Constructor.
	 */
	function __construct() {
		// setup dummy image for lazyloading
		$this->dummy_image = apply_filters( 'bp_lazyload_dummy_image', plugin_dir_url( __FILE__ ) . 'bg.gif' );

		// enqueue JS
		add_action( 'wp_enqueue_scripts',       array( $this, 'js' ), 20 );

		// filter the avatar
		add_filter( 'bp_core_fetch_avatar',     array( $this, 'filter_avatar' ) );

		// add "data-src" attribute to <img> tag
		add_filter( 'bp_activity_allowed_tags', array( $this, 'allowed_tags' ) );
	}

	/**
	 * Enqueue JS.
	 */
	public function js() {
		// filter the JS handle to allow plugin devs to load existing lazyload lib
		$lazyload_js = apply_filters( 'bp_lazyload_unveil_handle', 'bp-unveil' );

		// enqueue our copy of lazyload if one isn't already enqueued
		if ( ! wp_script_is( $lazyload_js ) ) {
			wp_enqueue_script(
				$lazyload_js,
				plugin_dir_url( __FILE__ ) . 'jquery.unveil.min.js',
				array( 'jquery' ),
				$this->version,
				true
			);
		}

		// enqueue our customized JS
		wp_enqueue_script(
			'bp-lazyload-avatars',
			plugin_dir_url( __FILE__ ) . 'bp-lazyload.js',
			array( $lazyload_js ),
			$this->version,
			true
		);
	}

	/**
	 * Filter BP avatars to inject our 'data-src' attribute to the <img> tag.
	 */
	public function filter_avatar( $retval ) {
		// if not on the frontend, stop now!
		if ( ! did_action( 'get_header' ) )
			return $retval;

		$src_pos   = strpos( $retval, 'src=' );
		$class_pos = strpos( $retval, 'class=' );

		$avatar_url = substr( $retval, $src_pos + 5, $class_pos - 12 );
		$data_attr  = '" data-src="' . $avatar_url;

		return str_replace( $avatar_url, $this->dummy_image . $data_attr, $retval );
	}

	/**
	 * Let BP know of our custom 'data-src' attribute so it won't strip it out.
	 */
	public function allowed_tags( $retval ) {
		$retval['img']['data-src'] = array();

		return $retval;
	}
}

add_action( 'bp_init', array( 'BP_LazyLoad', 'init' ) );

endif;
