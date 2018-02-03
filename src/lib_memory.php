<?php
/**
 * CodeMOOC CodyMazeBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Support library for conversational memory.
 */

require_once(dirname(__FILE__) . '/config.php');
require_once(dirname(__FILE__) . '/lib_log.php');
require_once(dirname(__FILE__) . '/lib_database.php');

$memory = new stdClass();

function memory_load_for_user($telegram_id) {
    global $memory;

    $data = db_scalar_query("SELECT `memory` FROM `user_status` WHERE `telegram_id` = {$telegram_id}");
    if(!$data) {
        $memory = new stdClass();
    }
    else {
        $memory = json_decode($data, false);
    }

    Logger::debug("Loaded memory: " . print_r($memory, true), __FILE__, $telegram_id);

    return $memory;
}

function memory_persist($telegram_id) {
    global $memory;

    $encoded = json_encode($memory);
    $update_result = db_perform_action("INSERT INTO `user_status` (`telegram_id`, `last_memory_update`, `memory`) VALUES({$telegram_id}, NOW(), '" . db_escape($encoded) . "') ON DUPLICATE KEY UPDATE `last_memory_update` = NOW(), `memory` = '" . db_escape($encoded) . "'");

    if($update_result === false) {
        Logger::error("Failed to update conversation memory", __FILE__, $telegram_id);
    }
    else {
        Logger::debug("Conversation memory updated: {$encoded}", __FILE__, $telegram_id);
    }
}
