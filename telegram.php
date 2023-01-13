<?php
function tg_sendMessage($message)
{
    $bot_token = "5771279953:AAGirD4apiUSv8QP6t8bF2MzNzdl39pNKSg";
    $tg_channel = "@dota2_killing_race";

    $message = urlencode($message);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot$bot_token/sendMessage?chat_id=$tg_channel&text=$message&parse_mode=HTML&disable_web_page_preview=1");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
//    $output = json_decode($output, true);
//    var_dump($output);
}
