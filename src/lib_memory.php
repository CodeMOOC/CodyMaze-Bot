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
    $update_result = db_perform_action("UPDATE `user_status` SET `memory` = '" . db_escape($encoded) . "', `last_memory_update` = NOW() WHERE `telegram_id` = {$telegram_id}");
    if($update_result === false) {
        Logger::warning("Failed to update conversation memory", __FILE__, $telegram_id);
    }
    else if($update_result === 0) {
        // No user record, yet
        if(db_perform_action("INSERT INTO `user_status` (`telegram_id`, `last_memory_update`, `memory`) VALUES({$telegram_id}, NOW(), '" . db_escape($encoded) . "')") === false) {
            Logger::error("Failed to insert conversation memory", __FILE__, $telegram_id);
        }
    }
    else {
        // All set
        Logger::debug("Conversation memory updated: {$encoded}", __FILE__, $telegram_id);
    }
}
