<?php
/**
 * What this program does is accept multiple connections and forward
 * each incoming message to the rest of the connections.
 */

namespace PhpCache;

use ThenLabs\SocketServer\Event\ConnectionEvent;
use ThenLabs\SocketServer\Event\DataEvent;
use ThenLabs\SocketServer\Event\DisconnectionEvent;
use ThenLabs\SocketServer\SocketServer;

class HubServer extends SocketServer
{
    /** @var \ThenLabs\SocketServer\Connection[] */
    protected  array $connections = [];

    public function onConnection(ConnectionEvent $event): void
    {
        foreach ($this->connections as $connection) {
            $connection->writeLine("New connection.");
        }

        $this->connections[] = $event->getConnection();
    }

    public function onData(DataEvent $event): void
    {
        $data = $event->getData();

        switch ($data) {
            case 'exit':
                $event->getConnection()->close();
                break;

            case 'stop':
                $event->getServer()->stop();
                break;

            default:
                foreach ($this->connections as $connection) {
                    if ($connection !== $event->getConnection()) {
                        $connection->writeLine($data);
                    }
                }
                break;
        }
    }

    public function onDisconnection(DisconnectionEvent $event): void
    {
        foreach ($this->connections as $id => $connection) {
            if ($connection === $event->getConnection()) {
                unset($this->connections[$id]);
                break;
            }
        }
    }
}