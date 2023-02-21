-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Фев 21 2023 г., 00:57
-- Версия сервера: 10.4.24-MariaDB
-- Версия PHP: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `dota2_db`
--

-- --------------------------------------------------------

--
-- Структура таблицы `hero`
--

CREATE TABLE `hero` (
  `id` int(11) NOT NULL COMMENT 'Id героя',
  `localized_name` varchar(255) NOT NULL COMMENT 'Имя героя (Anti-Mage)',
  `name` varchar(255) DEFAULT NULL COMMENT 'Имя героя как в доте (npc_dota_hero_antimage)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Данные о герое';

-- --------------------------------------------------------

--
-- Структура таблицы `kill_race`
--

CREATE TABLE `kill_race` (
  `league_id` int(11) NOT NULL COMMENT 'Id лиги',
  `hero_id` int(11) NOT NULL COMMENT 'Id героя',
  `kr10_kills` int(11) DEFAULT NULL COMMENT 'Общее количество убийств (Гонка убийств 10)',
  `kr10_matches` int(11) DEFAULT NULL COMMENT 'Количество сыгранных матчей (Гонка убийств 10)',
  `kr10_average` double DEFAULT NULL COMMENT 'Среднее количество убийств за матч (Гонка убийств 10)',
  `kr15_kills` int(11) DEFAULT NULL COMMENT 'Общее количество убийств (Гонка убийств 15)',
  `kr15_matches` int(11) DEFAULT NULL COMMENT 'Количество сыгранных матчей (Гонка убийств 15)',
  `kr15_average` double DEFAULT NULL COMMENT 'Среднее количество убийств за матч (Гонка убийств 15)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Суммарная результативность героя за все матчи лиги для события "Гонка убийств"';

-- --------------------------------------------------------

--
-- Структура таблицы `league`
--

CREATE TABLE `league` (
  `id` int(11) NOT NULL COMMENT 'Id лиги',
  `name` varchar(255) DEFAULT NULL COMMENT 'Наименование лиги',
  `tier` varchar(255) DEFAULT NULL COMMENT 'Тир лиги (например premium)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Данные о лиге';

--
-- Триггеры `league`
--
DELIMITER $$
CREATE TRIGGER `generate_empty_kill_race` AFTER INSERT ON `league` FOR EACH ROW begin
    DECLARE done INT DEFAULT FALSE;
    DECLARE hero_id int;
    DECLARE hero_cursor CURSOR
        FOR
        select id from hero;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN hero_cursor;

    read_loop:
    LOOP
        FETCH hero_cursor INTO hero_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        INSERT INTO kill_race VALUES (NEW.id, hero_id, 0, 0, 0, 0, 0, 0);
    END LOOP;

    CLOSE hero_cursor;
end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `live_league`
--

CREATE TABLE `live_league` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `tier` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='таблица нужна для проверки тира лиги лайв матча';

-- --------------------------------------------------------

--
-- Структура таблицы `live_match`
--

CREATE TABLE `live_match` (
  `id` bigint(20) NOT NULL COMMENT 'id матча, который был отправлен в тг'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Таблица для анонса лайв матча в тг';

-- --------------------------------------------------------

--
-- Структура таблицы `match`
--

CREATE TABLE `match` (
  `id` bigint(20) NOT NULL COMMENT 'Идентификационный номер матча, присвоенный Valve',
  `league_id` int(11) NOT NULL COMMENT 'Идентификатор лиги',
  `radiant_score` int(11) DEFAULT NULL COMMENT 'Итоговый счет Radiant (количество убийств на Dire)',
  `dire_score` int(11) DEFAULT NULL COMMENT 'Итоговый счет Dire (количество убийств на Radiant)',
  `radiant_win` tinyint(1) DEFAULT NULL COMMENT 'Логическое значение, указывающее, выиграли ли Radiant матч',
  `radiant_kr10` tinyint(1) DEFAULT NULL COMMENT 'Логическое значение, указывающее, совершили ли Radiant событие "Гонка убийств 10"',
  `radiant_kr15` tinyint(1) DEFAULT NULL COMMENT 'Логическое значение, указывающее, совершили ли Radiant событие "Гонка убийств 15"',
  `duration` int(11) DEFAULT NULL COMMENT 'Длительность игры в секундах',
  `first_blood_time` int(11) DEFAULT NULL COMMENT 'Время в секундах, когда произошла первая кровь',
  `radiant_team_name` varchar(255) DEFAULT NULL COMMENT 'Имя команды, играющей за Radiant',
  `dire_team_name` varchar(255) DEFAULT NULL COMMENT 'Имя команды, играющей за Dire',
  `radiant_team_id` int(11) DEFAULT NULL COMMENT 'Id команды, играющей за Radiant',
  `dire_team_id` int(11) DEFAULT NULL COMMENT 'Id команды, играющей за Dire'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Данные о матче';

-- --------------------------------------------------------

--
-- Структура таблицы `match_saved_api`
--

CREATE TABLE `match_saved_api` (
  `match_id` bigint(20) NOT NULL COMMENT 'Id матча',
  `api_str` mediumtext DEFAULT NULL COMMENT 'Api-ответ в виде строки'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `player`
--

CREATE TABLE `player` (
  `match_id` bigint(20) NOT NULL COMMENT 'Идентификационный номер матча, присвоенный Valve',
  `hero_id` int(11) NOT NULL COMMENT 'Id героя',
  `player_slot` int(11) NOT NULL COMMENT 'Слот игрока в матче. 0-4 - Radiant, 128-132 - Dire',
  `kills` int(11) DEFAULT NULL COMMENT 'Количество совершенных убийств в матче',
  `deaths` int(11) DEFAULT NULL COMMENT 'Количество смертей в матче',
  `assists` int(11) DEFAULT NULL COMMENT 'Количество оказанной помощи в матче',
  `kr10_kills` int(11) DEFAULT NULL COMMENT 'Количество убиств для события "Гонка убийств 10"',
  `kr15_kills` int(11) DEFAULT NULL COMMENT 'Количество убиств для события "Гонка убийств 15"'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Данные о игроке';

--
-- Триггеры `player`
--
DELIMITER $$
CREATE TRIGGER `kill_race_10_update_on_player_delete` AFTER DELETE ON `player` FOR EACH ROW BEGIN
    DECLARE `@hero_id`, `@kr10_kills`, `@kr10_matches`, `@league_id` int;

    set `@hero_id` = OLD.hero_id;
    set `@league_id` = (select league_id from `match` where `match`.id = OLD.match_id);

    select sum(kr10_kills), count(hero_id)
    into `@kr10_kills`, `@kr10_matches`
    from player
    where match_id in (select id from `match` where league_id = `@league_id`) and hero_id = `@hero_id`;

    update kill_race
    set kr10_kills   = `@kr10_kills`,
        kr10_matches = `@kr10_matches`,
        kr10_average = `@kr10_kills` / `@kr10_matches`
    where kill_race.league_id = `@league_id`
      and kill_race.hero_id = `@hero_id`;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `kill_race_10_update_on_player_insert` AFTER INSERT ON `player` FOR EACH ROW BEGIN
    DECLARE `@hero_id`, `@kr10_kills`, `@kr10_matches`, `@league_id` int;

    set `@hero_id` = NEW.hero_id;
    set `@league_id` = (select league_id from `match` where `match`.id = NEW.match_id);

    select sum(kr10_kills), count(hero_id)
    into `@kr10_kills`, `@kr10_matches`
    from player
    where match_id in (select id from `match` where league_id = `@league_id`) and hero_id = `@hero_id`;

    update kill_race
    set kr10_kills   = `@kr10_kills`,
        kr10_matches = `@kr10_matches`,
        kr10_average = `@kr10_kills` / `@kr10_matches`
    where kill_race.league_id = `@league_id`
      and kill_race.hero_id = `@hero_id`;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `kill_race_10_update_on_player_update` AFTER UPDATE ON `player` FOR EACH ROW BEGIN
    DECLARE `@hero_id`, `@kr10_kills`, `@kr10_matches`, `@league_id` int;

    set `@hero_id` = NEW.hero_id;
    set `@league_id` = (select league_id from `match` where `match`.id = NEW.match_id);

    select sum(kr10_kills), count(hero_id)
    into `@kr10_kills`, `@kr10_matches`
    from player
    where match_id in (select id from `match` where league_id = `@league_id`) and hero_id = `@hero_id`;

    update kill_race
    set kr10_kills   = `@kr10_kills`,
        kr10_matches = `@kr10_matches`,
        kr10_average = `@kr10_kills` / `@kr10_matches`
    where kill_race.league_id = `@league_id`
      and kill_race.hero_id = `@hero_id`;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `kill_race_15_update_on_player_delete` AFTER DELETE ON `player` FOR EACH ROW BEGIN
    DECLARE `@hero_id`, `@kr15_kills`, `@kr15_matches`, `@league_id` int;

    set `@hero_id` = OLD.hero_id;
    set `@league_id` = (select league_id from `match` where `match`.id = OLD.match_id);

    select sum(kr15_kills), count(hero_id)
    into `@kr15_kills`, `@kr15_matches`
    from player
    where match_id in (select id from `match` where league_id = `@league_id`) and hero_id = `@hero_id`;

    update kill_race
    set kr15_kills   = `@kr15_kills`,
        kr15_matches = `@kr15_matches`,
        kr15_average = `@kr15_kills` / `@kr15_matches`
    where kill_race.league_id = `@league_id`
      and kill_race.hero_id = `@hero_id`;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `kill_race_15_update_on_player_insert` AFTER INSERT ON `player` FOR EACH ROW BEGIN
    DECLARE `@hero_id`, `@kr15_kills`, `@kr15_matches`, `@league_id` int;

    set `@hero_id` = NEW.hero_id;
    set `@league_id` = (select league_id from `match` where `match`.id = NEW.match_id);

    select sum(kr15_kills), count(hero_id)
    into `@kr15_kills`, `@kr15_matches`
    from player
    where match_id in (select id from `match` where league_id = `@league_id`) and hero_id = `@hero_id`;

    update kill_race
    set kr15_kills   = `@kr15_kills`,
        kr15_matches = `@kr15_matches`,
        kr15_average = `@kr15_kills` / `@kr15_matches`
    where kill_race.league_id = `@league_id`
      and kill_race.hero_id = `@hero_id`;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `kill_race_15_update_on_player_update` AFTER UPDATE ON `player` FOR EACH ROW BEGIN
    DECLARE `@hero_id`, `@kr15_kills`, `@kr15_matches`, `@league_id` int;

    set `@hero_id` = NEW.hero_id;
    set `@league_id` = (select league_id from `match` where `match`.id = NEW.match_id);

    select sum(kr15_kills), count(hero_id)
    into `@kr15_kills`, `@kr15_matches`
    from player
    where match_id in (select id from `match` where league_id = `@league_id`) and hero_id = `@hero_id`;

    update kill_race
    set kr15_kills   = `@kr15_kills`,
        kr15_matches = `@kr15_matches`,
        kr15_average = `@kr15_kills` / `@kr15_matches`
    where kill_race.league_id = `@league_id`
      and kill_race.hero_id = `@hero_id`;
END
$$
DELIMITER ;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `hero`
--
ALTER TABLE `hero`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `kill_race`
--
ALTER TABLE `kill_race`
  ADD KEY `hero_league_stats_ibfk_2` (`league_id`),
  ADD KEY `hero_league_stats_ibfk_1` (`hero_id`);

--
-- Индексы таблицы `league`
--
ALTER TABLE `league`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `live_league`
--
ALTER TABLE `live_league`
  ADD UNIQUE KEY `live_league_pk` (`id`);

--
-- Индексы таблицы `live_match`
--
ALTER TABLE `live_match`
  ADD UNIQUE KEY `live_match_pk` (`id`);

--
-- Индексы таблицы `match`
--
ALTER TABLE `match`
  ADD PRIMARY KEY (`id`),
  ADD KEY `match_ibfk_1` (`league_id`);

--
-- Индексы таблицы `match_saved_api`
--
ALTER TABLE `match_saved_api`
  ADD UNIQUE KEY `match_saved_api_pk` (`match_id`);

--
-- Индексы таблицы `player`
--
ALTER TABLE `player`
  ADD KEY `player_ibfk_2` (`hero_id`),
  ADD KEY `player_ibfk_3` (`match_id`);

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `kill_race`
--
ALTER TABLE `kill_race`
  ADD CONSTRAINT `kill_race_ibfk_1` FOREIGN KEY (`hero_id`) REFERENCES `hero` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `kill_race_ibfk_2` FOREIGN KEY (`league_id`) REFERENCES `league` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `match`
--
ALTER TABLE `match`
  ADD CONSTRAINT `match_ibfk_1` FOREIGN KEY (`league_id`) REFERENCES `league` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `player`
--
ALTER TABLE `player`
  ADD CONSTRAINT `player_ibfk_2` FOREIGN KEY (`hero_id`) REFERENCES `hero` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `player_ibfk_3` FOREIGN KEY (`match_id`) REFERENCES `match` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
