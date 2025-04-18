<?php

namespace Woolify;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use PDO;
use Exception;

class WebSocketServer implements MessageComponentInterface
{
    protected $clients;
    protected $subscriptions;
    protected $db;

    public function __construct()
    {
        $this->clients = new SplObjectStorage;
        $this->subscriptions = [];
        
        try {
            require_once __DIR__ . '/../includes/db_connect.php';
            $this->db = $conn;
        } catch (Exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        
        // Remove all subscriptions for this connection
        foreach ($this->subscriptions as $topic => $subscribers) {
            if (($key = array_search($conn, $subscribers)) !== false) {
                unset($this->subscriptions[$topic][$key]);
            }
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        try {
            $data = json_decode($msg, true);
            if (!$data || !isset($data['action'])) {
                throw new Exception('Invalid message format');
            }

            switch ($data['action']) {
                case 'subscribe':
                    $this->handleSubscribe($from, $data);
                    break;
                case 'unsubscribe':
                    $this->handleUnsubscribe($from, $data);
                    break;
                case 'getBatch':
                    $this->handleGetBatch($from, $data);
                    break;
                default:
                    throw new Exception('Unknown action');
            }
        } catch (Exception $e) {
            $this->sendError($from, $e->getMessage());
        }
    }

    protected function handleSubscribe(ConnectionInterface $conn, array $data)
    {
        if (!isset($data['topic'])) {
            throw new Exception('Topic not specified');
        }

        $topic = $data['topic'];
        if (!isset($this->subscriptions[$topic])) {
            $this->subscriptions[$topic] = [];
        }

        if (!in_array($conn, $this->subscriptions[$topic])) {
            $this->subscriptions[$topic][] = $conn;
        }

        $this->sendSuccess($conn, "Subscribed to $topic");
    }

    protected function handleUnsubscribe(ConnectionInterface $conn, array $data)
    {
        if (!isset($data['topic'])) {
            throw new Exception('Topic not specified');
        }

        $topic = $data['topic'];
        if (isset($this->subscriptions[$topic])) {
            if (($key = array_search($conn, $this->subscriptions[$topic])) !== false) {
                unset($this->subscriptions[$topic][$key]);
                $this->sendSuccess($conn, "Unsubscribed from $topic");
            }
        }
    }

    protected function handleGetBatch(ConnectionInterface $conn, array $data)
    {
        if (!isset($data['batchId'])) {
            throw new Exception('Batch ID not specified');
        }

        $batchId = $data['batchId'];
        
        try {
            $stmt = $this->db->prepare("
                SELECT wb.*, f.name as farm_name, qc.overall_grade, pr.status as processing_status 
                FROM wool_batches wb 
                LEFT JOIN farms f ON wb.farm_id = f.id 
                LEFT JOIN quality_checks qc ON wb.id = qc.batch_id 
                LEFT JOIN processing_records pr ON wb.id = pr.batch_id 
                WHERE wb.id = ?
            ");
            
            $stmt->execute([$batchId]);
            $batch = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($batch) {
                $this->sendSuccess($conn, "Batch data retrieved", $batch);
            } else {
                throw new Exception('Batch not found');
            }
        } catch (Exception $e) {
            throw new Exception('Failed to fetch batch data: ' . $e->getMessage());
        }
    }

    protected function broadcast($topic, $message)
    {
        if (isset($this->subscriptions[$topic])) {
            foreach ($this->subscriptions[$topic] as $client) {
                $client->send(json_encode($message));
            }
        }
    }

    protected function sendSuccess(ConnectionInterface $conn, string $message, $data = null)
    {
        $response = [
            'status' => 'success',
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        $conn->send(json_encode($response));
    }

    protected function sendError(ConnectionInterface $conn, string $message)
    {
        $conn->send(json_encode([
            'status' => 'error',
            'message' => $message
        ]));
    }
} 