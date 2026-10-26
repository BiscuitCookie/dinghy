<?php
$host = getenv('DB_HOST') ?: 'db';
$name = getenv('DB_NAME') ?: 'appdb';
$user = getenv('DB_USER') ?: 'appuser';
$pass = getenv('DB_PASSWORD') ?: 'secret';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name", $user, $pass);
    $status = 'подключение к MySQL успешно';
} catch (PDOException $e) {
    $status = 'ошибка подключения: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>LAMP</title>
</head>
<body>
    <h1>LAMP работает</h1>
    <p>PHP: <?= PHP_VERSION ?></p>
    <p>MySQL: <?= htmlspecialchars($status) ?></p>
</body>
</html>
