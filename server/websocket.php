<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;

class WoolifyWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $subscriptions;
    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->subscriptions = [];
        $this->connectToDatabase();
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['action'])) {
            return;
        }

        switch ($data['action']) {
            case 'subscribe':
                if (isset($data['batchId'])) {
                    $this->subscriptions[$from->resourceId] = $data['batchId'];
                    $this->sendBatchUpdate($from, $data['batchId']);
                }
                break;
            case 'unsubscribe':
                unset($this->subscriptions[$from->resourceId]);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->subscriptions[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function connectToDatabase() {
        try {
            $this->db = new PDO(
                "mysql:host=localhost;dbname=woolify_db",
                "root",
                "",
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    protected function sendBatchUpdate($conn, $batchId) {
        try {
            $stmt = $this->db->prepare("
                SELECT wb.*, pr.status as processing_status, tr.status as transport_status
                FROM wool_batches wb
                LEFT JOIN processing_records pr ON wb.id = pr.batch_id
                LEFT JOIN transportation_records tr ON wb.id = tr.batch_id
                WHERE wb.id = ?
            ");
            $stmt->execute([$batchId]);
            $batchData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($batchData) {
                $conn->send(json_encode([
                    'type' => 'batch_update',
                    'data' => $batchData
                ]));
            }
        } catch (PDOException $e) {
            echo "Database error: " . $e->getMessage() . "\n";
        }
    }

    public function broadcastBatchUpdate($batchId) {
        foreach ($this->subscriptions as $clientId => $subscribedBatchId) {
            if ($subscribedBatchId == $batchId) {
                foreach ($this->clients as $client) {
                    if ($client->resourceId == $clientId) {
                        $this->sendBatchUpdate($client, $batchId);
                        break;
                    }
                }
            }
        }
    }
}

$loop = Factory::create();
$socket = new SocketServer('0.0.0.0:8080', $loop);

$server = new IoServer(
    new HttpServer(
        new WsServer(
            new WoolifyWebSocket()
        )
    ),
    $socket,
    $loop
);

echo "WebSocket server started on port 8080\n";
$server->run(); 