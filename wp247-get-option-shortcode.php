<?php
/*
	Plugin Name: WP247 Get Option Shortcode
	Version: 1.0
	Description: Include WordPress options anywhere shortcodes are accepted

	Tags: options, shortcode, get_option
	Author: wp247
	Author URI: http://wp247.net/
	Uses: weDevs Settings API wrapper class from http://tareq.weDevs.com Tareq's Planet
*/

if ( !class_exists( 'WP247_get_option_shortcode' ) )
{
	class WP247_get_option_shortcode
	{
		private $options;
		
		private $defaults = array( 'shortcode' => 'wp247_get_option'
								  ,'execution' => array( 'widget_text'		=> 'widget_text'
														,'comment_excerpt'	=> 'comment_excerpt'
														,'comment_text'		=> 'comment_text'
														,'the_content_rss'	=> 'the_content_rss'
														,'the_content_feed'	=> 'the_content_feed'
														,'the_excerpt_rss'	=> 'the_excerpt_rss'
														,'comment_text_rss'	=> 'comment_text_rss'
														)
								 );
		
		private $shortcode_atts = array( 'option' => '', 'default' => '', 'scope' => '', 'translate' => '' );
		
		function __construct()
		{
			add_action( 'wp_loaded', array( &$this, 'do_action_wp_loaded' ) );
			$shortcode = $this->get_option( 'shortcode' );
			if ( !empty( $shortcode ) )
			{
				add_shortcode( $shortcode, array( &$this, 'do_shortcode' ) );
				if ( isset( $this->options[ 'execution' ] ) and is_array( $this->options[ 'execution' ] ) )
				{
					foreach ( $this->options[ 'execution' ] as $key => $value ) add_filter( $key, 'do_shortcode' );
				}
			}
		}

		public function do_action_wp_loaded()
		{
			if ( current_user_can( 'manage_options' ) )
				require_once dirname( __FILE__ ) . '/wp247-get-option-shortcode-admin.php';
		}

		private function get_options()
		{
			if ( empty( $this->options ) )
			{
				$this->options = get_option( 'wp247_get_option_shortcode', null );
				if ( empty( $this->options ) )
				{
					$this->options = $this->defaults;
					add_option( 'wp247_get_option_shortcode', $this->options );
				}
			}
			return $this->options;
		}

		public function get_option( $option, $default = null )
		{
			$options = $this->get_options();
			if ( isset( $options[ $option ] ) and !empty( $options[ $option ] ) ) $value  = $options[ $option ];
			else if ( !empty( $default ) ) $value = $default;
			else if ( isset( $this->defaults[ $option ] ) ) $value = $this->defaults[ $option ];
			else $value = null;
			if ( is_array( $value ) ) $value = serialize( $value );
			return $value;
		}

		public function do_shortcode( $atts, $content = null, $tag = null )
		{
			extract( shortcode_atts( $this->shortcode_atts, $atts ) );
			if ( 'site' == strtolower( $scope ) )
				$value = empty( $option ) ? '' : get_site_option( $option, $default );
			else $value = empty( $option ) ? '' : get_option( $option, $default );
			if ( !empty( $translate ) )
			{
			}
			return $value;
		}

	}

	global $wp247_option_shortcode;
	$wp247_option_shortcode = new WP247_get_option_shortcode();
}
