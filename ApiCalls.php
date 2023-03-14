<?php

/**
 * Класс для api-запросов
 */
class ApiCalls
{
    /**
     * @var int Счетчик вызова ф-ций с opendota api
     */
    static int $calls_count = 0;

    /**
     * Получить айди матчей
     * @param $league_id
     * @return array айди матчей
     */
    public static function getMatchesIds($league_id): array
    {
        self::sleep();
        $matches = self::curlRequestAndResponse("https://api.opendota.com/api/leagues/$league_id/matches");

        $matches_ids = array();
        foreach ($matches as $match) {
            $matches_ids[] = $match["match_id"];
        }

        return $matches_ids;
    }

    /**
     * Получить данные матча
     * @param $match_id
     * @return array
     */
    public static function getMatch($match_id): array
    {
        self::sleep();
        return self::curlRequestAndResponse("https://api.opendota.com/api/matches/$match_id");
    }

    /**
     * Получить данные героя
     * @return array
     */
    public static function getHeroes(): array
    {
        self::sleep();
        return self::curlRequestAndResponse("https://api.opendota.com/api/constants/heroes");
    }

    /**
     * Получить данные лиги
     * @param $league_id
     * @return array
     */
    public static function getLeague($league_id): array
    {
        self::sleep();
        return self::curlRequestAndResponse("https://api.opendota.com/api/leagues/$league_id");
    }

    /**
     * Выполняет curl-запрос и возвращает ответ в виде ассоциативного массива
     * @param $url
     * @return array
     */
    private static function curlRequestAndResponse($url): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return json_decode($output, true);
    }

    /**
     * Костыль для opendota, так как кд на api 60 запросов в минуту
     * @return void
     */
    private static function sleep(): void
    {
        self::$calls_count++;
        if (self::$calls_count % 60 == 0 && self::$calls_count != 0) sleep(60);
    }

    /**
     * Получить список текущих лайв игр
     * @return array
     */
    public static function getLiveMatches(): array
    {
        return self::curlRequestAndResponse("https://api.steampowered.com/IDOTA2Match_570/GetLiveLeagueGames/v1/?key=TOKEN&league_id=");
    }
}