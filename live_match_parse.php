<?php
require 'telegram.php';
require "Database.php";
require "total_frags.php";

$db = Database::getInstance();

// Берем стату героев из бд, передавая id лиг
$heroes_stat = $db->selectKillRace(array_column($db->selectLeagues(), "id"));

// Цикл для crontab
for ($i = 0; $i < 5; $i++) {
// Получаем список текущих лайв матчей
    $live_matches = ApiCalls::getLiveMatches();
    foreach ($live_matches["result"]["games"] as $live_match) {
        // Проверяем пренадлежит ли матч к премиум лиге
        $league = $db->selectLiveLeague($live_match["league_id"]);
        if (!$league) {
            $league = $db->insertLiveLeague($live_match["league_id"]);
            if ($league["tier"] == "premium") {
                $db->insertLeagueFromLive($league["leagueid"], $league["name"], $league["tier"]);
            }
        }
        if (!($league["tier"] == "premium")) {
            continue;
        }

        // Если матч был анонсирован в тг, то пропускаем итерацию цикла
        if (in_array($live_match["match_id"], $db->selectLiveMatches())) {
            continue;
        }
        // Записываем анонсированный матч в бд
        $db->insertLiveMatch($live_match["match_id"]);

        // Если герои еще не были пикнуты игроками, то пропускаем итерацию цикла
        // Иначе подсчитываем суммарную силу героев для обеих команд
        $rad_avg = 0;
        $dire_avg = 0;
        $loop_continue = 0;
        $heroes_radiant = array();
        $heroes_dire = array();
        foreach ($live_match["players"] as $player) {
            if ($player["team"] == 0) {
                if ($player["hero_id"] == 0) {
                    $loop_continue = 1;
                    break;
                }
                $rad_avg += $heroes_stat[$player["hero_id"]]["kr10_average"];
                $heroes_radiant[] = $player["hero_id"];
            }
            if ($player["team"] == 1) {
                if ($player["hero_id"] == 0) {
                    $loop_continue = 1;
                    break;
                }
                $dire_avg += $heroes_stat[$player["hero_id"]]["kr10_average"];
                $heroes_dire[] = $player["hero_id"];
            }
        }
        if ($loop_continue == 1) continue;

        $team1_info = total_frags_info($live_match["radiant_team"]["team_id"]);
        $team2_info = total_frags_info($live_match["radiant_team"]["team_id"]);

        // Анонс в тг
        $message =
//        $league["name"] . " tier: " . $league["tier"] . "\n" .
//            $live_match["radiant_team"]["team_name"] . " avg: " . number_format($rad_avg, 2) . "\n" .
//            $live_match["dire_team"]["team_name"] . " avg: " . number_format($dire_avg, 2) . "\n" .
//            "Ставь на " . "<b>" . ($rad_avg > $dire_avg ? $live_match["radiant_team"]["team_name"] : $live_match["dire_team"]["team_name"]) . "</b>" . "\n" .
            "avg убийств командой " . $live_match["radiant_team"]["team_name"] . ": " . $team1_info["kills"] . "\n" .
            "avg убийств на команде " . $live_match["radiant_team"]["team_name"] . ": " . $team1_info["deaths"] . "\n" .
            "avg фрагов у команды " . $live_match["radiant_team"]["team_name"] . ": " . $team1_info["avg"] . "\n" .
            "avg убийств командой " . $live_match["dire_team"]["team_name"] . ": " . $team1_info["kills"] . "\n" .
            "avg убийств на команде " . $live_match["dire_team"]["team_name"] . ": " . $team1_info["deaths"] . "\n" .
            "avg фрагов у команды " . $live_match["dire_team"]["team_name"] . ": " . $team1_info["avg"] . "\n" .
            "<b>" ."Тотал фрагов: ". "</b>" . total_frags($heroes_radiant, $heroes_dire) . "\n" .
            "https://betboom.ru/esport";
        tg_sendMessage($message);
    }
    // Пауза для crontab, исключая последнюю итерацию
    if ($i == 4) {
        continue;
    }
    sleep(10);
}