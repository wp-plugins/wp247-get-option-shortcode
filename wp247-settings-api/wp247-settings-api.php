<?php
/**
 * wp247 Settings API wrapper class
 *
 * @version 1.2
 *
 */
/* Skip namespace usage due to errors
namespace wp247sapi;
*/

/* Skip namespace usage due to errors
if ( !class_exists( '\wp247sapi\WP247_Settings_API' ) )
*/
if ( !class_exists( 'WP247_Settings_API' ) )
{

	class WP247_Settings_API
	{
		/**
		 * settings Menu array
		 *
		 * @var array
		 */
		static $version = '1.1';

		/**
		 * settings Menu array
		 *
		 * @var array
		 */
		private $settings_admin_menu = array();

		/**
		 * settings sections array
		 *
		 * @var array
		 */
		private $settings_sections = array();

		/**
		 * Settings fields array
		 *
		 * @var array
		 */
		private $settings_fields = array();

		/**
		 * settings InfoBar array
		 *
		 * @var array
		 */
		private $settings_infobar = array();

		/**
		 * Info Bar width %
		 *
		 * @var integer
		 */
		private $infobar_width = 20;

		/**
		 * Head scripts array
		 *
		 * @var array
		 */
		private $head_scripts = array();

		public function __construct()
		{
			add_action( 'admin_enqueue_scripts', array( $this, 'do_action_admin_enqueue_scripts' ) );
			add_action( 'admin_head', array( $this, 'do_action_admin_head' ), 9999 );
			add_action( 'admin_menu', array( $this, 'do_action_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'do_action_admin_init' ) );
		}

		/**
		 * Output head scripts and styles
		 */
		function do_action_admin_head()
		{
			if ( empty( $this->settings_admin_menu ) ) $this->set_admin_menu( $this->get_settings_admin_menu() );
			echo "
<script type='text/javascript'>
	var wp247sapi_plugin_name = '" . $this->settings_admin_menu[ 'page_title' ] . "'; 
	var wp247sapi_plugin_slug = '" . $this->settings_admin_menu[ 'menu_slug' ] . "'; 
</script>
";
			$this->set_head_scripts( $this->get_head_scripts() );
			if ( !empty( $this->head_scripts ) )
			{
				if ( !is_array( $this->head_scripts ) ) "\n" . $this->head_scripts . "\n";
				else echo "\n" . implode( "\n", $this->head_scripts ) . "\n";
			}
		}

		/**
		 * Enqueue scripts and styles
		 */
		function do_action_admin_enqueue_scripts()
		{
			if ( empty( $this->settings_admin_menu ) ) $this->set_admin_menu( $this->get_settings_admin_menu() );
			if ( $_GET[ 'page' ] == $this->settings_admin_menu[ 'menu_slug' ] )
			{
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_media();
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_style( 'wp247-settings-api-styles', plugins_url( 'wp247-settings-api.css', __FILE__ ) );
				wp_enqueue_script( 'wp247-settings-api-styles', plugins_url( 'wp247-settings-api.js', __FILE__ ), array( 'jquery' ) );
				$this->enqueue_scripts();
			}
		}

		/**
		 * Enqueue scripts and styles
		 *
		 * May be (but not required to be) overloaded
		 */
		function enqueue_scripts()
		{
			return;
		}

		/**
		 * Add settings option page
		 */
		function do_action_admin_menu()
		{
			if ( empty( $this->settings_admin_menu ) ) $this->set_admin_menu( $this->get_settings_admin_menu() );
			extract( $this->settings_admin_menu );
			if ( !isset( $capability ) ) $capability = 'manage_options';
			if ( !isset( $parent_slug ) or empty( $parent_slug ) ) $parent_slug = 'options-general';
			$parent_slug = strtolower( $parent_slug );
			if ( '.php' != substr( $parent_slug, -3, 4 ) ) $parent_slug .= '.php';
			add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, array( $this, 'show_settings_page' ) );
/*
			if ( isset( $parent_slug ) and !empty( $parent_slug ) )
			{
				$parent_slug = strtolower( $parent_slug );
				if ( '.php' != substr( $parent_slug, -4, 4 ) ) $parent_slug .= '.php';
				add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, array( $this, 'show_settings_page' ) );
			}
			else add_options_page( $page_title, $menu_title, $capability, $menu_slug, array( $this, 'show_settings_page' ) );
*/
		}

		/**
		 * Initialize and registers the settings sections and fileds to WordPress
		 *
		 * Usually this should be called at `admin_init` hook.
		 *
		 * This function gets the initiated settings sections and fields. Then
		 * registers them to WordPress and ready for use.
		 */
		function do_action_admin_init()
		{
			//register settings sections
			if ( empty( $this->settings_sections ) ) $this->set_sections( $this->get_settings_sections() );
			foreach ( $this->settings_sections as $section )
			{
				if ( !isset( $section['save'] ) or 'no' != $section['save'] )
				{
					if ( false == get_option( $section['id'] ) )
					{
						add_option( $section['id'] );
					}
				}

				if ( isset($section['desc']) && !empty($section['desc']) )
				{
					$section['desc'] = '<div class="inside">'.$section['desc'].'</div>';
					$callback = create_function('', 'echo "'.str_replace('"', '\"', $section['desc']).'";');
				}
				else
				{
					$callback = '__return_false';
				}

				add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
			}

			//register settings fields
			if ( empty( $this->settings_fields ) ) $this->set_fields( $this->get_settings_fields() );
			foreach ( $this->settings_fields as $section => $field )
			{
				foreach ( $field as $option )
				{
					$type = isset( $option['type'] ) ? $option['type'] : 'text';

					$args = array(
						'id' => $option['name'],
						'desc' => isset( $option['desc'] ) ? $option['desc'] : '',
				        'intro' => isset( $option['intro'] ) ? $option['intro'] : '',
						'name' => $option['label'],
						'section' => $section,
						'size' => isset( $option['size'] ) ? $option['size'] : null,
						'options' => isset( $option['options'] ) ? $option['options'] : '',
						'std' => isset( $option['default'] ) ? $option['default'] : '',
						'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
						);
					if ( isset( $option[ 'rows' ] ) ) $args[ 'rows' ] = $option[ 'rows' ];
					if ( isset( $option[ 'cols' ] ) ) $args[ 'cols' ] = $option[ 'cols' ];
					add_settings_field( $section . '[' . $option['name'] . ']', $option['label'], array( $this, 'callback_' . $type ), $section, $section, $args );
				}
			}

			// creates our settings in the options table
			foreach ( $this->settings_sections as $section )
			{
				register_setting( $section['id'], $section['id'], array( $this, 'sanitize_options' ) );
			}

			if ( empty( $this->settings_infobar ) ) $this->set_infobar( $this->get_settings_infobar() );
		}

		/**
		 * Set settings infobar
		 *
		 * @param array   $head_scripts head scripts array
		 */
		function set_head_scripts( $head_scripts )
		{
			if ( is_array( $head_scripts ) ) $this->head_scripts = $head_scripts;
			else $this->head_scripts = array( $head_scripts );

			return $this;
		}

		/**
		 * Add a single head script
		 *
		 * @param string $head_scripts
		 * @param array  $head_scripts
		 */
		function add_head_scripts( $head_scripts )
		{
			if ( !is_array( $head_scripts ) ) $this->head_scripts[] = $head_scripts;
			else $this->head_scripts = array_merge( $this->head_scripts, $head_scripts );

			return $this;
		}

		/**
		 * Returns the current head_script
		 *
		 * May be (but not required to be) overloaded
		 *
		 * @return array head scripts
		 */
		function get_head_scripts()
		{
			return $this->head_scripts;
		}

		/**
		 * Set settings admin menu
		 *
		 * @param array   $admin_menu setting admin_menu array
		 */
		function set_admin_menu( $admin_menu )
		{
			$this->settings_admin_menu = $admin_menu;

			return $this;
		}

		/**
		 * Add a single section
		 *
		 * @param array   $section
		 */
		function add_admin_menu( $admin_menu )
		{
			$this->settings_admin_menu[] = $admin_menu;

			return $this;
		}

		/**
		 * Returns the current admin_menu
		 *
		 * May be (but not required to be) overloaded
		 *
		 * @return array admin menu
		 */
		function get_settings_admin_menu()
		{
			return $this->admin_menu;
		}

		/**
		 * Set settings sections
		 *
		 * @param array   $sections setting sections array
		 */
		function set_sections( $sections )
		{
			$this->settings_sections = $sections;

			return $this;
		}

		/**
		 * Add a single section
		 *
		 * @param array   $section
		 */
		function add_section( $section )
		{
			$this->settings_sections[] = $section;

			return $this;
		}

		/**
		 * Returns the current sections
		 *
		 * May be (but not required to be) overloaded
		 *
		 * @return array section
		 */
		function get_settings_sections()
		{
			return $this->settings_sections;
		}

		/**
		 * Set settings fields
		 *
		 * @param array   $fields settings fields array
		 */
		function set_fields( $fields )
		{
			$this->settings_fields = $fields;

			return $this;
		}

		function add_field( $section, $field )
		{
			$defaults = array(
				'name' => '',
				'label' => '',
				'desc' => '',
	            'intro' => '',
				'type' => 'text'
			);

			$arg = wp_parse_args( $field, $defaults );
			$this->settings_fields[$section][] = $arg;

			return $this;
		}

		/**
		 * Returns the current fields
		 *
		 * May be (but not required to be) overloaded
		 *
		 * @return array fields
		 */
		function get_settings_fields()
		{
			return $this->settings_fields;
		}

		/**
		 * Set settings infobar
		 *
		 * @param array   $infobar setting infobar array
		 */
		function set_infobar( $infobar )
		{
			$this->settings_infobar = $infobar;

			return $this;
		}

		/**
		 * Add a single infobar
		 *
		 * @param array   $infobar
		 */
		function add_infobar( $infobar )
		{
			if ( !is_array( $infobar ) ) $this->settings_infobar[] = $infobar;
			else $this->settings_infobar = array_merge( $this->head_scripts, $infobar );

			return $this;
		}

		/**
		 * Returns an empty infobar
		 *
		 * May be (but not required to be) overloaded
		 *
		 * @return array settings infobar
		 */
		function get_settings_infobar()
		{
			return array();
		}

		/**
		 * Set infobar width
		 *
		 * @param integer   $infobar_width infobar width integer
		 */
		function set_infobar_width( $infobar_width )
		{
			$this->infobar_width = $infobar_width;

			return $this;
		}

		/**
		 * Returns the default infobar width
		 *
		 * May be (but not required to be) overloaded
		 *
		 * @return integer infobar width
		 */
		function get_infobar_width()
		{
			return $this->infobar_width;
		}

		/**
		 * Show settings page
		 */
		function show_settings_page()
		{
			$this->set_infobar_width( $this->get_infobar_width() );

			if ( isset( $this->settings_admin_menu[ 'page_title' ] ) and ! empty( $this->settings_admin_menu[ 'page_title' ] ) )
				echo '<h2>' . $this->settings_admin_menu[ 'page_title' ] . '</h2>';

			echo '<div class="wrap">';

			$this->show_navigation();

			if ( $this->infobar_width <= 0 or empty( $this->settings_infobar ) ) $this->show_forms();
			else
			{
				echo '<div style="float: left; width: ' . ( 100 - $this->infobar_width - 3 ) .'%;">';
				$this->show_forms();
				echo '</div><div style="float: right; width: ' . $this->infobar_width . '%;">';
				$this->show_infobar();
				echo '</div>';
			}

			echo '</div>';
		}

		/**
		 * Displays a text field for a settings field
		 *
		 * @param array   $args settings field args
		 */
		function callback_text( $args )
		{
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html = sprintf( '<input type="text" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
			$html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

			if ( !empty( $args['intro'] ) ) echo sprintf( '<span class="description">%s</span><br />', $args['intro'] );

			echo $html;
		}

		/**
		 * Displays a checkbox for a settings field
		 *
		 * @param array   $args settings field args
		 */
		function callback_checkbox( $args )
		{
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

			$html = sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
			$html .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s]" name="%1$s[%2$s]" value="on"%4$s />', $args['section'], $args['id'], $value, checked( $value, 'on', false ) );
			$html .= sprintf( '<label for="wpuf-%1$s[%2$s]"> %3$s</label>', $args['section'], $args['id'], $args['desc'] );

			if ( !empty( $args['intro'] ) ) echo sprintf( '<span class="description">%s</span><br />', $args['intro'] );

			echo $html;
		}

		/**
		 * Displays a multicheckbox settings field
		 *
		 * @param array   $args settings field args
		 */
		function callback_multicheck( $args )
		{
			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

			$html = '';
			foreach ( $args['options'] as $key => $label )
			{
$exp = explode( '/', $key );
$key = $exp[0];
$val = isset( $exp[1] ) ? $exp[1] : $key;
				$checked = isset( $value[$key] ) ? $value[$key] : '0';
//				$html .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s"%4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
				$html .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%4$s"%5$s />', $args['section'], $args['id'], $key, $val, checked( $checked, $val, false ) );
				$html .= sprintf( '<label for="wpuf-%1$s[%2$s][%4$s]"> %3$s</label><br>', $args['section'], $args['id'], $label, $key );
			}
			$html .= sprintf( '<span class="description"> %s</label>', $args['desc'] );

			if ( !empty( $args['intro'] ) ) echo sprintf( '<span class="description">%s</span><br />', $args['intro'] );

			echo $html;
		}

		/**
		 * Displays a radio settings field
		 *
		 * @param array   $args settings field args
		 */
		function callback_radio( $args )
		{
			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

			$html = '';
			foreach ( $args['options'] as $key => $label )
			{
				$html .= sprintf( '<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
				$html .= sprintf( '<label for="wpuf-%1$s[%2$s][%4$s]"> %3$s</label><br>', $args['section'], $args['id'], $label, $key );
			}
			$html .= sprintf( '<span class="description"> %s</label>', $args['desc'] );

			if ( !empty( $args['intro'] ) ) echo sprintf( '<span class="description">%s</span><br />', $args['intro'] );

			echo $html;
		}

		/**
		 * Displays a selectbox for a settings field
		 *
		 * @param array   $args settings field args
		 */
		function callback_select( $args )
		{
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );
			foreach ( $args['options'] as $key => $label )
			{
				$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
			}
			$html .= sprintf( '</select>' );
			$html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

			if ( !empty( $args['intro'] ) ) echo sprintf( '<span class="description">%s</span><br />', $args['intro'] );

			echo $html;
		}

		/**
		 * Displays a textarea for a settings field
		 *
		 * @param array   $args settings field args
		 */
		function callback_textarea( $args )
		{
			$value = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html = sprintf( '<textarea rows="%5$s" cols="%6$s" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]">%4$s</textarea>', $size, $args['section'], $args['id'], $value, ( isset( $args['rows'] ) ? $args['rows'] : 5 ), ( isset( $args['cols'] ) ? $args['cols'] : 55 ) );
			$html .= sprintf( '<br><span class="description"> %s</span>', $args['desc'] );

			if ( !empty( $args['intro'] ) ) echo sprintf( '<span class="description">%s</span><br />', $args['intro'] );

			echo $html;
		}

		/**
		 * Displays a textarea for a settings field
		 *
		 * @param array   $args settings field args
		 */
		function callback_html( $args )
		{
			echo $args['desc'];
		}

		/**
		 * Displays a rich text textarea for a settings field
		 *
		 * @param array   $args settings field args
		 */
		function callback_wysiwyg( $args )
		{
			$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
			$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : '500px';

			if ( !empty( $args['intro'] ) ) echo sprintf( '<span class="description">%s</span><br />', $args['intro'] );

			echo '<div style="width: ' . $size . ';">';

			wp_editor( $value, $args['section'] . '-' . $args['id'] . '', array( 'teeny' => true, 'textarea_name' => $args['section'] . '[' . $args['id'] . ']', 'textarea_rows' => 10 ) );

			echo '</div>';

			echo sprintf( '<br><span class="description"> %s</span>', $args['desc'] );
		}

		/**
		 * Displays a file upload field for a settings field
		 *
		 * @param array   $args settings field args
		 */
		function callback_file( $args )
		{
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
			$id = $args['section']  . '[' . $args['id'] . ']';

			$html  = sprintf( '<input type="text" class="%1$s-text wp247sapi-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
			$html .= '<input type="button" class="button wp247sapi-browse" value="'.__( 'Browse' ).'" />';

			$html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

			if ( !empty( $args['intro'] ) ) echo sprintf( '<span class="description">%s</span><br />', $args['intro'] );

			echo $html;
		}

		/**
		 * Displays a password field for a settings field
		 *
		 * @param array   $args settings field args
		 */
		function callback_password( $args )
		{
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
			$html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

			if ( !empty( $args['intro'] ) ) echo sprintf( '<span class="description">%s</span><br />', $args['intro'] );

			echo $html;
		}

		/**
		 * Displays a color picker field for a settings field
		 *
		 * @param array   $args settings field args
		 */
		function callback_color( $args )
		{
			$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
			$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

			$html = sprintf( '<input type="text" class="%1$s-text wp247sapi-color-picker-field" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" />', $size, $args['section'], $args['id'], $value, $args['std'] );
			$html .= sprintf( '<span class="description" style="display:block;"> %s</span>', $args['desc'] );

			if ( !empty( $args['intro'] ) ) echo sprintf( '<span class="description">%s</span><br />', $args['intro'] );

			echo $html;
		}

		/**
		 * Sanitize callback for Settings API
		 */
		function sanitize_options( $options )
		{
			if ( is_array( $options ) )
			{
				foreach( $options as $option_slug => $option_value )
				{
					$sanitize_callback = $this->get_sanitize_callback( $option_slug );

					// If callback is set, call it
					if ( $sanitize_callback )
					{
						$options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value, $option_slug );
						continue;
					}
				}
			}

			return $options;
		}

		/**
		 * Get sanitization callback for given option slug
		 *
		 * @param string $slug option slug
		 *
		 * @return mixed string or bool false
		 */
		function get_sanitize_callback( $slug = '' )
		{
			if ( empty( $slug ) ) return false;

			// Iterate over registered fields and see if we can find proper callback
			foreach( $this->settings_fields as $section => $options )
			{
				foreach ( $options as $option )
				{
					if ( $option['name'] != $slug ) continue;

					// Return the callback name
					return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
				}
			}

			return false;
		}

		/**
		 * Get the value of a settings field
		 *
		 * @param string  $option  settings field name
		 * @param string  $section the section name this field belongs to
		 * @param string  $default default text if it's not found
		 * @return string
		 */
		function get_option( $option, $section, $default = '' )
		{
			$options = get_option( $section );

			if ( isset( $options[$option] ) )
			{
				return $options[$option];
			}

			return $default;
		}

		/**
		 * Show navigations as tab
		 *
		 * Shows all the settings section labels as tab
		 */
		function show_navigation()
		{
			if ( count( $this->settings_sections ) <= 1 ) return;

			$html = '<h2 class="nav-tab-wrapper">';

			foreach ( $this->settings_sections as $tab )
			{
				$html .= sprintf( '<a href="#%1$s" class="nav-tab" id="%1$s_tab">%2$s</a>', $tab['id'], $tab['title'] );
			}

			$html .= '</h2>';

			echo $html;
		}

		/**
		 * Show the section settings forms
		 *
		 * This function displays every section in a different form
		 */
		function show_forms()
		{
			?>
			<div class="metabox-holder">
				<div class="postbox">
					<?php foreach ( $this->settings_sections as $form ) { ?>
						<div id="<?php echo $form['id']; ?>" class="wp247sapi-form">
							<form method="post" action="options.php">

								<?php do_action( 'wp247sapi_form_top_' . $form['id'], $form ); ?>
								<?php settings_fields( $form['id'] ); ?>
								<?php do_settings_sections( $form['id'] ); ?>
								<?php do_action( 'wp247sapi_form_bottom_' . $form['id'], $form ); ?>

<?php if ( !isset( $form['save'] ) or 'no' != $form['save'] ) : ?>
								<div style="padding-left: 10px">
									<?php submit_button(); ?>
								</div>
<?php endif; ?>
							</form>
						</div>
					<?php } ?>
				</div>
			</div>
			<?php
		}

		/**
		 * Show the section settings forms
		 *
		 * This function displays the info bar
		 */
		function show_infobar()
		{
			$infobar = $this->settings_infobar;
			if ( !is_array( $infobar ) ) $infobar = array( $infobar );
			foreach ( $infobar as $title => $content )
			{
				$ib = ( is_numeric( $title ) ? $content : '<h3>' . $title . '</h3>' . $content );
				echo '<div class="postbox" style="float: right; min-width: 100%; width: 100%; margin: 10px 0 0 0; padding: 0 10px 0 10px; height: 100%;">' . $ib . '</div>';
			}
		}

	}

} // if ( !class_exists( 'WP247_Settings_API' ) )