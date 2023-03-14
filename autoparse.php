<?php
require "Database.php";
ignore_user_abort(1); // Let the script run even if user leaves the page
set_time_limit(0);    // Let script run forever

function league_parse($league_id): void
{
    $db = Database::getInstance();

    if (!($db->selectLeague($league_id))) {
        $db->insertLeague($league_id);
    }

    /**
     * Ищем матчи, которых еще нет в бд
     */
    $matches_api = ApiCalls::getMatchesIds($league_id);
    $matches_db = $db->selectMatchesIds($league_id);
    $matches = array_diff($matches_api, $matches_db);

    foreach ($matches as $match) {
        $db->insertMatch($match);
    }
}

$leagues_ids = array_column(Database::getInstance()->selectLeagues(), "id");

$added_leagues = array();
foreach ($leagues_ids as $league_id) {
    league_parse($league_id);
    $added_leagues[] = $league_id;
}

echo "Всё прошло успешно! Добавленные лиги: " . implode(", ", $added_leagues);