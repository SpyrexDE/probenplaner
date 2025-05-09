<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Dashboard</title>
    <link rel="stylesheet" href="assets/debug.css">
</head>
<body>
    <h1>
        <span class="title">Debug Dashboard</span>
        <?php 
        $env = $_ENV['APP_ENV'] ?? 'unknown';
        ?>
        <small class="env-indicator <?= $envClass ?>">
            ENV: <?= htmlspecialchars($env) ?>
        </small>
    </h1>
    
    <?php if (!empty($message)): ?>
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?> 