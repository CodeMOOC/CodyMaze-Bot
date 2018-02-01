<?php
/* General data */
define('BOARD_SIDE_SIZE', 5);
define('BOARD_SIZE', BOARD_SIDE_SIZE * BOARD_SIDE_SIZE);
define("NUMBER_OF_GAMES", 13);

/* user_status table array position constants */
define('USER_STATUS_TELEGRAM_ID', 0);
define('USER_STATUS_COMPLETED', 1);
define('USER_STATUS_COMPLETED_ON', 2);
define('USER_STATUS_NAME', 3);
define('USER_STATUS_CERTIFICATE_ID', 4);
define('USER_STATUS_CERTIFICATE_SENT', 5);

/* cardinal position info codes */
define('CARD_ANSWERING_QUIZ', 't');
define('CARD_NOT_ANSWERING_QUIZ', 'f');
define('CARD_ENDGAME_POSITION', 'e');

function cardinal_direction_is_valid($dir) {
    return in_array($dir, array("n", "s", "e", "w"));
}

function cardinal_direction_to_name($dir) {
    switch($dir) {
        case 'n':
            return __("North");
        case 'e':
            return __('East');
        case 's':
            return __('South');
        case 'w':
            return __('West');
        default:
            Logger::warning("Unknown cardinal direction {$dir}", __FILE__);
            return __("North");
    }
}

function cardinal_direction_to_description($dir) {
    switch($dir) {
        case 'n':
            return __("northwards");
        case 'e':
            return __('eastwards');
        case 's':
            return __('southwards');
        case 'w':
            return __('westwards');
        default:
            Logger::warning("Unknown cardinal direction {$dir}", __FILE__);
            return __("northwards");
    }
}
