<?php
require "ApiCalls.php";

/**
 * Класс для работы с бд
 */
class Database
{
    /**
     * @var int порог совершенных убийств одной из команд для события "гонка убийств".
     * Может быть 5, 10, 15... до 30 вроде в некоторых букмекерках.
     */
    private int $kill_threshold = 10;

    /**
     * @var PDO Объект подключения к бд
     */
    private PDO $dbh;

    /**
     * @var Database Синглтон паттерн тест
     */
    private static Database $instance;

    /**
     * Создаем объект подключения к бд
     * Ставим вывод ассоциативным массивом
     */
    private function __construct()
    {
        $this->dbh = new PDO('mysql:host=localhost;dbname=dota2_db', 'root', '');
        $this->dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    /**
     * Синглтон паттерн тест
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * TODO Сделать метод для кастомных запросов к бд.
     * @param $query
     * @return void
     */
    public function query($query)
    {
    }

    /**
     * Вставка лиги в бд
     * @param $league_id
     * @return void
     */
    public function insertLeague($league_id): void
    {
        $league = ApiCalls::getLeague($league_id);

        $stmt = $this->dbh->prepare("INSERT INTO league (id, name, tier) VALUES (:id, :name, :tier)");
        $stmt->execute(['id' => $league["leagueid"], 'name' => $league["name"], 'tier' => $league["tier"]]);
    }

    /**
     * Вставка премиум лиги из лайва
     * TODO Костыльный метод из-за того, что в insertLeague используется api-запрос
     * @param $id
     * @param $name
     * @param $tier
     * @return void
     */
    public function insertLeagueFromLive($id, $name, $tier): void
    {
        $stmt = $this->dbh->prepare("INSERT INTO league (id, name, tier) VALUES (:id, :name, :tier)");
        $stmt->execute(['id' => $id, 'name' => $name, 'tier' => $tier]);
    }

    /**
     * Вставка матча в бд
     * @param $match_id
     * @return void
     */
    public function insertMatch($match_id): void
    {
        $match = ApiCalls::getMatch($match_id);

        $heroes_kills = array();
        $kills_log = array();
        foreach ($match["players"] as $player) {
            $heroes_kills[$player["hero_id"]] = ["player_slot" => $player["player_slot"], "kills" => 0];
            foreach ($player["kills_log"] as $kill) {
                $kills_log[] = ["player_slot" => $player["player_slot"], "hero_id" => $player["hero_id"], "time" => $kill["time"]];
            }
        }

        /**
         * Сортировка килл лога по возрастанию
         * Костыль в виде сортировки потому, что килл логи для каждого игрока отдельно.
         * Приходиться собирать килл лог со всех игроков и упорядочивать по возрастанию,
         * чтобы понять какая из команд первая достигла $kill_threshold
         */
        usort($kills_log, function ($a, $b) {
            if ($a["time"] == $b["time"]) {
                return 0;
            }
            return ($a["time"] < $b["time"]) ? -1 : 1;
        });

        $dire_kills = 0;
        $radiant_kills = 0;
        foreach ($kills_log as $kill) {
            $heroes_kills[$kill["hero_id"]]["kills"]++;

            if ($kill["player_slot"] <= 4) {
                $radiant_kills++;
            } else {
                $dire_kills++;
            }

            if ($dire_kills == $this->kill_threshold || $radiant_kills == $this->kill_threshold) break;
        }

        $radiant_did_10_kills = $radiant_kills > $dire_kills ? 1 : 0;

        /**
         * Insert match
         */
        $stmt = $this->dbh->prepare("INSERT INTO `match` (id, league_id, radiant_did_10_kills) VALUES (:id, :league_id, :radiant_did_10_kills)");
        $stmt->execute(['id' => $match["match_id"], 'league_id' => $match["leagueid"], 'radiant_did_10_kills' => $radiant_did_10_kills]);

        /**
         * Insert player
         */
        $stmt = $this->dbh->prepare("INSERT INTO player (match_id, hero_id, player_slot, kills) VALUES (:match_id, :hero_id, :player_slot, :kills)");
        foreach ($heroes_kills as $hero_id => $hero_data) {
            $stmt->execute(['match_id' => $match["match_id"], 'hero_id' => $hero_id, 'player_slot' => $hero_data["player_slot"], 'kills' => $hero_data["kills"]]);
        }
    }

    /**
     * Вставка героев в бд
     * Данные героев константны. Вставка требуется в случае, если таблица hero пуста.
     * @return void
     */
    public function insertHeroes(): void
    {
        $heroes = ApiCalls::getHeroes();

        $stmt = $this->dbh->prepare("INSERT INTO hero (id, localized_name, name) VALUES (:id, :localized_name, :name)");
        foreach ($heroes as $hero) {
            $stmt->execute(['id' => $hero["id"], 'localized_name' => $hero["localized_name"], 'name' => $hero["name"]]);
        }
    }

    /**
     * Выборка героев
     * @return bool|array массив героев и бд
     */
    public function selectHeroes(): bool|array
    {
        $stmt = $this->dbh->query("select * from hero");
        return $stmt->fetchAll();
    }

    /**
     * Удалить лигу
     * При удалении лиги каскадно удаляются поля в таблицах match, player, hero_league_stats
     * @param $league_id
     * @return void
     */
    public function deleteLeague($league_id): void
    {
        $stmt = $this->dbh->prepare("delete from league where id = :id");
        $stmt->execute(['id' => $league_id]);
    }

    /**
     * Выборка лиги
     * @param $league_id
     * @return mixed данные о лиге
     */
    public function selectLeague($league_id): mixed
    {
        $stmt = $this->dbh->prepare("select * from league where id = :id");
        $stmt->execute(['id' => $league_id]);
        return $stmt->fetch();
    }

    /**
     * Выборка id матчей, принадлежащей лиге $league_id
     * @param $league_id
     * @return bool|array id мачтей
     */
    public function selectMatchesIds($league_id): bool|array
    {
        $stmt = $this->dbh->prepare("select id from `match` where league_id = :league_id");
        $stmt->execute(['league_id' => $league_id]);

        $matches_ids = array();
        while ($match = $stmt->fetch()) {
            $matches_ids[] = $match["id"];
        }

        return $matches_ids;
    }

    /**
     * Выборка всех лиг
     * @return bool|array данные о всех лигах
     */
    public function selectLeagues(): bool|array
    {
        $stmt = $this->dbh->prepare("select * from league");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Выборка статистики героев для лиги/лиг
     * @param array $leagues_ids
     * @return array статистика героев по лиге
     */
    public function selectHeroLeagueStats(array $leagues_ids): array
    {
        /** Костыль для where in */
        $in = str_repeat('?,', count($leagues_ids) - 1) . '?';
        $stmt = $this->dbh->prepare("select hero_id, sum(total_kills) as kills, sum(matches_played) as matches, sum(total_kills)/sum(matches_played) as average from hero_league_stats where league_id in ($in) group by hero_id");
        $stmt->execute($leagues_ids);
        /**
         * Приведение массива к виду { "hero_id" => [hero_data] }
         * TODO можно сделать красиво, если использовать параметры в fetchAll()
         */
        $heroes_stat = $stmt->fetchAll();
        $hero_stats_formatted = array();
        foreach ($heroes_stat as $hero_stat) {
            $hero_stats_formatted[$hero_stat["hero_id"]]["kills"] = $hero_stat["kills"];
            $hero_stats_formatted[$hero_stat["hero_id"]]["matches"] = $hero_stat["matches"];
            $hero_stats_formatted[$hero_stat["hero_id"]]["average"] = $hero_stat["average"];
        }
        return $hero_stats_formatted;
    }


    /**
     * Выборка всех матчей лиги
     * @param $league_id
     * @return bool|array данные о матчах
     */
    public function selectMatches($league_id): bool|array
    {
        $stmt = $this->dbh->prepare("select * from `match` where league_id = :league_id");
        $stmt->execute(['league_id' => $league_id]);
        return $stmt->fetchAll();
    }

    /**
     * Выборка игроков
     * @param $match_id
     * @return bool|array данные игроков
     */
    public function selectPlayers($match_id): bool|array
    {
        $stmt = $this->dbh->prepare("select * from player where match_id = :match_id");
        $stmt->execute(['match_id' => $match_id]);
        return $stmt->fetchAll();
    }

    /**
     * Вставка лайв матча, который был анонсирован в тг
     * @param $live_match_id
     * @return void
     */
    public function insertLiveMatch($live_match_id): void
    {
        $stmt = $this->dbh->prepare("INSERT INTO live_match (id) VALUES (:id)");
        $stmt->execute(['id' => $live_match_id]);
    }

    /**
     * Выборка лайв матчей, анонсированных в тг
     * @return array
     */
    public function selectLiveMatches(): array
    {
        $stmt = $this->dbh->prepare("select * from live_match");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Вставка лиги лайв матча
     * Сделано для сокращения кол-ва запросов к api
     * @param $live_league_id
     * @return array данные о лиге с api-запроса
     */
    public function insertLiveLeague($live_league_id): array
    {
        $league = ApiCalls::getLeague($live_league_id);

        $stmt = $this->dbh->prepare("INSERT INTO live_league (id, name, tier) VALUES (:id, :name, :tier)");
        $stmt->execute(['id' => $league["leagueid"], 'name' => $league["name"], 'tier' => $league["tier"]]);

        return $league;
    }

    /**
     * Выборка лиг лайв матчей
     * Сделано для сокращения кол-ва запросов к api
     * @param $live_league_id
     * @return mixed лиги лайв матчей
     */
    public function selectLiveLeague($live_league_id): mixed
    {
        $stmt = $this->dbh->prepare("select * from live_league where id = :id");
        $stmt->execute(['id' => $live_league_id]);
        return $stmt->fetch();
    }
}