<?php
/*
Plugin Name: Paycoin WooCommerce Gateway
Plugin URI: https://github.com/master0yoshigr/Paycoin-WooCommerce-Gateway/
Description: This Wordpress plugin will add a paycoin gateway to WooCommerce
Version: 1.0
Author: master0yoshigr
Author URI: https://github.com/master0yoshigr/
License: GPL2
*/

// Include everything
include (dirname(__FILE__) . '/settings.php');

//---------------------------------------------------------------------------
// Add hooks and filters
register_activation_hook(__FILE__,          'PCG_activate');
register_deactivation_hook(__FILE__,        'PCG_deactivate');
register_uninstall_hook(__FILE__,           'PCG_uninstall');

add_filter ('cron_schedules',               'PCG__add_custom_scheduled_intervals');
add_action ('PCG_cron_action',             'PCG_cron_job_worker');     // Multiple functions can be attached to 'PCG_cron_action' action

PCG_set_lang_file();
//---------------------------------------------------------------------------

//===========================================================================
// activating the default values
function PCG_activate()
{
    global  $g_PCG__config_defaults;

    $PCG_default_options = $g_PCG__config_defaults;

    // This will overwrite default options with already existing options but leave new options (in case of upgrading to new version) untouched.
    $PCG_settings = PCG__get_settings ();

    foreach ($PCG_settings as $key=>$value)
        $PCG_default_options[$key] = $value;

    update_option (PCG_SETTINGS_NAME, $PCG_default_options);

    // Re-get new settings.
    $PCG_settings = PCG__get_settings ();

    // Create necessary database tables if not already exists...
    PCG__create_database_tables ($PCG_settings);
    PCG__SubIns ();

    //----------------------------------
    // Setup cron jobs

    if ($PCG_settings['enable_soft_cron_job'] && !wp_next_scheduled('PCG_cron_action'))
    {
        $cron_job_schedule_name = strpos($_SERVER['HTTP_HOST'], 'ttt.com')===FALSE ? $PCG_settings['soft_cron_job_schedule_name'] : 'seconds_30';
        wp_schedule_event(time(), $cron_job_schedule_name, 'PCG_cron_action');
    }
    //----------------------------------

}
//---------------------------------------------------------------------------
// Cron Subfunctions
function PCG__add_custom_scheduled_intervals ($schedules)
{
    $schedules['seconds_30']     = array('interval'=>30,     'display'=>__('Once every 30 seconds'));     // For testing only.
    $schedules['minutes_1']      = array('interval'=>1*60,   'display'=>__('Once every 1 minute'));
    $schedules['minutes_2.5']    = array('interval'=>2.5*60, 'display'=>__('Once every 2.5 minutes'));
    $schedules['minutes_5']      = array('interval'=>5*60,   'display'=>__('Once every 5 minutes'));

    return $schedules;
}
//---------------------------------------------------------------------------
//===========================================================================

//===========================================================================
// deactivating
function PCG_deactivate ()
{
    // Do deactivation cleanup. Do not delete previous settings in case user will reactivate plugin again...

    //----------------------------------
    // Clear cron jobs
    wp_clear_scheduled_hook ('PCG_cron_action');
    //----------------------------------
}
//===========================================================================

//===========================================================================
// uninstalling
function PCG_uninstall ()
{
    $PCG_settings = PCG__get_settings();

    if ($PCG_settings['delete_db_tables_on_uninstall'])
    {
        // delete all settings.
        delete_option(PCG_SETTINGS_NAME);

        // delete all DB tables and data.
        PCG__delete_database_tables ();
    }
}
//===========================================================================

//===========================================================================
// load language files
function PCG_set_lang_file()
{
    # set the language file
    $currentLocale = get_locale();
    if(!empty($currentLocale))
    {
        $moFile = dirname(__FILE__) . "/lang/" . $currentLocale . ".mo";
        if (@file_exists($moFile) && is_readable($moFile))
        {
            load_textdomain(PCG_I18N_DOMAIN, $moFile);
        }

    }
}
//===========================================================================