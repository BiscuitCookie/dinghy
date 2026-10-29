#!/bin/sh
# Ожидаем готовности MySQL и RabbitMQ перед стартом воркера
until php artisan tinker --execute='try { DB::connection()->select("select 1"); } catch (Throwable $e) { exit(1); }' > /dev/null 2>&1; do
    echo "waiting for database..."
    sleep 2
done

until php artisan tinker --execute='try { Illuminate\Support\Facades\Queue::connection("rabbitmq")->size(); } catch (Throwable $e) { exit(1); }' > /dev/null 2>&1; do
    echo "waiting for rabbitmq..."
    sleep 2
done

# Объявляем очереди (idempotent) — иначе воркер спамит ошибками,
# пока публикатор не создал очередь, а после down -v декларации теряются
php -r '
require "vendor/autoload.php";
$conn = new PhpAmqpLib\Connection\AMQPStreamConnection(
    getenv("RABBITMQ_HOST") ?: "rabbitmq",
    5672,
    getenv("RABBITMQ_USER") ?: "retro",
    getenv("RABBITMQ_PASSWORD") ?: "retro"
);
$channel = $conn->channel();
foreach (["mail", "default"] as $queue) {
    $channel->queue_declare($queue, false, true, false, false);
}
$channel->close();
$conn->close();
echo "queues declared\n";
'

exec php artisan queue:work --queue=mail,default --sleep=3 --tries=3
