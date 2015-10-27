#! /usr/bin/env php
<?php

// script to manage copy tasks
//
// copy_master.php args
// --init: clear in_progress flags (do at boot time)
// --daemon: run as a daemon
// --sleep_interval: if daemon, how long to sleep between scans
// --retry_interval: how long to wait until retry failed copy (sec)
// default: start tasks as needed, then exit

require_once("hl_db.inc");

$sleep_interval = 10;
$retry_interval = 0;

function init() {
    task_update_all("in_progress=0", "completed=0");
}

function start_task($task) {
    task_update($task->id, "in_progress=1");
    $pid = pcntl_fork();
    if (!$pid) {
        $cmd = "copier.php --task_id $task->id";
        system($cmd);
    }
}

function start_tasks() {
    global $retry_interval, $config;

    if ($config->max_transfers) {
        $tasks = task_enum("in_progress<>0");
        $limit = $config->max_transfers - count($tasks);
        if ($limit <= 0) return;
    } else {
        $limit = 9999;
    }

    $t = time() - $retry_interval;
    $tasks = task_enum("completed=0 and in_progress=0 and last_error_time<$t limit $limit");
    foreach ($tasks as $task) {
        echo "starting task $task->id\n";
        start_task($task);
    }
}

function daemon() {
    global $sleep_interval;
    while (1) {
        start_tasks();
        // reap any child processes
        //
        while (1) {
            $ret = pcntl_wait($status, WNOHANG);
            if ($ret > 0) continue;
            break;
        }
        sleep($sleep_interval);
    }
}

$daemon = false;

init_db(LIBRARIAN_DB_NAME);
$config = config_get();

for ($i=1; $i<$argc; $i++) {
    switch ($argv[$i]) {
    case "--init":
        init();
        exit;
    case "--daemon":
        $daemon = true;
        break;
    case "--sleep_interval":
        $sleep_interval = (int)($argv[++$argc]);
        break;
    default:
        echo "bad arg: ".$argv[$i]."\n";
    }
}

if ($daemon) {
    daemon();
} else {
    start_tasks();
}

?>
