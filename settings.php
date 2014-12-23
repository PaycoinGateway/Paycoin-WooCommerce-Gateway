<?php

//---------------------------------------------------------------------------
// Global definitions
if (!defined('PCG_PLUGIN_NAME'))
{
    define('PCG_VERSION',           '1.0');

    //-----------------------------------------------
    define('PCG_SETTINGS_NAME',     'PCG-Settings');
    define('PCG_PLUGIN_NAME',       'Paycoin Gateway for WooCommerce');

    // i18n plugin domain for language files
    define('PCG_I18N_DOMAIN',       'PCG');

    if (extension_loaded('gmp') && !defined('USE_EXT'))
        define ('USE_EXT', 'GMP');
    else if (extension_loaded('bcmath') && !defined('USE_EXT'))
        define ('USE_EXT', 'BCMATH');
}
//---------------------------------------------------------------------------

//------------------------------------------
// Load wordpress for POSTback, WebHook and API pages that are called by external services directly.
if (defined('PCG_MUST_LOAD_WP') && !defined('WP_USE_THEMES') && !defined('ABSPATH'))
{
    $g_blog_dir = preg_replace ('|(/+[^/]+){4}$|', '', str_replace ('\\', '/', __FILE__)); // For love of the art of regex-ing
    define('WP_USE_THEMES', false);
    require_once ($g_blog_dir . '/wp-blog-header.php');

    // Force-elimination of header 404 for non-wordpress pages.
    header ("HTTP/1.1 200 OK");
    header ("Status: 200 OK");

    require_once ($g_blog_dir . '/wp-admin/includes/admin.php');
}
//------------------------------------------


// This loads the phpecc modules and selects best math library
require_once (dirname(__FILE__) . '/phpecc/classes/util/bcmath_Utils.php');
require_once (dirname(__FILE__) . '/phpecc/classes/util/gmp_Utils.php');
require_once (dirname(__FILE__) . '/phpecc/classes/interface/CurveFpInterface.php');
require_once (dirname(__FILE__) . '/phpecc/classes/CurveFp.php');
require_once (dirname(__FILE__) . '/phpecc/classes/interface/PointInterface.php');
require_once (dirname(__FILE__) . '/phpecc/classes/Point.php');
require_once (dirname(__FILE__) . '/phpecc/classes/NumberTheory.php');

