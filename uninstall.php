<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Didban
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'didban_version' );