<?php
/**
 * Plugin Name:       The Events Calendar Extension: Calendar Widget Areas
 * Plugin URI:        https://theeventscalendar.com/extensions/calendar-widget-areas/
 * GitHub Plugin URI: https://github.com/mt-support/tribe-ext-calendar-widget-areas
 * Description:       Adds widget areas (a.k.a. sidebars) that only display on The Events Calendar pages/views.
 * Version:           1.1.0
 * Extension Class:   Tribe\Extensions\Calendar_Widget_Areas\Main
 * Author:            Modern Tribe, Inc.
 * Author URI:        http://m.tri.be/1971
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       tribe-ext-calendar-widget-areas
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

namespace Tribe\Extensions\Calendar_Widget_Areas;

use Tribe__Autoloader;
use Tribe__Events__Main;
use Tribe__Extension;

/**
 * Define Constants
 */

if ( ! defined( __NAMESPACE__ . '\NS' ) ) {
	define( __NAMESPACE__ . '\NS', __NAMESPACE__ . '\\' );
}

if ( ! defined( NS . 'PLUGIN_TEXT_DOMAIN' ) ) {
	// `Tribe\Extensions\Calendar_Widget_Areas\PLUGIN_TEXT_DOMAIN` is defined
	define( NS . 'PLUGIN_TEXT_DOMAIN', 'tribe-ext-calendar-widget-areas' );
}

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
if (
	class_exists( 'Tribe__Extension' )
	&& ! class_exists( Main::class )
) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Main extends Tribe__Extension {

		/**
		 * @var Tribe__Autoloader
		 */
		private $class_loader;

		/**
		 * Setup the Extension's properties.
		 *
		 * This always executes even if the required plugins are not present.
		 */
		public function construct() {
			$this->add_required_plugin( 'Tribe__Events__Main' );
		}

		/**
		 * Get this plugin's options prefix.
		 *
		 * Settings_Helper will append a trailing underscore before each option.
		 *
		 * @see \Tribe\Extensions\Calendar_Widget_Areas\Settings::set_options_prefix()
		 *
		 * @return string
		 */
		private function get_options_prefix() {
			return (string) str_replace( '-', '_', PLUGIN_TEXT_DOMAIN );
		}

		/**
		 * Get Settings instance.
		 *
		 * @return Settings
		 */
		private function get_settings() {
			if ( empty( $this->settings ) ) {
				$this->settings = new Settings( $this->get_options_prefix() );
			}

			return $this->settings;
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {
			load_plugin_textdomain( PLUGIN_TEXT_DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages/' );

			if ( ! $this->php_version_check() ) {
				return;
			}

			$this->class_loader();

			$this->get_settings();

			// Register all widget areas.
			add_action( 'widgets_init', array( $this, 'register_sidebars' ) );

			add_action( 'init', array( $this, 'enqueue_styles' ) );

			// Add filters and actions based on choices.
			foreach ( $this->get_enabled_areas_full_details() as $value ) {
				if ( method_exists( $this, $value['method'] ) ) {
					if ( empty( $value['priority'] ) ) {
						$priority = 10;
					} else {
						$priority = $value['priority'];
					}

					$priority = apply_filters( 'tribe_ext_calendar_widget_area_priority', $priority, $value );

					$priority = (int) $priority;

					if ( ! empty( $value['filter'] ) ) {
						add_filter( $value['hook'], array( $this, $value['method'] ), $priority );
					} else {
						add_action( $value['hook'], array( $this, $value['method'] ), $priority );
					}
				}
			}
		}

		/**
		 * Check if we have a sufficient version of PHP. Admin notice if we don't and user should see it.
		 *
		 * @link https://theeventscalendar.com/knowledgebase/php-version-requirement-changes/ All extensions require PHP 5.6+.
		 *
		 * @return bool
		 */
		private function php_version_check() {
			$php_required_version = '5.6';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if (
					is_admin()
					&& current_user_can( 'activate_plugins' )
				) {
					$message = '<p>';

					$message .= sprintf( __( '%s requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.', PLUGIN_TEXT_DOMAIN ), $this->get_name(), $php_required_version );

					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );

					$message .= '</p>';

					tribe_notice( PLUGIN_TEXT_DOMAIN . '-php-version', $message, [ 'type' => 'error' ] );
				}

				return false;
			}

			return true;
		}

		/**
		 * Use Tribe Autoloader for all class files within this namespace in the 'src' directory.
		 *
		 * @return Tribe__Autoloader
		 */
		public function class_loader() {
			if ( empty( $this->class_loader ) ) {
				$this->class_loader = new Tribe__Autoloader;
				$this->class_loader->set_dir_separator( '\\' );
				$this->class_loader->register_prefix(
					NS,
					__DIR__ . DIRECTORY_SEPARATOR . 'src'
				);
			}

			$this->class_loader->register_autoloader();

			return $this->class_loader;
		}

		/**
		 * Register all widget areas.
		 */
		public function register_sidebars() {
			foreach ( $this->get_enabled_areas_full_details() as $value ) {
				register_sidebar(
					array(
						'name'        => $value['name'],
						'id'          => "tec_ext_widget_areas__{$value['method']}",
						'description' => $value['desc'],
					)
				);
			}
		}

		/**
		 * Register and enqueue stylesheet
		 */
		public function enqueue_styles() {
			wp_register_style( 'tribe-ext-calendar-widget-areas', plugin_dir_url( __FILE__ ) . 'src/resources/css/tribe-ext-calendar-widget-areas.css', array(), $this->get_version() );

			wp_enqueue_style( 'tribe-ext-calendar-widget-areas' );
		}

		/**
		 * Get all of this extension's options.
		 *
		 * @return array
		 */
		public function get_all_options() {
			$settings = $this->get_settings();

			return $settings->get_all_options();
		}

		/**
		 * All available widget areas this extension supports and details about each in a multidimensional associative array.
		 *
		 * Follow this format to enable a new widget area for each action or filter hook
		 * you want to use. You will also need to add an additional method toward the
		 * end of this file for each area added so we know what code to execute.
		 *
		 * @return array {
		 *     Every widget area supported by this extension
		 *
		 *                         {
		 *                         The details for each widget area
		 *
		 * @param array  $hook     The action or filter hook to be used.
		 * @param string $method   The method of this class that outputs the widget. This value may prefix with context, such as "single_", and the actual function named accordingly and having the necessary logic in place. The actual function names are "tec_ext_widget_areas__" + the 'method' name from here.
		 * @param string $name     Name of the widget in the admin screens
		 * @param string $desc     Widget description
		 * @param bool   $filter   Optional: If $hook is a 'filter' hook instead of an action hook, set this to (bool) true.
		 * @param int    $priority Optional: Set this if desired. Will default to 10 if not set.
		 *                         }
		 *                         }
		 */
		protected function get_all_areas() {
			$areas = array(
				// template
				array(
					'hook'   => 'tribe_events_before_template',
					'method' => 'before_template',
					'name'   => __( 'TEC Above Calendar', PLUGIN_TEXT_DOMAIN ),
					'desc'   => __( 'Widgets in this area will be shown ABOVE The Events Calendar.', PLUGIN_TEXT_DOMAIN ),
				),
				array(
					'hook'   => 'tribe_events_after_template',
					'method' => 'after_template',
					'name'   => __( 'TEC Below Calendar', PLUGIN_TEXT_DOMAIN ),
					'desc'   => __( 'Widgets in this area will be shown BELOW The Events Calendar.', PLUGIN_TEXT_DOMAIN ),
				),
				// single
				// template
				array(
					'hook'   => 'tribe_events_before_view',
					'method' => 'single_before_view',
					'name'   => __( 'TEC Single: Top', PLUGIN_TEXT_DOMAIN ),
					'desc'   => __( 'Widgets in this area will be shown ABOVE Single Events.', PLUGIN_TEXT_DOMAIN ),
				),
				// description
				array(
					'hook'   => 'tribe_events_single_event_before_the_content',
					'method' => 'single_event_before_the_content',
					'name'   => __( 'TEC Single: Above Description', PLUGIN_TEXT_DOMAIN ),
					'desc'   => __( 'Widgets in this area will be shown ABOVE the Single Event Description.', PLUGIN_TEXT_DOMAIN ),
				),
				// description
				array(
					'hook'   => 'tribe_events_single_event_after_the_content',
					'method' => 'single_event_after_the_content',
					'name'   => __( 'TEC Single: Below Description', PLUGIN_TEXT_DOMAIN ),
					'desc'   => __( 'Widgets in this area will be shown BELOW the Single Event Description.', PLUGIN_TEXT_DOMAIN ),
				),
				// details
				array(
					'hook'   => 'tribe_events_single_event_before_the_meta',
					'method' => 'single_event_before_the_meta',
					'name'   => __( 'TEC Single: Above Details', PLUGIN_TEXT_DOMAIN ),
					'desc'   => __( 'Widgets in this area will be shown ABOVE the Single Event Details.', PLUGIN_TEXT_DOMAIN ),
				),
				// details
				array(
					'hook'     => 'tribe_events_single_event_after_the_meta',
					'method'   => 'single_event_after_the_meta_early',
					'name'     => __( 'TEC Single: Below Details (Before)', PLUGIN_TEXT_DOMAIN ),
					'desc'     => __( 'Widgets in this area will be shown DIRECTLY BELOW the Single Event Details (before Related Events and Tickets, if displayed).', PLUGIN_TEXT_DOMAIN ),
					'priority' => 1,
				),
				// details
				array(
					'hook'     => 'tribe_events_single_event_after_the_meta',
					'method'   => 'single_event_after_the_meta_late',
					'name'     => __( 'TEC Single: Below Details (After)', PLUGIN_TEXT_DOMAIN ),
					'desc'     => __( 'Widgets in this area will be shown BELOW the Single Event Details (after Related Events and Tickets, if displayed).', PLUGIN_TEXT_DOMAIN ),
					'priority' => 100,
				),
				// template
				array(
					'hook'   => 'tribe_events_after_view',
					'method' => 'single_after_view',
					'name'   => __( 'TEC Single: Bottom', PLUGIN_TEXT_DOMAIN ),
					'desc'   => __( 'Widgets in this area will be shown BELOW Single Events.', PLUGIN_TEXT_DOMAIN ),
				),
			);

			return apply_filters( 'tribe_ext_calendar_widget_areas', $areas );
		}

		/**
		 * Get all method names
		 *
		 * @return array
		 */
		protected function get_all_areas_simple() {
			return wp_list_pluck( $this->get_all_areas(), 'method' );
		}

		/**
		 * Convert All Areas array from indexed array to associative array, using 'method' as the key.
		 *
		 * @return array
		 */
		protected function get_all_areas_assoc() {
			$all_available_assoc = array();

			foreach ( $this->get_all_areas() as $value ) {
				$method_name                       = $value['method'];
				$all_available_assoc[$method_name] = $value;
			}

			return $all_available_assoc;
		}


		/**
		 * Build options to present to user
		 *
		 * @return array
		 */
		public function get_available_area_options() {
			$options = array();

			foreach ( $this->get_all_areas() as $value ) {
				$method_name           = $value['method'];
				$options[$method_name] = $value['name'];
			}

			return $options;
		}

		/**
		 * The chosen widget areas to activate/run. If none are selected in Display settings, act as if all are checked/enabled.
		 *
		 * @return array
		 */
		protected function get_enabled_areas_simple() {
			$settings = $this->get_settings();

			$result = (array) $settings->get_option(
				'enabled_areas',
				$this->get_all_areas_simple()
			);

			return $result;
		}

		/**
		 * The chosen widget areas to activate/run. If none are selected in Display settings, act as if all are checked/enabled.
		 *
		 * @return array
		 */
		protected function get_enabled_areas_full_details() {
			$all_available_assoc = $this->get_all_areas_assoc();

			$enabled_areas = $this->get_enabled_areas_simple();

			$result = array();

			foreach ( $enabled_areas as $value ) {
				if ( array_key_exists( $value, $all_available_assoc ) ) {
					$result[$value] = $all_available_assoc[$value];
				}
			}

			return $result;
		}

		/**
		 * Before Calendar widget area
		 */
		public function before_template() {
			dynamic_sidebar( 'tec_ext_widget_areas__before_template' );
		}

		/**
		 * After Calendar widget area
		 */
		public function after_template() {
			dynamic_sidebar( 'tec_ext_widget_areas__after_template' );
		}

		/**
		 * Before Event Single widget area
		 */
		public function single_before_view() {
			if ( is_singular( Tribe__Events__Main::POSTTYPE ) ) {
				dynamic_sidebar( 'tec_ext_widget_areas__single_before_view' );
			}
		}

		/**
		 * Above Event Single Description widget area
		 */
		public function single_event_before_the_content() {
			dynamic_sidebar( 'tec_ext_widget_areas__single_event_before_the_content' );
		}

		/**
		 * Below Event Single Description widget area
		 */
		public function single_event_after_the_content() {
			dynamic_sidebar( 'tec_ext_widget_areas__single_event_after_the_content' );
		}

		/**
		 * Above Event Single Details widget area
		 */
		public function single_event_before_the_meta() {
			dynamic_sidebar( 'tec_ext_widget_areas__single_event_before_the_meta' );
		}

		/**
		 * Below Event Single Details BEFORE OTHERS widget area
		 */
		public function single_event_after_the_meta_early() {
			dynamic_sidebar( 'tec_ext_widget_areas__single_event_after_the_meta_early' );
		}

		/**
		 * Below Event Single Details AFTER OTHERS widget area
		 */
		public function single_event_after_the_meta_late() {
			dynamic_sidebar( 'tec_ext_widget_areas__single_event_after_the_meta_late' );
		}

		/**
		 * After Event Single widget area
		 */
		public function single_after_view() {
			if ( is_singular( Tribe__Events__Main::POSTTYPE ) ) {
				dynamic_sidebar( 'tec_ext_widget_areas__single_after_view' );
			}
		}

	} // end class
} // end if class_exists check