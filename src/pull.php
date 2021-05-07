<?php
/*
 * Telegram Bot Sample
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Basic message processing in pull mode for your bot.
 * Start editing here. =)
 */

require_once(dirname(__FILE__) . '/lib.php');

$last_update = getenv('TELEGRAM_LAST_UPDATE');

// Fetch updates from API
// Note: we remember the last fetched ID and query for the next one, if available.
//       The third parameter enabled long-polling. Switch to any number of seconds
//       to enable (the request will hang until timeout or until a message is received).
$content = telegram_get_updates(intval($last_update) + 1, 1, 60);
if($content === false) {
    Logger::fatal('Failed to fetch updates from API', __FILE__);
}
if(count($content) == 0) {
    Logger::debug('No new messages', __FILE__);
    exit;
}

$update = $content[0];

Logger::debug('New update received: ' . print_r($update, true), __FILE__);

// Updates have the following structure:
// [
//     {
//         "update_id": 123456789,
//         "message": {
//              ** message object **
//         }
//     }
// ]

$update_id = $update['update_id'];

include 'msg_processing_simple.php';
?>
