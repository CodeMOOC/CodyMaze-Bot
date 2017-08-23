<?php
/*
 * Telegram Bot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Configuration file.
 */

define('TELEGRAM_BOT_TOKEN', '323540485:AAE0GuCMXDMwj4uDNNHNrXoEfX5OpKaZvRU');
define('TELEGRAM_API_URI_BASE', 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/');
define('TELEGRAM_FILE_API_URI_BASE', 'https://api.telegram.org/file/bot' . TELEGRAM_BOT_TOKEN . '/');

define('DATABASE_HOST', 'localhost');
define('DATABASE_NAME', 'codymaze');
define('DATABASE_USERNAME', 'codymazebot');
define('DATABASE_PASSWORD', 'c0DYmAz€bOT2017');

// PHP configuration
date_default_timezone_set('UTC'); // ensure UTC is used for all date functions
set_time_limit(0); // ensure scripts are not interrupted (e.g., long-polling or downloads)
