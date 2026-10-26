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

exec php artisan queue:work --queue=mail,default --sleep=3 --tries=3
