<?php
require "Database.php";

$heroes_stat = Database::getInstance()->selectHeroLeagueStats($_POST["leagues"]);

array_pop($_POST);
$result = array();
foreach ($_POST as $field => $hero) {
    $heroes_stat[$hero]["field"] = $field;
    $result[] = $heroes_stat[$hero];
}

echo json_encode($result);