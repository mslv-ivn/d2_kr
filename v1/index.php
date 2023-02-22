<?php
require "../Database.php";
$heroes = Database::getInstance()->selectHeroes();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <style>
        .hero-stat {
            height: 75px;
        }

        .team-average {
            height: 15px;
        }

        body {
            background-image: url("img/wallpaperbetter.com_1920x1080.jpg");
        }

        body {
            color: white;
        }

        .button_play-dota {
            background-image: url("img/play_dota.png");
            height: 50px;
            width: 330px
        }

        .league-select {
            width: fit-content;
        }

        select {
            overflow: auto;
        }
    </style>
</head>
<body>
<div class="container">
    <div>
        <form action="heroes_form.php" method="post" id="heroes-form">
            <div class="mb-3">
                <input type="text">
                <div class="h5">Radiant</div>
                <div class="radiant-stat row">
                    <?php for ($i = 0; $i < 5; $i++) { ?>
                        <div class="hero col">
                            <div class="hero-select">
                                <!--                            <label for="select-hero--><?php //echo $i ?><!--">-->
                                <?php //echo $i ?><!--th hero</label>-->
                                <select class="selectpicker" id="select-hero<?= $i ?>" name="select-hero<?= $i ?>"
                                        data-live-search="true">
                                    <option value="" disabled selected>Select hero</option>
                                    <?php
                                    foreach ($heroes as $hero) {
                                        echo "<option value='$hero[id]'>$hero[localized_name]</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="hero-stat" id="select-hero<?= $i ?>-insert">
                                <div class="hero-kills"></div>
                                <div class="hero-matches"></div>
                                <div class="hero-average"></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <div class="team-average h6" id="team1-average"></div>
            </div>

            <div class="mb-3">
                <input type="text">
                <div class="h5">Dire</div>
                <div class="dire-stat row">
                    <?php for ($i = 5; $i < 10; $i++) { ?>
                        <div class="hero col">
                            <div class="hero-select">
                                <!--                            <label for="select-hero--><?php //echo $i ?><!--">-->
                                <?php //echo $i ?><!--th hero</label>-->
                                <select class="selectpicker mb-2" id="select-hero<?= $i ?>" name="select-hero<?= $i ?>"
                                        data-live-search="true">
                                    <option value="" disabled selected>Select hero</option>
                                    <?php
                                    foreach ($heroes as $hero) {
                                        echo "<option value='$hero[id]'>$hero[localized_name]</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="hero-stat" id="select-hero<?= $i ?>-insert">
                                <div class="hero-kills"></div>
                                <div class="hero-matches"></div>
                                <div class="hero-average"></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                <div class="team-average h6" id="team2-average"></div>
            </div>

            <div class="mb-3">
                <div class="h5">Select league</div>
                <div class="league-select">
                    <input class="form-check-input mb-2" type="checkbox" id="selectall_checkbox"
                           onclick="eventCheckBox()" checked>
                    <label class="form-check-label" for="selectall">
                        Select all leagues
                    </label>
                    <?php $leagues = Database::getInstance()->selectLeagues() ?>
                    <select class="form-select" id="selectall_leagues" size="<?= count($leagues) ?>" required multiple
                            name="leagues[]">
                        <?php foreach ($leagues as $league) { ?>
                            <option selected value="<?= $league["id"] ?>"><?= $league["name"] ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <button class="button_play-dota" type="submit">
        </form>
    </div>
    <div class="mt-3">
        <a href="../league_parse/index.html" class="btn btn-danger">Parse league</a>
    </div>
</div>
<script>
    const form = document.getElementById('heroes-form');

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        fetch(form.action, {
            method: form.method,
            body: new FormData(form),
        })
            .then(response => response.json())
            .then(data => stat_print(data))
    });

    function stat_print(heroes) {
        let rad_avg = 0, dire_avg = 0;
        for (const hero of heroes) {
            if (hero["field"].slice(-1) < 5) {
                rad_avg += parseFloat(hero["average_kills_kr10"]);
            } else {
                dire_avg += parseFloat(hero["average_kills_kr10"]);
            }
            const ins = document.getElementById(hero["field"] + "-insert");

            ins.querySelector('.hero-kills').innerHTML = "<b>kills: </b>" + hero["kills_kr10"];
            ins.querySelector('.hero-matches').innerHTML = "<b>matches: </b>" + hero["matches_kr10"];
            ins.querySelector('.hero-average').innerHTML = "<b>average: </b>" + hero["average_kills_kr10"];

            // ins.innerHTML = "kills: " + hero["kills"] + " matches: " + hero["matches"] + " average: " + hero["average"];

        }
        document.getElementById("team1-average").innerHTML = ("radiant average: " + rad_avg);
        document.getElementById("team2-average").innerHTML = ("dire average: " + dire_avg);
    }

    function eventCheckBox() {
        let checkbox = document.getElementById("selectall_checkbox");
        let options = document.getElementById("selectall_leagues");
        for (let child of options.children) { //zero-based array
            child.selected = checkbox.checked;
        }
    }
</script>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js"
        integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js"
        integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy"
        crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
</body>
</html>