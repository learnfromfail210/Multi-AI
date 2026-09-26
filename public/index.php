<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

$appName = $config['app']['name'];
$version = $config['app']['version'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($appName) ?></title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #0f1115;
            color: #ffffff;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 60px 20px;
            text-align: center;
        }

        h1 {
            font-size: 42px;
            margin-bottom: 10px;
        }

        p {
            color: #aeb4c0;
            font-size: 18px;
        }

        .version {
            margin-top: 30px;
            font-size: 14px;
            color: #737b89;
        }
    </style>
</head>

<body>

    <main class="container">
        <h1><?= htmlspecialchars($appName) ?></h1>

        <p>
            Your unified multi-AI workspace.
        </p>

        <div class="version">
            Version <?= htmlspecialchars($version) ?>
        </div>
    </main>

</body>
</html>
