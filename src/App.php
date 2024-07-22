<?php

namespace AMQF\IoTServer;

use AMQF\IoTServer\Config\IoTWSConfig;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;

class App
{
    public function __construct(private IoTWSConfig $_config)
    {
    }

    public function run()
    {
        $loop = LoopFactory::create();

        $wsServer = new WebSocketServer();

        $webSock = new \React\Socket\Server('0.0.0.0:8081', $loop);
        $webServer = new IoServer(
            new HttpServer(
                new WsServer(
                    $wsServer
                )
            ),
            $webSock,
            $loop
        );

        echo "WebSocket Server is running on ws://localhost:8081\n";

        $mqttClient = new MQTTClient($this->_config->getMQTTClientSettings());

        $mqttClient->connect();

        $mqttClient->subscribe(function ($topic, $message) use ($wsServer) {
            echo "Received message from MQTT: {$message}\n";
            $wsServer->broadcast(json_encode(['topic' => $topic, 'message' => $message]));
        });

        $mqttClient->run();

        // $loop->run();
    }
}