<?php
/*
Plugin Name:        FRC - WP Base
Plugin URI:         https://www.frantic.fi/
Description:        A collection of modifications and default settings to apply for theme, admin, login & plugins.
Version:            0.0.1
Author:             Frantic
Author URI:         https://www.frantic.fi/
*/

namespace Frc\WP\Base;

require_once __DIR__ . '/lib/helpers.php';

const FRC_FEATURE_PREFIX = 'frc-base-';

function fetch_modules($folder) {
    return glob(__DIR__ . '/modules/'. $folder .'/*.php');
}

function load_modules() {

    foreach ( fetch_modules('plugin') as $file ) {
        maybe_require_feature($file, 'plugin');
    }

    if ( is_admin() ) {
        foreach ( fetch_modules('admin') as $file ) {
            maybe_require_feature($file, 'admin');
        }
        return;
    }

    foreach ( fetch_modules('login') as $file ) {
        maybe_require_feature($file, 'login');
    }

    foreach ( fetch_modules('theme') as $file ) {
        maybe_require_feature($file, 'theme');
    }

}

function get_plugin_from_feature($feature) {
    $prefix = FRC_FEATURE_PREFIX;
    return str_replace("{$prefix}plugin-", '', $feature);
}

function maybe_require_feature($file, $side = 'theme') {

    $prefix = FRC_FEATURE_PREFIX;

    // Set feature name, for example: frc-theme-disable-api
    $feature = $prefix . $side . '-' . basename($file, '.php');

    // Remove disabled (prefixed with "!")
    if ( current_theme_supports('!' . $feature) ) {
        remove_theme_support($feature);
        remove_theme_support('!' . $feature);
        return;
    }

    // Add default supports
    if ( enabled_by_default($feature) )
        add_theme_support($feature);

    // If all side's modules wanted, add support for individual modules
    if ( current_theme_supports("{$prefix}{$side}-all") )
        add_theme_support($feature);

    // Disable if module is not supported
    if ( !current_theme_supports($feature) )
        return;

    // Activate admin modules only in admin side
    if ( $side === 'admin' && !is_admin() )
        return;

    // Disable unactive plugin modules
    if ( $side === 'plugin' && !frc_is_plugin_active(get_plugin_from_feature($feature)) )
        return;

    if ( !file_exists($file) )
        return;

    require_once $file;

}

function enabled_by_default($feature) {
    $feature = str_replace(FRC_FEATURE_PREFIX, '', $feature);
    return in_array($feature, [
        'plugin-defaults',
        'admin-defaults',
        'login-defaults',
        'theme-defaults',
    ]);
}

add_action('after_setup_theme', __NAMESPACE__ . '\\load_modules', 100);
