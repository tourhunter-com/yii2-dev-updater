<?php
/** @var \tourhunter\devUpdater\DevUpdaterComponent $devUpdater */

use yii\helpers\Url;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Yii2 Develop Updater</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 100;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .header {
            font-size: 200%;
            margin-bottom: 30px;
        }

        .btn-group {
            margin-top: 20px;
        }

        .btn-group .btn {
            color: black;
            text-decoration: none;
            padding: 10px;
            margin: 10px 5px;
            background-color: #00c851;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn.btn-discard {
            background-color: #ff5722;
        }

        .btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .warning-text {
            color: #886600;
        }

        .error-text {
            color: #dc3500;
        }

        .message {
            font-size: 120%;
        }
    </style>
    <script>
        var runUpdate = function () {
            var request = new XMLHttpRequest();
            request.open('GET', '<?= Url::to([$devUpdater->controllerId . '/run']) ?>', true);
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            request.send();
            var runLinkEl = document.getElementById('btn-run');
            runLinkEl.classList.add('disabled');
            setTimeout(function () {
                document.location.reload();
            }, 1000);
            return false;
        };
    </script>
</head>
<body>
<div class="flex-center position-ref full-height">
    <div class="header">
        DevUpdater
    </div>
    <?php if ($devUpdater->hasWarnings()) { ?>
        Warnings:
        <div class="warnings">
            <?php foreach ($devUpdater->getWarnings() as $warning) { ?>
                <p class="warning-text">&#9888; <?= $warning ?></p>
            <?php } ?>
        </div>
    <?php } ?>

    <?php $errors = $devUpdater->getInfoStorage()->getLastErrorsInfo(); ?>
    <?php if (count($errors)) { ?>
        Errors:
        <div class="errors">
            <?php foreach ($errors as $error) { ?>
                <p class="error-text">&#9762; <?= $error ?></p>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if ($devUpdater->isRunningUpdate()) { ?>
        <div class="message">
            The updating process is running now!
        </div>
        <script>
            setTimeout(function () {
                document.location.reload();
            }, 3000);
        </script>
    <?php } else { ?>

    <?php if ($devUpdater->getUpdateNecessity()) { ?>
        <div class="message">
            The project needs updating! (<?= implode(', ', $devUpdater->getNonUpdatedServiceTitles()) ?>)
        </div>
        <div class="btn-group">
            <a class="btn btn-run" id="btn-run" onclick="return runUpdate()" href="">Run</a>
            <a class="btn btn-discard" href="<?= Url::to([$devUpdater->controllerId . '/discard']) ?>">Discard</a>
        </div>
    <?php } else { ?>
        <div class="message">
            The project doesn't need updating!
        </div>
        <div class="btn-group">
            <a class="btn btn-run" href="<?= Url::to(['/']) ?>">Return to site</a>
        </div>
    <?php } ?>
    <?php } ?>
</div>
</body>
</html>
