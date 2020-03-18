<?php

namespace EPFL\Menus\CLI;

if (! defined( 'ABSPATH' )) {
    die( 'Access denied.' );
}

use \WP_CLI;
use \WP_CLI_Command;

require_once(__DIR__ . '/lib/i18n.php');
use function EPFL\I18N\___;

require_once(__DIR__ . '/epfl-menus.php');
use \EPFL\Menus\ExternalMenuItem;

function log_success ($details) {
    if ($details) {
        WP_CLI::log(sprintf('✓ %s', $details));
    } else {
        WP_CLI::log('✓');
    }
}

function log_failure ($details) {
    if ($details) {
        WP_CLI::log(sprintf('\u001b[31m✗ %s\u001b[0m', $details));
    } else {
        WP_CLI::log(sprintf('\u001b[31m✗\u001b[0m'));
    }
}

class EPFLMenusCLICommand extends WP_CLI_Command
{
    public static function hook () {
        WP_CLI::add_command('epfl menus refresh', [get_called_class(), 'refresh' ]);
        WP_CLI::add_command('epfl menus add-external-menu-item', [get_called_class(), 'add_external_menu_item' ]);
    }

    public function refresh () {
        WP_CLI::log(___('Enumerating menus on filesystem...'));
        $local = ExternalMenuItem::load_from_filesystem();
        WP_CLI::log(sprintf(___('... Success, found %d local menus'),
                            count($local)));

        WP_CLI::log(___('Enumerating menus in config file...'));
        $local = ExternalMenuItem::load_from_config_file();
        WP_CLI::log(sprintf(___('... Success, found %d site-configured menus'),
                            count($local)));

        $all = ExternalMenuItem::all();
        WP_CLI::log(sprintf(___('Refreshing %d instances...'),
                            count($all)));
        foreach ($all as $emi) {
            try {
                $emi->refresh();
                log_success($emi);
            } catch (\Throwable $t) {
                log_failure($emi);
            }
        }
    }

    /**
     * @example wp epfl menus add-external-menu-item --menu-location-slug=top urn:epfl:labs "laboratoires"
     */
    public function add_external_menu_item ($args, $assoc_args) {
        list($urn, $title) = $args;

        $menu_location_slug = $assoc_args['menu-location-slug'];
        if (!empty($menu_location_slug)) $menu_location_slug = "top";

        # todo: check that params is format urn:epfl
        WP_CLI::log(___('Add a new external menu item...'));

        $external_menu_item = ExternalMenuItem::get_or_create($urn);
        $external_menu_item->set_title($title);
        $external_menu_item->meta()->set_remote_slug($menu_location_slug);
        $external_menu_item->meta()->set_items_json('[]');

        WP_CLI::log(sprintf(___('External menu item ID %d...'),$external_menu_item->ID));
    }
}

EPFLMenusCLICommand::hook();
