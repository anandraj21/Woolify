class WoolifyWebSocket {
    constructor(url = 'ws://localhost:8080') {
        this.url = url;
        this.socket = null;
        this.subscriptions = new Set();
        this.messageHandlers = new Map();
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000; // Start with 1 second
    }

    connect() {
        try {
            this.socket = new WebSocket(this.url);
            
            this.socket.onopen = () => {
                console.log('Connected to WebSocket server');
                this.reconnectAttempts = 0;
                this.resubscribe();
            };

            this.socket.onclose = () => {
                console.log('Disconnected from WebSocket server');
                this.handleReconnect();
            };

            this.socket.onerror = (error) => {
                console.error('WebSocket error:', error);
            };

            this.socket.onmessage = (event) => {
                try {
                    const message = JSON.parse(event.data);
                    this.handleMessage(message);
                } catch (error) {
                    console.error('Error parsing message:', error);
                }
            };
        } catch (error) {
            console.error('Error connecting to WebSocket server:', error);
            this.handleReconnect();
        }
    }

    handleReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);
            console.log(`Attempting to reconnect in ${delay}ms...`);
            setTimeout(() => this.connect(), delay);
        } else {
            console.error('Max reconnection attempts reached');
        }
    }

    resubscribe() {
        for (const topic of this.subscriptions) {
            this.subscribe(topic);
        }
    }

    subscribe(topic) {
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            this.subscriptions.add(topic);
            this.send({
                action: 'subscribe',
                topic: topic
            });
        }
    }

    unsubscribe(topic) {
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            this.subscriptions.delete(topic);
            this.send({
                action: 'unsubscribe',
                topic: topic
            });
        }
    }

    send(data) {
        if (this.socket && this.socket.readyState === WebSocket.OPEN) {
            this.socket.send(JSON.stringify(data));
        } else {
            console.error('WebSocket is not connected');
        }
    }

    on(topic, handler) {
        if (!this.messageHandlers.has(topic)) {
            this.messageHandlers.set(topic, new Set());
        }
        this.messageHandlers.get(topic).add(handler);
    }

    off(topic, handler) {
        if (this.messageHandlers.has(topic)) {
            this.messageHandlers.get(topic).delete(handler);
        }
    }

    handleMessage(message) {
        if (message.topic && this.messageHandlers.has(message.topic)) {
            for (const handler of this.messageHandlers.get(message.topic)) {
                try {
                    handler(message.data);
                } catch (error) {
                    console.error('Error in message handler:', error);
                }
            }
        }
    }
}

// Example usage:
/*
const ws = new WoolifyWebSocket();
ws.connect();

// Subscribe to batch updates
ws.subscribe('batch_updates');

// Handle batch updates
ws.on('batch_updates', (data) => {
    console.log('Received batch update:', data);
    // Update UI with new batch data
});

// Send a message
ws.send({
    action: 'get_batch',
    batchId: 123
});
*/ 