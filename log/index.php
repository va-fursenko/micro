<?php 
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'config.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'class.Log.php');

    if (isset($_GET['action']) && ($_GET['action'] == 'clear')){
        fclose(fopen('error.log', 'w'));
        fclose(fopen('db.error.log', 'w'));
        fclose(fopen('db.log', 'w'));
        header('Location: http://' . $_SERVER['SERVER_NAME'] . '/log/');
    }
?>
<!DOCTYPE html>
<html>

    <head>
        <title><?= CONFIG::PAGE_TITLE ?></title>
        <meta charset="<?= CONFIG::PAGE_CHARSET ?>">
        <script src="<?= CONFIG::HOST ?>js/jquery.js"></script>
        <script src="<?= CONFIG::HOST ?>js/bootstrap.js"></script>
        <link rel="stylesheet" href="<?= CONFIG::HOST ?>css/bootstrap.css">
        <link rel="stylesheet" href="<?= CONFIG::HOST ?>css/log.css">
    </head>

    <body>
        <form id="logForm" name="logForm" method="post" action="index.php?action=clear">
            <div class="btn-container">
                <input type=submit value='Очистить все логи' class="btn btn-danger">
            </div>
        </form>

        <div id="logDiv">
            <div class="tab-header">
                <ul class="nav nav-pills" role="tablist">
                    <li>
                        <a href="#e0" aria-controls="e1" role="tab" data-toggle="tab">Ошибки PHP</a>
                    </li>
                    <li class="active">
                        <a href="#e1" aria-controls="e1" role="tab" data-toggle="tab">Ошибки БД</a>
                    </li>
                    <li>
                        <a href="#e2" aria-controls="e2" role="tab" data-toggle="tab">Запросы БД</a>
                    </li>
                </ul>
            </div>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane fade" id="e0">
                    <?= Log::showLogFile('error.log') ?>
                </div>
                <div role="tabpanel" class="tab-pane fade active in" id="e1">
                    <?= Log::showLogFile('db.error.log') ?>
                </div>
                <div role="tabpanel" class="tab-pane fade" id="e2">
                    <?= Log::showLogFile('db.log') ?>
                </div>
            </div>
        </div>

    </body>
</html>


