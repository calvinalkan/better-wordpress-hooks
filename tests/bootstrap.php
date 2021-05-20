<?php


    declare(strict_types = 1);


    $ds = DIRECTORY_SEPARATOR;


    $root_dir = getenv('ROOT_DIR');

    if ( ! $root_dir || ! is_dir($root_dir) ) {

        $root_dir = dirname(__FILE__, 2);

    }

    if ( $root_dir ) {


        define('ROOT_DIR', rtrim($root_dir , $ds));
        define('TESTS_DIR', ROOT_DIR . $ds . 'tests');
        define('VENDOR_DIR', ROOT_DIR . $ds . 'vendor');
        define('PLUGIN_PHP', ROOT_DIR . $ds . 'vendor' . $ds . 'calvinalkan' . $ds . 'wordpress-hook-api-clone'. $ds . 'plugin.php');

    }

    require_once ROOT_DIR . $ds . 'vendor' . $ds . 'autoload.php';