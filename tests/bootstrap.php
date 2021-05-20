<?php


    declare(strict_types = 1);


    $root_dir = getenv('ROOT_DIR');

    if ( ! $root_dir || ! is_dir($root_dir) ) {

        $root_dir = dirname(__FILE__, 2);

    }

    if ( $root_dir ) {

        define('ROOT_DIR', rtrim($root_dir , DIRECTORY_SEPARATOR));
        define('TESTS_DIR', ROOT_DIR . DIRECTORY_SEPARATOR . 'tests');

    }

    require_once ROOT_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';