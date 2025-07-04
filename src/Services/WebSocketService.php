<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Services;

use Turkpin\AdminKit\Services\CacheService;
use Turkpin\AdminKit\Services\AuthService;

class WebSocketService
{
    private CacheService $cacheService;
    private AuthService $authService;
    private array $config;
    private array $connections;
    private array $channels;
    private array $eventHandlers;

    public function __construct(
        CacheService $cacheService,
        AuthService $authService,
        array $config = []
    ) {
        $this->cacheService = $cacheService;
        $this->authService = $authService;
        $this->config = array_merge([
            'enabled' => true,
            'port' => 8080,
            'host' => '0.0.0.0',
            'max_connections' => 1000,
            'heartbeat_interval' => 30,
            'auth_required' => true,
            'ssl_enabled' => false,
            'ssl_cert' => null,
            'ssl_key' => null,
            'fallback_polling' => true,
            'polling_interval' => 5000
        ], $config);
        
        $this->connections = [];
        $this->channels = [];
        $this->eventHandlers = [];
        
        $this->registerDefaultChannels();
        $this->registerDefaultEvents();
    }

    /**
     * Register default channels
     */
    private function registerDefaultChannels(): void
    {
        $this->channels = [
            'notifications' => [
                'description' => 'Real-time notifications',
                'auth_required' => true,
                'private' => true
            ],
            'system' => [
                'description' => 'System-wide announcements',
                'auth_required' => true,
                'private' => false
            ],
            'performance' => [
                'description' => 'Performance metrics',
                'auth_required' => true,
                'private' => false,
                'roles' => ['admin']
            ],
            'audit' => [
                'description' => 'Audit events',
                'auth_required' => true,
                'private' => false,
                'roles' => ['admin', 'auditor']
            ],
            'chat' => [
                'description' => 'Admin chat',
                'auth_required' => true,
                'private' => false
            ],
            'presence' => [
                'description' => 'User presence tracking',
                'auth_required' => true,
                'private' => false
            ]
        ];
    }

    /**
     * Register default event handlers
     */
    private function registerDefaultEvents(): void
    {
        $this->eventHandlers = [
            'connect' => [$this, 'handleConnect'],
            'disconnect' => [$this, 'handleDisconnect'],
            'authenticate' => [$this, 'handleAuthenticate'],
            'subscribe' => [$this, 'handleSubscribe'],
            'unsubscribe' => [$this, 'handleUnsubscribe'],
            'ping' => [$this, 'handlePing'],
            'message' => [$this, 'handleMessage'],
            'notification' => [$this, 'handleNotification'],
            'typing' => [$this, 'handleTyping'],
            'presence' => [$this, 'handlePresence']
        ];
    }

    /**
     * Start WebSocket server
     */
    public function start(): void
    {
        if (!$this->config['enabled']) {
            return;
        }

        echo "Starting AdminKit WebSocket Server on {$this->config['host']}:{$this->config['port']}\n";
        
        // In real implementation, use ReactPHP or similar
        // This is a simplified structure
        $this->simulateServer();
    }

    /**
     * Simulate WebSocket server (for demonstration)
     */
    private function simulateServer(): void
    {
        // Create socket server
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($socket, $this->config['host'], $this->config['port']);
        socket_listen($socket);

        echo "WebSocket server listening...\n";

        while (true) {
            $client = socket_accept($socket);
            if ($client) {
                $this->handleNewConnection($client);
            }
        }
    }

    /**
     * Handle new WebSocket connection
     */
    private function handleNewConnection($client): void
    {
        $connectionId = uniqid('ws_');
        
        $this->connections[$connectionId] = [
            'id' => $connectionId,
            'socket' => $client,
            'authenticated' => false,
            'user_id' => null,
            'channels' => [],
            'last_ping' => time(),
            'ip' => $this->getClientIp($client),
            'user_agent' => '',
            'connected_at' => time()
        ];

        $this->emit('connect', ['connection_id' => $connectionId]);
        
        echo "New connection: {$connectionId}\n";
    }

