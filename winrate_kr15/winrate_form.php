<?php
require "../Database.php";
$db = Database::getInstance();

//$_GET = ["league_main" => 14268, "leagues" => [14268, 14569]];

$league_matches = $db->selectMatches($_GET["league_main"]);
$hero_stats = $db->selectKillRace($_GET["leagues"]);

$win = 0;
$lose = 0;
$win2 = 0;
$lose2 = 0;
foreach ($league_matches as $league_match) {
    $players = $db->selectPlayers($league_match["id"]);

    $radiant_avg_kills = 0;
    $dire_avg_kills = 0;
    $radiant_avg_deaths = 0;
    $dire_avg_deaths = 0;
    foreach ($players as $player) {
        if ($player["player_slot"] <= 4) {
            $radiant_avg_kills += $hero_stats[$player["hero_id"]]["average_kills_kr15"];
            $radiant_avg_deaths += $hero_stats[$player["hero_id"]]["average_deaths_kr15"];
        } else {
            $dire_avg_kills += $hero_stats[$player["hero_id"]]["average_kills_kr15"];
            $dire_avg_deaths += $hero_stats[$player["hero_id"]]["average_deaths_kr15"];
        }
    }


    if ($league_match["radiant_kr15"] == ($radiant_avg_kills > $dire_avg_kills)) {
        $win++;
    } else {
        $lose++;
    }

    $radiant_kr15 = ($radiant_avg_kills + $dire_avg_deaths)/2;
    $dire_kr15 = ($dire_avg_kills + $radiant_avg_deaths)/2;
    if ($league_match["radiant_kr15"] == ($radiant_kr15 > $dire_kr15)) {
        $win2++;
    } else {
        $lose2++;
    }
}

echo "win: " . $win . "<br>";
echo "lose: " . $lose . "<br>";
echo "win2: " . $win2 . "<br>";
echo "lose2: " . $lose2 . "<br>";