<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqPublisher implements EventPublisher
{
    public function publish(string $routingKey, array $pesan): void
    {
        $cfg = config('services.rabbitmq');

        $koneksi = new AMQPStreamConnection($cfg['host'], $cfg['port'], $cfg['user'], $cfg['password']);
        $kanal = $koneksi->channel();

        // exchange topic, durable
        $kanal->exchange_declare($cfg['exchange'], 'topic', false, true, false);

        // antrean penerima dibuat dan di-bind supaya pesan tidak hilang
        // meskipun layanan penerima belum menyala
        foreach ($cfg['antrean'] as $antrean) {
            $kanal->queue_declare($antrean, false, true, false, false);
            $kanal->queue_bind($antrean, $cfg['exchange'], 'pembayaran.dikonfirmasi');
        }

        $kanal->basic_publish(
            new AMQPMessage(json_encode($pesan, JSON_UNESCAPED_UNICODE), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]),
            $cfg['exchange'],
            $routingKey
        );

        $kanal->close();
        $koneksi->close();
    }
}