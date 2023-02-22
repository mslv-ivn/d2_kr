<?php
require "../Database.php";
// TODO Duplicated code fragment
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

//$leagues_ids = $_GET["league-id"];
$leagues_ids = [14857, 14858, 14859, 14860, 14886, 14887, 14892, 14893, 14921, 14922, 14927, 14928 ];

$added_leagues = array();
foreach ($leagues_ids as $league_id) {
    league_parse($league_id);
    $added_leagues[] = $league_id;
}

echo "Всё прошло успешно! Добавленные лиги: " . implode(", ", $added_leagues);