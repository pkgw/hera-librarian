#! /usr/bin/env php
<?php

// CLI script to create Librarian and MC records.
// Usage: db_init.php command
//
// commands:
// hl_source name
//      create a Librarian source with the given name
// mc_source name
//      create a MC source with the given name
// store name capacity
//      create a store with the given name and capacity (in bytes)
// test_setup
//      create records for testing

require_once("test_setup.inc");
require_once("hera_util.inc");
require_once("hl_db.inc");

function usage() {
    echo "usage: db_init.php command
    commands:
        hl_source name: create Librarian source
        mc_source name: create M&C source
        store name capacity: create store
        test_setup: create records for testing
";
    exit;
}

function create_source($name, $subsystem) {
    $source = new StdClass;
    $source->name = $name;
    $source->authenticator = random_string();
    $source->create_time = time();
    echo "creating $subsystem source $name; authenticator: $source->authenticator\n";
    return source_insert($source);
}

function create_store($store) {
    $store->create_time = time();
    return store_insert($store);
}

function hl_setup() {
    global $test_stores, $test_source_names, $test_config;
    foreach ($test_source_names as $u) {
        if (!create_source($u, 'Librarian')) {
            echo db_error()."\n";
        }
    }
    foreach ($test_stores as $s) {
        if (!create_store($s)) {
            echo db_error()."\n";
        }
    }
    config_insert($test_config);
}

if ($argc < 2) {
    usage();
}

init_db(LIBRARIAN_DB_NAME);

switch ($argv[1]) {
case 'source':
    create_source($argv[2]);
    break;
case 'store':
    create_store($argv[2], $argv[3], $argv[4]);
    break;
case 'test_setup':
    hl_setup();
    break;
default: usage();
}

?>
