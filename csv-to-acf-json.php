<?php
/**
 * Plugin Name: CSV to ACF JSON
 * Plugin URI:  https://github.com/spencerblacker/csv-to-acf-json
 * Description: Upload a CSV file and generate an ACF Field Group JSON file ready for import into Advanced Custom Fields.
 * Version:     1.0.0
 * Author:      Spencer Blackler
 * Author URI:  https://spencerblacker.com
 * License:     GPL-2.0-or-later
 * Text Domain: csv-to-acf-json
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CTAJ_VERSION', '1.0.0' );
define( 'CTAJ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CTAJ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once CTAJ_PLUGIN_DIR . 'includes/class-admin-page.php';
require_once CTAJ_PLUGIN_DIR . 'includes/class-json-generator.php';

add_action( 'plugins_loaded', function () {
    if ( is_admin() ) {
        new CTAJ_Admin_Page();
    }
} );
