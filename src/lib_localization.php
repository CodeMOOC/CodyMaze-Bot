<?php
/**
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Support library for localization.
 */

require_once(dirname(__FILE__) . '/lib_log.php');

// This array maps ISO language codes to locales installed on the local system,
// which match the locales printed by the `locale -a` command.
// Language codes are matched exactly for regional codes, and then approximately
// using the first two characters.
const LANGUAGE_LOCALE_MAP = array(
    'en-US' => 'en_US.utf8',
    'en' => 'en_US.utf8',
    'it' => 'it_IT.utf8',
    'sr' => 'sr_RS.utf8@latin',
    'hu' => 'hu_HU.utf8'
);

// This array maps ISO language codes to user-readable representations of the
// language, localized to the target language.
const LANGUAGE_NAME_MAP = array(
    'en-US' => 'English 🇺🇸',
    'hu' => 'Magyar 🇭🇺',
    'it' => 'Italiano 🇮🇹',
    'sr' => 'Srpski 🇷🇸'
);

function localization_get_locale_for_iso($iso_code) {
    if(array_key_exists($iso_code, LANGUAGE_LOCALE_MAP)) {
        // Exact match
        return LANGUAGE_LOCALE_MAP[$iso_code];
    }
    else if(strlen($iso_code) > 2 && array_key_exists(substr($iso_code, 0, 2), LANGUAGE_LOCALE_MAP)) {
        // Match with base 2-character ISO code
        return LANGUAGE_LOCALE_MAP[substr($iso_code, 0, 2)];
    }
    else {
        // No match found :(
        return LANGUAGE_LOCALE_MAP['en'];
    }
}

/**
 * Set current locale by language ISO code.
 *
 * @param $locale_iso_code ISO code of the target locale, will be matched
 *                         as well as possible against LANGUAGE_LOCALE_MAP.
 * @return String of the final locale set, taken from LANGUAGE_LOCALE_MAP values.
 */
function localization_set_locale($locale_iso_code) {
    $locale = localization_get_locale_for_iso($locale_iso_code);

    putenv('LC_ALL=' . $locale);
    if(setlocale(LC_ALL, $locale) === false) {
        Logger::error("Failed to set locale to {$locale}", __FILE__);
    }

    return $locale;
}

function localization_safe_gettext($msgid, $domain) {
    textdomain($domain);

    $value = gettext($msgid);
    if(!$value || $value === $msgid) {
        // No value in translation, default to EN and try again
        $previous_locale = setlocale(LC_ALL, "0");

        setlocale(LC_ALL, LANGUAGE_LOCALE_MAP['en']);
        $value = gettext($msgid);

        setlocale(LC_ALL, $previous_locale);
    }

    return $value;
}

function localization_load_user($telegram_id, $chat_language_code) {
    $conv_language_code = db_scalar_query("SELECT `language_code` FROM `user_status` WHERE `telegram_id` = {$telegram_id}");
    if($conv_language_code) {
        Logger::debug("Conversation set to locale {$conv_language_code}", __FILE__, $telegram_id);
        localization_set_locale($conv_language_code);
        return;
    }

    if($chat_language_code) {
        Logger::debug("Chat set to locale {$chat_language_code}", __FILE__, $telegram_id);
        localization_set_locale($chat_language_code);
        return;
    }

    Logger::debug("No locale info, leaving default", __FILE__, $telegram_id);
}

function localization_switch_and_persist_locale($telegram_id, $language_code) {
    $real_language_code = localization_set_locale($language_code);

    Logger::debug("Switching and persisting to {$real_language_code}", __FILE__);

    $upsert_sql = sprintf(
        'INSERT INTO `user_status` (`telegram_id`, `language_code`) VALUES(%1$d, \'%2$s\') ON DUPLICATE KEY UPDATE `language_code` = \'%2$s\'',
        $telegram_id,
        db_escape($real_language_code)
    );
    $upsert_result = db_perform_action($upsert_sql);
    if($upsert_result === false) {
        Logger::error("Failed to upsert localization setting ({$upsert_sql})", __FILE__, $telegram_id);
    }
    else {
        Logger::debug("Localization setting updated: {$real_language_code}", __FILE__, $telegram_id);
    }
}

/**
 * Get translated string.
 */
function __($msgid, $domain = 'text') {
    return localization_safe_gettext($msgid, $domain);
}

/**
 * Echoes translated string.
 */
function _e($msgid, $domain = 'text') {
    echo localization_safe_gettext($msgid, $domain);
}

// Load text domains
$target_dir = (dirname(__FILE__) . '/translation');

bindtextdomain('text', $target_dir);
bind_textdomain_codeset('text', 'UTF-8');

// Set default locale
localization_set_locale('it');
