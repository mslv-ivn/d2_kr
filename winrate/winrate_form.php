<?php
require "../Database.php";
$db = Database::getInstance();

//$_GET = ["league_main" => 14268, "leagues" => [14268, 14569]];

$league_matches = $db->selectMatches($_GET["league_main"]);
$hero_stats = $db->selectKillRace($_GET["leagues"]);

$win = 0;
$lose = 0;
foreach ($league_matches as $league_match) {
    $players = $db->selectPlayers($league_match["id"]);

    $radiant_avg = 0;
    $dire_avg = 0;
    foreach ($players as $player) {
        if ($player["player_slot"] <= 4) {
            $radiant_avg += $hero_stats[$player["hero_id"]]["kr10_average"];
        } else {
            $dire_avg += $hero_stats[$player["hero_id"]]["kr10_average"];
        }
    }


    if ($league_match["radiant_kr10"] == ($radiant_avg > $dire_avg)) {
        $win++;
    } else {
        $lose++;
    }
}

echo "win: " . $win . "<br>";
echo "lose: " . $lose;