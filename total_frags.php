<?php
require_once "Database.php";

function total_frags($heroes_dire, $heroes_radiant): float|int
{
    $pdo = Database::getInstance()->getPDO();
    $in = str_repeat('?,', count($heroes_dire) - 1) . '?';
    $stmt = $pdo->prepare("select avg(kills) as kills, avg(deaths) as deaths from player where hero_id in ($in) group by hero_id");
    $stmt->execute($heroes_dire);
    $heroes_dire = $stmt->fetchAll();


    $in = str_repeat('?,', count($heroes_radiant) - 1) . '?';
    $stmt = $pdo->prepare("select avg(kills) as kills, avg(deaths) as deaths from player where hero_id in ($in) group by hero_id");
    $stmt->execute($heroes_radiant);
    $heroes_radiant = $stmt->fetchAll();

    $t1 = ['kills' => 0, 'deaths' => 0];
    foreach ($heroes_dire as $hero_t1) {
        $t1['kills'] += $hero_t1['kills'];
        $t1['deaths'] += $hero_t1['deaths'];
    }

    $t2 = ['kills' => 0, 'deaths' => 0];
    foreach ($heroes_radiant as $hero_t2) {
        $t2['kills'] += $hero_t2['kills'];
        $t2['deaths'] += $hero_t2['deaths'];
    }

    $avg1 = ($t1['kills'] + $t2["deaths"]) / 2;
    $avg2 = ($t2['kills'] + $t1["deaths"]) / 2;

    return $avg1 + $avg2;
}

function total_frags_info($team_id): array
{
    $pdo = Database::getInstance()->getPDO();
    $stmt = $pdo->prepare("select radiant_score as kills, dire_score as deaths from `match` where radiant_team_id = :team_id");
    $stmt->execute(['team_id' => $team_id]);
    $team_rad = $stmt->fetchAll();

    $team_kills_sum = 0;
    $team_deaths_sum = 0;
    $team_kills_count = count($team_rad);

    foreach ($team_rad as $rad) {
        $team_kills_sum += $rad['kills'];
        $team_deaths_sum += $rad['deaths'];
    }

    $stmt = $pdo->prepare("select radiant_score as deaths, dire_score as kills from `match` where dire_team_id = :team_id");
    $stmt->execute(['team_id' => $team_id]);
    $team_dire = $stmt->fetchAll();

    $team_deaths_count = count($team_dire);

    foreach ($team_dire as $dire) {
        $team_kills_sum += $dire['kills'];
        $team_deaths_sum += $dire['deaths'];
    }
    $team_count = $team_kills_count + $team_deaths_count;

    $team_kills = $team_kills_sum / $team_count;
    $team_deaths = $team_deaths_sum / $team_count;
    $team_avg = $team_kills + $team_deaths;

    return ["kills" => $team_kills, "deaths" => $team_deaths, "avg" => $team_avg];
}