    /**
     * Broadcast message to all connections in channel
     */
    public function broadcast(string $channel, array $data, array $excludeConnections = []): void
    {
        if (!isset($this->channels[$channel])) {
            return;
        }

        $message = $this->formatMessage('broadcast', $data, [
            'channel' => $channel,
            'timestamp' => time()
        ]);

        foreach ($this->connections as $connectionId => $connection) {
            if (in_array($connectionId, $excludeConnections)) {
                continue;
            }

            if (in_array($channel, $connection['channels'])) {
                $this->sendToConnection($connectionId, $message);
            }
        }

        // Also send via Server-Sent Events if enabled
        if ($this->config['fallback_polling']) {
            $this->sendViaSSE($channel, $data);
        }
    }

    /**
     * Send message to specific user
     */
    public function sendToUser(int $userId, array $data, string $event = 'message'): void
    {
        $message = $this->formatMessage($event, $data, [
            'user_id' => $userId,
            'timestamp' => time()
        ]);

        foreach ($this->connections as $connection) {
            if ($connection['user_id'] === $userId) {
                $this->sendToConnection($connection['id'], $message);
            }
        }

        // Fallback to database notification if user not connected
        $userConnected = false;
        foreach ($this->connections as $connection) {
            if ($connection['user_id'] === $userId) {
                $userConnected = true;
                break;
            }
        }

        if (!$userConnected) {
            $this->storeOfflineMessage($userId, $data, $event);
        }
    }

    /**
     * Send message to specific connection
     */
    private function sendToConnection(string $connectionId, array $message): void
    {
        if (!isset($this->connections[$connectionId])) {
            return;
        }

        $connection = $this->connections[$connectionId];
        $jsonMessage = json_encode($message);

        // In real implementation, send via WebSocket
        // For now, we'll simulate by storing in cache
        $this->cacheService->set("ws_message:{$connectionId}:" . uniqid(), $message, 300);
    }

