<?php
require "../Database.php";
$leagues = Database::getInstance()->selectLeagues();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Title</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <meta charset="UTF-8">
    <title>Winrate</title>
</head>
<body>
<div class="container">
    <form action="total_frags_winrate.php" method="get">
        <div class="mb-3">
            <div class="h5">Винрейт лиги</div>
            <div class="league-select">
                <select class="form-select" size="<?= count($leagues) ?>" required multiple
                        name="league_main">
                    <?php foreach ($leagues as $league) { ?>
                        <option value="<?= $league["id"] ?>"><?= $league["name"] ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <div class="h5">На основе каких лиг</div>
            <div class="league-select">
                <input class="form-check-input mb-2" type="checkbox" id="selectall_checkbox" onclick="eventCheckBox()"
                       checked>
                <label class="form-check-label" for="selectall">
                    Select all leagues
                </label>
                <select class="form-select" id="selectall_leagues" size="<?= count($leagues) ?>" required multiple
                        name="leagues[]">
                    <?php foreach ($leagues as $league) { ?>
                        <option selected value="<?= $league["id"] ?>"><?= $league["name"] ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <button type="submit">Отправить</button>
    </form>
</div>
<script>
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