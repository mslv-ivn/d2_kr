<?php
require '../total_frags.php';

$db = Database::getInstance();

//$_GET = ["league_main" => 14859, "leagues" => [14858, 14859, 14886, 14892, 14893, 14898, 14913, 14927]];

$league_matches = $db->selectMatches($_GET["league_main"]);

$win = 0;
$lose = 0;
foreach ($league_matches as $league_match) {
    $players = $db->selectPlayers($league_match["id"]);

    $radiant_heroes = array();
    $dire_heroes = array();
    foreach ($players as $player) {
        if ($player["player_slot"] <= 4) {
            $radiant_heroes[] = $player["hero_id"];
        } else {
            $dire_heroes[] = $player["hero_id"];
        }
    }

    $total_kills = total_frags($radiant_heroes, $dire_heroes);

    $pdo = $db->getPDO();
    $stmt = $pdo->prepare("select radiant_score + dire_score as kills from `match` where id = :id");
    $stmt->execute(["id" => $league_match["id"]]);
    $match_sum_kills = $stmt->fetchAll();

    if ($total_kills > $match_sum_kills[0]['kills']) {
        $win++;
    } else {
        $lose++;
    }
}

echo "win: " . $win . "<br>";
echo "lose: " . $lose;