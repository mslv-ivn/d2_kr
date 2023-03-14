<?php
require "ApiCalls.php";

/**
 * Класс для работы с бд
 */
class Database
{
    /**
     * @var int порог совершенных убийств одной из команд для события "гонка убийств".
     */
    private int $kill_threshold = 10;
    private int $kill_threshold2 = 15;

    /**
     * @var PDO Объект подключения к бд
     */
    private PDO $dbh;

    /**
     * @var Database Объект синглтон паттерн
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
     * Синглтон паттерн
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
     * Получить объект для получения к бд
     * TODO Метод является временным костылем, пока не будет реализован метод query()
     * @return PDO объект подключения к бд
     */
    public function getPDO(): PDO
    {
        return $this->dbh;
    }

    /**
     * TODO Сделать метод для кастомных запросов к бд.
     * @param $query
     * @return void
     */
    public function query($query): void
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
        $stmt = $this->dbh->prepare("select api_str from match_saved_api where match_id = :match_id");
        $stmt->execute(["match_id" => $match_id]);
        $api_str = $stmt->fetchColumn();
        if ($api_str) {
            $match = json_decode($api_str, true);
        } else {
            $match = ApiCalls::getMatch($match_id);
            // Сохранить api-ответ в виде строки
            $api_str = json_encode($match);
            $stmt = $this->dbh->prepare("INSERT INTO match_saved_api (match_id, api_str) VALUES (:match_id, :api_str)");
            $stmt->execute(['match_id' => $match["match_id"], 'api_str' => $api_str]);
        }

        //Костыль - в килл логе герои отображаются как "npc_dota_hero_rubick"
        $stmt = $this->dbh->prepare("select name, id from hero");
        $stmt->execute();
        $heroes = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

        $heroes_kills = array();
        $kills_log = array();
        foreach ($match["players"] as $player) {
            $heroes_kills[$player["hero_id"]] = ["player_slot" => $player["player_slot"], "kills" => $player["kills"], "deaths" => $player["deaths"], "assists" => $player["assists"], "kills_kr10" => 0, "deaths_kr10" => 0, "kills_kr15" => 0, "deaths_kr15" => 0];
            foreach ($player["kills_log"] as $kill) {
                $kills_log[] = ["player_slot" => $player["player_slot"], "hero_id" => $player["hero_id"], "time" => $kill["time"], "dead_hero_id" => $heroes[$kill["key"]]["id"]];
            }
        }

        /**
         * Сортировка килл лога по возрастанию.
         * Костыль в виде сортировки, потому что килл лог для каждого игрока отдельный.
         * Приходиться собирать килл лог со всех игроков и упорядочивать по возрастанию,
         * чтобы понять, какая из команд первая достигла $kill_threshold.
         */
        usort($kills_log, function ($a, $b) {
            if ($a["time"] == $b["time"]) {
                return 0;
            }
            return ($a["time"] < $b["time"]) ? -1 : 1;
        });

        $radiant_kills_kr10 = 0;
        $dire_kills_kr10 = 0;
        $radiant_kills_kr15 = 0;
        $dire_kills_kr15 = 0;
        foreach ($kills_log as $kill) {
            if ($radiant_kills_kr10 < $this->kill_threshold && $dire_kills_kr10 < $this->kill_threshold) {
                $heroes_kills[$kill["hero_id"]]["kills_kr10"]++;
                $heroes_kills[$kill["dead_hero_id"]]["deaths_kr10"]++;
                if ($kill["player_slot"] <= 4) {
                    $radiant_kills_kr10++;
                } else {
                    $dire_kills_kr10++;
                }
            }

            $heroes_kills[$kill["hero_id"]]["kills_kr15"]++;
            $heroes_kills[$kill["dead_hero_id"]]["deaths_kr15"]++;
            if ($kill["player_slot"] <= 4) {
                $radiant_kills_kr15++;
            } else {
                $dire_kills_kr15++;
            }

            if ($radiant_kills_kr15 == $this->kill_threshold2 || $dire_kills_kr15 == $this->kill_threshold2) break;
        }
        // Совершили ли Radiant событие "Гонка убийств 10"
        $radiant_kr10 = $radiant_kills_kr10 > $dire_kills_kr10 ? 1 : 0;
        // Совершили ли Radiant событие "Гонка убийств 15"
        $radiant_kr15 = $radiant_kills_kr15 > $dire_kills_kr15 ? 1 : 0;

        // Insert match
        $stmt = $this->dbh->prepare("INSERT INTO `match` (id, league_id, radiant_score, dire_score, radiant_win, radiant_kr10, radiant_kr15, duration, first_blood_time, radiant_team_name, dire_team_name, radiant_team_id, dire_team_id) VALUES (:id, :league_id, :radiant_score, :dire_score, :radiant_win, :radiant_kr10, :radiant_kr15, :duration, :first_blood_time, :radiant_team_name, :dire_team_name, :radiant_team_id, :dire_team_id)");
        $stmt->execute(['id' => $match["match_id"], 'league_id' => $match["leagueid"], 'radiant_score' => $match["radiant_score"], 'dire_score' => $match["dire_score"], 'radiant_win' => (int)$match["radiant_win"], 'radiant_kr10' => $radiant_kr10, 'radiant_kr15' => $radiant_kr15, 'duration' => $match["duration"], 'first_blood_time' => $match["first_blood_time"], 'radiant_team_name' => $match["radiant_team"]["name"], 'dire_team_name' => $match["dire_team"]["name"], 'radiant_team_id' => $match["radiant_team_id"], 'dire_team_id' => $match["dire_team_id"]]);

        // Insert player
        $stmt = $this->dbh->prepare("INSERT INTO player (match_id, hero_id, player_slot, kills, deaths, assists, kills_kr10, deaths_kr10, kills_kr15, deaths_kr15) VALUES (:match_id, :hero_id, :player_slot, :kills, :deaths, :assists, :kills_kr10, :deaths_kr10, :kills_kr15, :deaths_kr15)");
        foreach ($heroes_kills as $hero_id => $hero_data) {
            $stmt->execute(['match_id' => $match["match_id"], 'hero_id' => $hero_id, 'player_slot' => $hero_data["player_slot"], "kills" => $hero_data["kills"], "deaths" => $hero_data["deaths"], "assists" => $hero_data["assists"], 'kills_kr10' => $hero_data["kills_kr10"], 'deaths_kr10' => $hero_data['deaths_kr10'], 'kills_kr15' => $hero_data["kills_kr15"], 'deaths_kr15' => $hero_data["deaths_kr15"]]);
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
     * При удалении лиги каскадно удаляются поля в таблицах match, player, kill_race
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
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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
     * Выборка статистики героев для лиги/лиг (событие "гонка убийств")
     * @param array $leagues_ids
     * @return array статистика героев по лиге
     */
    public function selectKillRace(array $leagues_ids): array
    {
        /** Костыль для where in */
        $in = str_repeat('?,', count($leagues_ids) - 1) . '?';
        $stmt = $this->dbh->prepare("select hero_id, sum(kills_kr10) as kills_kr10, sum(matches_kr10) as matches_kr10, avg(average_kills_kr10) as average_kills_kr10, avg(average_deaths_kr10) as average_deaths_kr10, sum(kills_kr15) as kills_kr15, sum(matches_kr15) as matches_kr15, avg(average_kills_kr15) as average_kills_kr15, avg(average_deaths_kr15) as average_deaths_kr15 from kill_race where league_id in ($in) group by hero_id");
        $stmt->execute($leagues_ids);
        return $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
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