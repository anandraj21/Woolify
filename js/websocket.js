class WoolifyWebSocket {
    constructor() {
        this.socket = null;
        this.subscriptions = new Set();
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000; // Start with 1 second delay
    }

    connect() {
        try {
            this.socket = new WebSocket('ws://localhost:8080');
            
            this.socket.onopen = () => {
                console.log('Connected to WebSocket server');
                this.reconnectAttempts = 0;
                this.resubscribe();
            };

            this.socket.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleMessage(data);
                } catch (error) {
                    console.error('Error parsing WebSocket message:', error);
                }
            };

            this.socket.onclose = () => {
                console.log('WebSocket connection closed');
                this.attemptReconnect();
            };

            this.socket.onerror = (error) => {
                console.error('WebSocket error:', error);
            };
        } catch (error) {
            console.error('Error connecting to WebSocket server:', error);
            this.attemptReconnect();
        }
    }

    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1);
            console.log(`Attempting to reconnect in ${delay}ms...`);
            setTimeout(() => this.connect(), delay);
        } else {
            console.error('Max reconnection attempts reached');
        }
    }

    subscribeToBatch(batchId) {
        if (!this.socket || this.socket.readyState !== WebSocket.OPEN) {
            console.error('WebSocket is not connected');
            return;
        }

        const message = {
            action: 'subscribe',
            batchId: batchId
        };

        this.socket.send(JSON.stringify(message));
        this.subscriptions.add(batchId);
    }

    unsubscribeFromBatch(batchId) {
        if (!this.socket || this.socket.readyState !== WebSocket.OPEN) {
            console.error('WebSocket is not connected');
            return;
        }

        const message = {
            action: 'unsubscribe',
            batchId: batchId
        };

        this.socket.send(JSON.stringify(message));
        this.subscriptions.delete(batchId);
    }

    resubscribe() {
        this.subscriptions.forEach(batchId => {
            this.subscribeToBatch(batchId);
        });
    }

    handleMessage(data) {
        if (!data.action || !data.batchId) {
            console.error('Invalid message format:', data);
            return;
        }

        switch (data.action) {
            case 'update':
                // Dispatch a custom event with the batch update
                const event = new CustomEvent('batchUpdate', { 
                    detail: { 
                        batchId: data.batchId,
                        data: data.data 
                    } 
                });
                document.dispatchEvent(event);
                break;
            case 'error':
                console.error('Server error:', data.message);
                break;
            default:
                console.warn('Unknown action:', data.action);
        }
    }

    disconnect() {
        if (this.socket) {
            this.socket.close();
            this.socket = null;
        }
    }
}

// Create a global instance
window.woolifySocket = new WoolifyWebSocket();

// Connect when the page loads
document.addEventListener('DOMContentLoaded', () => {
    window.woolifySocket.connect();
});

// Example usage in other scripts:
/*
// Subscribe to updates for a batch
woolifySocket.subscribeToBatch(123);

// Listen for batch updates
document.addEventListener('batchUpdate', (event) => {
    const { batchId, data } = event.detail;
    console.log(`Received update for batch ${batchId}:`, data);
    // Update UI elements with the new data
});

// Unsubscribe when no longer needed
woolifySocket.unsubscribeFromBatch(123);
*/ 