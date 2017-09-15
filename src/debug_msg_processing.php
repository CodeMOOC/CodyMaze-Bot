<?php

function debug_message_processing($chat_id, $text){
    if(strpos($text, "get_certificate")){
        telegram_send_message($chat_id, "Sending certificate...");
        send_pdf($chat_id, "Brendan D. Paolini");
    } else {
        telegram_send_message($chat_id, "Error! received {$text}");
    }
}