    /**
     * Format message for transmission
     */
    private function formatMessage(string $event, array $data, array $meta = []): array
    {
        return [
            'event' => $event,
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => time(),
                'server_id' => gethostname()
            ], $meta)
        ];
    }

    /**
     * Handle client authentication
     */
    public function handleAuthenticate(array $data, string $connectionId): void
    {
        if (!isset($this->connections[$connectionId])) {
            return;
        }

        $token = $data['token'] ?? null;
        if (!$token) {
            $this->sendError($connectionId, 'Authentication token required');
            return;
        }

        // Verify token
        $user = $this->authService->validateToken($token);
        if (!$user) {
            $this->sendError($connectionId, 'Invalid authentication token');
            return;
        }

        // Update connection
        $this->connections[$connectionId]['authenticated'] = true;
        $this->connections[$connectionId]['user_id'] = $user['id'];

        // Send success response
        $this->sendToConnection($connectionId, $this->formatMessage('authenticated', [
            'user_id' => $user['id'],
            'name' => $user['name'],
            'roles' => $user['roles'] ?? []
        ]));

        // Auto-subscribe to user's private channel
        $this->subscribeToChannel($connectionId, "user.{$user['id']}");

        echo "User {$user['id']} authenticated on connection {$connectionId}\n";
    }

    /**
     * Handle channel subscription
     */
    public function handleSubscribe(array $data, string $connectionId): void
    {
        $channel = $data['channel'] ?? null;
        if (!$channel) {
            $this->sendError($connectionId, 'Channel name required');
            return;
        }

        if (!$this->canSubscribeToChannel($connectionId, $channel)) {
            $this->sendError($connectionId, 'Not authorized to subscribe to this channel');
            return;
        }

        $this->subscribeToChannel($connectionId, $channel);
        
        $this->sendToConnection($connectionId, $this->formatMessage('subscribed', [
            'channel' => $channel
        ]));
    }

    /**
     * Subscribe connection to channel
     */
    private function subscribeToChannel(string $connectionId, string $channel): void
    {
        if (!isset($this->connections[$connectionId])) {
            return;
        }

        if (!in_array($channel, $this->connections[$connectionId]['channels'])) {
            $this->connections[$connectionId]['channels'][] = $channel;
        }

        // Track channel subscribers
        $this->cacheService->set("channel_subscribers:{$channel}", 
            $this->getChannelSubscribers($channel), 3600);
    }

    /**
     * Check if connection can subscribe to channel
     */
    private function canSubscribeToChannel(string $connectionId, string $channel): bool
    {
        $connection = $this->connections[$connectionId] ?? null;
        if (!$connection) {
            return false;
        }

        $channelConfig = $this->channels[$channel] ?? null;
        if (!$channelConfig) {
            return false; // Unknown channel
        }

        // Check authentication requirement
        if ($channelConfig['auth_required'] && !$connection['authenticated']) {
            return false;
        }

        // Check role requirements
        if (isset($channelConfig['roles']) && $connection['authenticated']) {
            $user = $this->authService->getUser($connection['user_id']);
            $userRoles = $user['roles'] ?? [];
            
            $hasRequiredRole = false;
            foreach ($channelConfig['roles'] as $requiredRole) {
                if (in_array($requiredRole, $userRoles)) {
                    $hasRequiredRole = true;
                    break;
                }
            }
            
            if (!$hasRequiredRole) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get channel subscribers
     */
    private function getChannelSubscribers(string $channel): array
    {
        $subscribers = [];
        
        foreach ($this->connections as $connection) {
            if (in_array($channel, $connection['channels'])) {
                $subscribers[] = [
                    'connection_id' => $connection['id'],
                    'user_id' => $connection['user_id'],
                    'authenticated' => $connection['authenticated']
                ];
            }
        }
        
        return $subscribers;
    }

    /**
     * Handle disconnect
     */
    public function handleDisconnect(string $connectionId): void
    {
        if (isset($this->connections[$connectionId])) {
            $connection = $this->connections[$connectionId];
            
            // Update presence if user was authenticated
            if ($connection['authenticated'] && $connection['user_id']) {
                $this->updateUserPresence($connection['user_id'], 'offline');
            }
            
            unset($this->connections[$connectionId]);
            echo "Connection {$connectionId} disconnected\n";
        }
    }

    /**
     * Handle ping for connection health
     */
    public function handlePing(array $data, string $connectionId): void
    {
        if (isset($this->connections[$connectionId])) {
            $this->connections[$connectionId]['last_ping'] = time();
            
            $this->sendToConnection($connectionId, $this->formatMessage('pong', [
                'timestamp' => time()
            ]));
        }
    }

    /**
     * Handle user presence updates
     */
    public function handlePresence(array $data, string $connectionId): void
    {
        $connection = $this->connections[$connectionId] ?? null;
        if (!$connection || !$connection['authenticated']) {
            return;
        }

        $status = $data['status'] ?? 'online';
        $this->updateUserPresence($connection['user_id'], $status);
        
        // Broadcast presence update
        $this->broadcast('presence', [
            'user_id' => $connection['user_id'],
            'status' => $status,
            'timestamp' => time()
        ]);
    }

    /**
     * Update user presence status
     */
    private function updateUserPresence(int $userId, string $status): void
    {
        $presence = [
            'user_id' => $userId,
            'status' => $status,
            'last_seen' => time(),
            'connections' => $this->getUserConnectionCount($userId)
        ];
        
        $this->cacheService->set("presence:{$userId}", $presence, 300);
    }

    /**
     * Get user connection count
     */
    private function getUserConnectionCount(int $userId): int
    {
        $count = 0;
        foreach ($this->connections as $connection) {
            if ($connection['user_id'] === $userId) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Send via Server-Sent Events (fallback)
     */
    private function sendViaSSE(string $channel, array $data): void
    {
        $sseMessage = [
            'channel' => $channel,
            'data' => $data,
            'timestamp' => time()
        ];
        
        // Store for SSE endpoint to pick up
        $sseKey = "sse_messages:" . date('Y-m-d-H-i');
        $messages = $this->cacheService->get($sseKey, fn() => []);
        $messages[] = $sseMessage;
        
        // Keep only recent messages
        if (count($messages) > 100) {
            $messages = array_slice($messages, -100);
        }
        
        $this->cacheService->set($sseKey, $messages, 300);
    }

    /**
     * Store offline message for later delivery
     */
    private function storeOfflineMessage(int $userId, array $data, string $event): void
    {
        $message = [
            'user_id' => $userId,
            'event' => $event,
            'data' => $data,
            'created_at' => time()
        ];
        
        $offlineKey = "offline_messages:{$userId}";
        $messages = $this->cacheService->get($offlineKey, fn() => []);
        $messages[] = $message;
        
        // Keep only recent messages
        if (count($messages) > 50) {
            $messages = array_slice($messages, -50);
        }
        
        $this->cacheService->set($offlineKey, $messages, 86400);
    }

    /**
     * Send error message to connection
     */
    private function sendError(string $connectionId, string $message): void
    {
        $this->sendToConnection($connectionId, $this->formatMessage('error', [
            'message' => $message
        ]));
    }

    /**
     * Get client IP address
     */
    private function getClientIp($socket): string
    {
        // In real implementation, extract from socket
        return '127.0.0.1';
    }

    /**
     * Clean up stale connections
     */
    public function cleanupConnections(): void
    {
        $now = time();
        $timeout = $this->config['heartbeat_interval'] * 2;
        
        foreach ($this->connections as $connectionId => $connection) {
            if (($now - $connection['last_ping']) > $timeout) {
                $this->handleDisconnect($connectionId);
            }
        }
    }

    /**
     * Get connection statistics
     */
    public function getStats(): array
    {
        $totalConnections = count($this->connections);
        $authenticatedConnections = 0;
        $uniqueUsers = [];
        
        foreach ($this->connections as $connection) {
            if ($connection['authenticated']) {
                $authenticatedConnections++;
                if ($connection['user_id']) {
                    $uniqueUsers[$connection['user_id']] = true;
                }
            }
        }
        
        return [
            'total_connections' => $totalConnections,
            'authenticated_connections' => $authenticatedConnections,
            'unique_users' => count($uniqueUsers),
            'channels' => count($this->channels),
            'uptime' => time() - ($_SERVER['REQUEST_TIME'] ?? time())
        ];
    }

    /**
     * Render WebSocket client JavaScript
     */
    public function renderClientScript(): string
    {
        $config = [
            'url' => ($this->config['ssl_enabled'] ? 'wss://' : 'ws://') . 
                     $_SERVER['HTTP_HOST'] . ':' . $this->config['port'],
            'fallback_polling' => $this->config['fallback_polling'],
            'polling_interval' => $this->config['polling_interval'],
            'heartbeat_interval' => $this->config['heartbeat_interval'] * 1000
        ];
        
        return '
        <script>
        class AdminKitWebSocket {
            constructor(config) {
                this.config = config;
                this.socket = null;
                this.authenticated = false;
                this.reconnectAttempts = 0;
                this.maxReconnectAttempts = 5;
                this.eventHandlers = {};
                this.subscriptions = new Set();
                this.pollingInterval = null;
                
                this.connect();
            }
            
            connect() {
                if (!window.WebSocket) {
                    console.warn("WebSocket not supported, falling back to polling");
                    this.startPolling();
                    return;
                }
                
                try {
                    this.socket = new WebSocket(this.config.url);
                    this.setupEventHandlers();
                } catch (error) {
                    console.error("WebSocket connection failed:", error);
                    if (this.config.fallback_polling) {
                        this.startPolling();
                    }
                }
            }
            
            setupEventHandlers() {
                this.socket.onopen = () => {
                    console.log("WebSocket connected");
                    this.reconnectAttempts = 0;
                    this.startHeartbeat();
                    this.emit("connect");
                };
                
                this.socket.onmessage = (event) => {
                    try {
                        const message = JSON.parse(event.data);
                        this.handleMessage(message);
                    } catch (error) {
                        console.error("Failed to parse WebSocket message:", error);
                    }
                };
                
                this.socket.onclose = () => {
                    console.log("WebSocket disconnected");
                    this.stopHeartbeat();
                    this.authenticated = false;
                    this.emit("disconnect");
                    this.attemptReconnect();
                };
                
                this.socket.onerror = (error) => {
                    console.error("WebSocket error:", error);
                    this.emit("error", error);
                };
            }
            
            handleMessage(message) {
                const { event, data, meta } = message;
                
                switch (event) {
                    case "authenticated":
                        this.authenticated = true;
                        this.resubscribeChannels();
                        break;
                    case "notification":
                        this.showNotification(data);
                        break;
                    case "broadcast":
                        this.emit("broadcast", data, meta);
                        break;
                    case "pong":
                        // Heartbeat response
                        break;
                    default:
                        this.emit(event, data, meta);
                }
            }
            
            authenticate(token) {
                this.send("authenticate", { token });
            }
            
            subscribe(channel) {
                if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                    this.send("subscribe", { channel });
                }
                this.subscriptions.add(channel);
            }
            
            unsubscribe(channel) {
                if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                    this.send("unsubscribe", { channel });
                }
                this.subscriptions.delete(channel);
            }
            
            send(event, data = {}) {
                if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                    this.socket.send(JSON.stringify({ event, data }));
                }
            }
            
            on(event, handler) {
                if (!this.eventHandlers[event]) {
                    this.eventHandlers[event] = [];
                }
                this.eventHandlers[event].push(handler);
            }
            
            emit(event, ...args) {
                if (this.eventHandlers[event]) {
                    this.eventHandlers[event].forEach(handler => {
                        try {
                            handler(...args);
                        } catch (error) {
                            console.error(`Error in ${event} handler:`, error);
                        }
                    });
                }
            }
            
            startHeartbeat() {
                this.heartbeatInterval = setInterval(() => {
                    this.send("ping");
                }, this.config.heartbeat_interval);
            }
            
            stopHeartbeat() {
                if (this.heartbeatInterval) {
                    clearInterval(this.heartbeatInterval);
                    this.heartbeatInterval = null;
                }
            }
            
            attemptReconnect() {
                if (this.reconnectAttempts < this.maxReconnectAttempts) {
                    this.reconnectAttempts++;
                    const delay = Math.pow(2, this.reconnectAttempts) * 1000;
                    
                    console.log(`Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`);
                    
                    setTimeout(() => {
                        this.connect();
                    }, delay);
                } else {
                    console.warn("Max reconnect attempts reached, falling back to polling");
                    if (this.config.fallback_polling) {
                        this.startPolling();
                    }
                }
            }
            
            resubscribeChannels() {
                this.subscriptions.forEach(channel => {
                    this.send("subscribe", { channel });
                });
            }
            
            startPolling() {
                if (this.pollingInterval) return;
                
                console.log("Starting Server-Sent Events polling");
                
                this.pollingInterval = setInterval(() => {
                    this.pollForMessages();
                }, this.config.polling_interval);
            }
            
            stopPolling() {
                if (this.pollingInterval) {
                    clearInterval(this.pollingInterval);
                    this.pollingInterval = null;
                }
            }
            
            pollForMessages() {
                fetch("/admin/api/sse-messages")
                    .then(response => response.json())
                    .then(messages => {
                        messages.forEach(message => {
                            this.emit("broadcast", message.data, {
                                channel: message.channel,
                                timestamp: message.timestamp
                            });
                        });
                    })
                    .catch(error => {
                        console.error("Polling error:", error);
                    });
            }
            
            showNotification(data) {
                // Integrate with notification system
                if (window.notificationManager) {
                    window.notificationManager.showToast(data);
                } else {
                    console.log("Real-time notification:", data);
                }
            }
            
            updatePresence(status) {
                this.send("presence", { status });
            }
            
            disconnect() {
                this.stopHeartbeat();
                this.stopPolling();
                
                if (this.socket) {
                    this.socket.close();
                }
            }
        }
        
        // Initialize WebSocket connection
        const wsConfig = ' . json_encode($config) . ';
        window.adminKitWS = new AdminKitWebSocket(wsConfig);
        
        // Auto-authenticate if token available
        const authToken = localStorage.getItem("adminkit_token") || 
                         document.querySelector("meta[name=csrf-token]")?.content;
        
        if (authToken) {
            window.adminKitWS.on("connect", () => {
                window.adminKitWS.authenticate(authToken);
            });
        }
        
        // Handle page visibility for presence
        document.addEventListener("visibilitychange", () => {
            if (window.adminKitWS.authenticated) {
                const status = document.hidden ? "away" : "online";
                window.adminKitWS.updatePresence(status);
            }
        });
        
        // Handle page unload
        window.addEventListener("beforeunload", () => {
            window.adminKitWS.disconnect();
        });
        </script>';
    }

    /**
     * Handle real-time events
     */
    public function handleConnect(array $data): void
    {
        // Connection established
    }

    public function handleMessage(array $data, string $connectionId): void
    {
        // Handle generic message
    }

    public function handleNotification(array $data, string $connectionId): void
    {
        // Handle notification event
    }

    public function handleTyping(array $data, string $connectionId): void
    {
        // Handle typing indicator
        $connection = $this->connections[$connectionId] ?? null;
        if ($connection && $connection['authenticated']) {
            $this->broadcast('typing', [
                'user_id' => $connection['user_id'],
                'channel' => $data['channel'] ?? 'general',
                'typing' => $data['typing'] ?? false
            ]);
        }
    }

    /**
     * Emit event to handlers
     */
    private function emit(string $event, array $data): void
    {
        if (isset($this->eventHandlers[$event])) {
            call_user_func($this->eventHandlers[$event], $data);
        }
    }
}
