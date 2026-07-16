class ChatWebSocket {
    constructor() {
        this.socket = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 3000;
        this.currentChatId = null;
        this.authToken = null;
        this.heartbeatInterval = null;
        this.connectionFailedPermanently = false;
        this.lastConnectionAttempt = 0;
        this.minRetryInterval = 30000;
        this.isEmployee = false;
        this.isGlobalMode = false;
    }

    async connect() {
        console.log('Global Config:', window.globalWebSocketConfig);
        console.log('Global Config URLs:', window.globalWebSocketConfig?.urls);

        const now = Date.now();
        if (this.connectionFailedPermanently && (now - this.lastConnectionAttempt) < this.minRetryInterval) {
            return;
        }

        this.lastConnectionAttempt = now;

        try {
            const config = window.globalWebSocketConfig;

            if (!config) {
                throw new Error('No WebSocket configuration found');
            }

            this.isGlobalMode = true;

            const tokenUrl = config.urls?.generateWebsocketToken;

            if (!tokenUrl) {
                throw new Error('No token URL configured');
            }

            console.log('Requesting WebSocket token from:', tokenUrl);

            const response = await fetch(tokenUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error('Failed to get authentication token: ' + (data.message || 'Unknown error'));
            }

            this.authToken = data.token;

            const websocketUrl = config.websocketUrl;

            if (!websocketUrl) {
                throw new Error('No WebSocket URL configured');
            }

            console.log('Connecting to WebSocket:', websocketUrl);

            this.socket = new WebSocket(websocketUrl);

            this.socket.onopen = () => this.onOpen();
            this.socket.onmessage = (event) => this.onMessage(event);
            this.socket.onclose = () => this.onClose();
            this.socket.onerror = (error) => this.onError(error);

        } catch (error) {
            console.warn('WebSocket connection failed:', error.message);
            this.handleConnectionFailure();
        }
    }

    onOpen() {
        console.log('WebSocket connected - real-time chat enabled');
        this.isConnected = true;
        this.reconnectAttempts = 0;
        this.reconnectDelay = 3000;
        this.connectionFailedPermanently = false;

        this.send({
            type: 'auth',
            token: this.authToken
        });

        if (this.currentChatId) {
            this.joinChat(this.currentChatId);
        }

        this.startHeartbeat();
    }

    onMessage(event) {
        try {
            const data = JSON.parse(event.data);

            switch (data.type) {
                case 'auth_success':
                    console.log('WebSocket authenticated successfully');
                    break;

                case 'auth_error':
                    console.error('WebSocket auth error:', data.message);
                    this.handleConnectionFailure();
                    break;

                case 'new_message':
                    this.handleNewMessage(data);
                    break;

                case 'unread_update':
                    this.handleUnreadUpdate(data);
                    break;

                case 'pong':
                    break;

                default:
                    console.log('Unknown WebSocket message type:', data.type);
            }
        } catch (error) {
            console.error('Error parsing WebSocket message:', error);
        }
    }

    onClose() {
        console.log('WebSocket disconnected');
        this.isConnected = false;
        this.stopHeartbeat();

        setTimeout(() => {
            this.scheduleReconnect();
        }, 1000);
    }

    onError(error) {
        console.warn('WebSocket error:', error);
        this.handleConnectionFailure();
    }

    handleConnectionFailure() {
        this.isConnected = false;
        this.stopHeartbeat();
        this.scheduleReconnect();
    }

    send(data) {
        if (this.isConnected && this.socket && this.socket.readyState === WebSocket.OPEN) {
            try {
                this.socket.send(JSON.stringify(data));
                return true;
            } catch (error) {
                console.warn('Failed to send WebSocket message:', error);
                return false;
            }
        }
        return false;
    }

    joinChat(chatId) {
        this.currentChatId = chatId;
        if (this.isConnected) {
            this.send({
                type: 'join_chat',
                chatId: chatId
            });
        }
    }

    handleNewMessage(data) {
        console.log('=== handleNewMessage START ===');
        console.log('Full data received:', data);

        const { chatId, message, unreadUpdate } = data;

        console.log('Extracted values:');
        console.log('- chatId:', chatId);
        console.log('- message:', message);
        console.log('- unreadUpdate:', unreadUpdate);
        console.log('- this.currentChatId:', this.currentChatId);
        console.log('- this.isOnChatPage():', this.isOnChatPage());
        console.log('- Chat IDs match:', this.currentChatId === chatId);

        if (this.currentChatId === chatId && this.isOnChatPage()) {
            console.log('✓ Conditions met - adding message to current chat');
            this.addMessageToChat(message);

            if (!message.isOwn) {
                console.log('✓ Message from other user - marking as read and hiding badge');
                this.markChatAsRead(chatId);
                $(`.chat-item[data-chat-id="${chatId}"] .chat-badge`).hide();
            } else {
                console.log('✓ Own message - no need to mark as read');
            }
        } else {
            console.log('✗ Conditions not met - updating chat badge instead');
            console.log('  Reason: currentChatId =', this.currentChatId, ', chatId =', chatId, ', isOnChatPage =', this.isOnChatPage());
            this.updateChatBadge(chatId);
        }

        if (unreadUpdate) {
            console.log('✓ Updating unread counters from WebSocket data');
            console.log('- Total unread:', unreadUpdate.totalUnread);
            this.updateNavbarUnreadCount(unreadUpdate.totalUnread);

            if (unreadUpdate.chatUnread && unreadUpdate.chatUnread.chatId !== this.currentChatId) {
                console.log('✓ Updating specific chat badge:', unreadUpdate.chatUnread);
                this.updateChatUnreadBadge(
                    unreadUpdate.chatUnread.chatId,
                    unreadUpdate.chatUnread.count
                );
            } else {
                console.log('✗ Not updating specific chat badge (current chat or no data)');
            }
        } else {
            console.log('✗ No unreadUpdate data received');
        }

        console.log('=== handleNewMessage END ===');
    }

    handleUnreadUpdate(data) {
        this.updateNavbarUnreadCount(data.totalUnread);

        if (data.chatUnread && this.isOnChatPage()) {
            this.updateChatUnreadBadge(data.chatUnread.chatId, data.chatUnread.count);
        }
    }

    addMessageToChat(messageData) {
        console.log('addMessageToChat called with:', messageData);
        console.log('isOnChatPage():', this.isOnChatPage());
        console.log('Message already exists:', $(`[data-message-id="${messageData.id}"]`).length > 0);

        if (!this.isOnChatPage() || $(`[data-message-id="${messageData.id}"]`).length > 0) {
            console.log('Exiting addMessageToChat early');
            return;
        }

        const formattedData = {
            id: messageData.id,
            text: messageData.text,
            is_own: messageData.isOwn,
            sender_name: messageData.senderName,
            created_at_timestamp: messageData.createdAt,
            created_at: messageData.createdAt,
            is_edited: messageData.isEdited || false,
            can_edit: false,
            has_attachments: messageData.hasAttachments || false,
            attachments: messageData.attachments || [],
            reply_to: messageData.replyTo || null
        };

        console.log('Formatted data:', formattedData);
        console.log('window.createMessageHtml exists:', typeof window.createMessageHtml === 'function');

        const messageHtml = window.createMessageHtml ?
            window.createMessageHtml(formattedData) :
            this.createMessageHtml(messageData);

        console.log('Generated HTML:', messageHtml);
        console.log('Messages list element exists:', $("#messages-list").length > 0);

        $("#messages-list").append(messageHtml);
        $("#no-messages").remove();
        this.scrollToBottom();

        console.log('Message added successfully');
    }

    createMessageHtml(messageData) {
        const messageClass = messageData.isOwn ? "own" : "other";
        let messageHtml = `<div class="message ${messageClass}" data-message-id="${messageData.id}">`;

        messageHtml += '<div class="message-bubble">';

        if (messageData.text) {
            messageHtml += `<div class="message-text" id="message-text-${messageData.id}">`;
            messageHtml += this.escapeHtml(messageData.text).replace(/\n/g, "<br>");
            messageHtml += '</div>';
        }

        if (messageData.attachments && messageData.attachments.length > 0) {
            messageHtml += '<div class="message-attachments">';
            messageData.attachments.forEach(attachment => {
                if (attachment.is_image) {
                    messageHtml += `<div class="attachment-image-preview">`;
                    messageHtml += `<img src="${attachment.url}" alt="${this.escapeHtml(attachment.original_name)}" `;
                    messageHtml += `class="attachment-image" onclick="openImageModal('${attachment.url}', '${this.escapeHtml(attachment.original_name)}')">`;
                    messageHtml += `</div>`;
                } else {
                    messageHtml += `<a href="${attachment.url}" class="attachment-item" target="_blank">`;
                    messageHtml += `<i class="${attachment.icon}"></i> ${this.escapeHtml(attachment.original_name)}`;
                    messageHtml += `</a>`;
                }
            });
            messageHtml += '</div>';
        }

        messageHtml += '<div class="message-info">';
        if (!messageData.isOwn) {
            messageHtml += `<strong>${this.escapeHtml(messageData.senderName)}</strong><br>`;
        }
        messageHtml += this.formatMessageDate(messageData.createdAt);
        messageHtml += '</div>';

        messageHtml += '</div>';
        messageHtml += '</div>';

        return messageHtml;
    }

    updateNavbarUnreadCount(count) {
        const badge = $('.navbar-badge');
        if (count > 0) {
            badge.text(count > 99 ? '99+' : count).show();
        } else {
            badge.hide();
        }
    }

    updateChatBadge(chatId) {
        const config = window.globalWebSocketConfig;
        const url = config?.urls?.getUnreadCount;

        if (!url) return;

        const chatItem = $(`.chat-item[data-chat-id="${chatId}"] .chat-badge`);

        $.get(url, { chat_id: chatId }, (response) => {
            if (response.success) {
                const count = response.unreadCount || 0;
                if (count > 0) {
                    if (chatItem.length) {
                        chatItem.text(count > 99 ? '99+' : count).show();
                    } else {
                        $(`.chat-item[data-chat-id="${chatId}"]`).append(
                            `<span class="chat-badge">${count > 99 ? '99+' : count}</span>`
                        );
                    }
                } else {
                    chatItem.hide();
                }
            }
        }).fail(() => {
            console.warn('Failed to update chat badge');
        });
    }

    updateChatUnreadBadge(chatId, count) {
        if (!this.isOnChatPage()) return;

        const chatItem = $(`.chat-item[data-chat-id="${chatId}"] .chat-badge`);

        if (count > 0) {
            if (chatItem.length) {
                chatItem.text(count > 99 ? '99+' : count).show();
            } else {
                $(`.chat-item[data-chat-id="${chatId}"]`).append(
                    `<span class="chat-badge">${count > 99 ? '99+' : count}</span>`
                );
            }
        } else {
            chatItem.hide();
        }
    }

    markChatAsRead(chatId) {
        const config = window.globalWebSocketConfig;
        const url = config?.urls?.markAsRead;

        if (!url) return;

        const data = { chat_id: chatId };

        $.post(url, data, (response) => {
            if (response.success) {
                console.log('Messages marked as read');
            }
        }).fail(() => {
            console.warn('Failed to mark messages as read');
        });
    }

    formatMessageDate(timestamp) {
        const date = new Date(timestamp * 1000);
        const month = date.getMonth() + 1;
        const day = date.getDate();
        const year = date.getFullYear().toString().substr(-2);
        const hours = date.getHours().toString().padStart(2, "0");
        const minutes = date.getMinutes().toString().padStart(2, "0");

        return `${month}/${day}/${year} ${hours}:${minutes}`;
    }

    scrollToBottom() {
        const container = $("#messages-container");
        if (container.length === 0) return;

        const scrollTop = container[0].scrollHeight - container.height();
        container.animate({ scrollTop: scrollTop }, 300);
    }

    startHeartbeat() {
        this.heartbeatInterval = setInterval(() => {
            if (this.isConnected) {
                const sent = this.send({ type: 'ping' });
                if (!sent) {
                    this.handleConnectionFailure();
                }
            }
        }, 30000);
    }

    stopHeartbeat() {
        if (this.heartbeatInterval) {
            clearInterval(this.heartbeatInterval);
            this.heartbeatInterval = null;
        }
    }

    scheduleReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;

            setTimeout(() => {
                console.log(`Attempting WebSocket reconnection (${this.reconnectAttempts}/${this.maxReconnectAttempts})`);
                this.connect();
            }, this.reconnectDelay);

            this.reconnectDelay = Math.min(this.reconnectDelay * 1.5, 30000);
        } else {
            this.connectionFailedPermanently = true;
            console.log('WebSocket connection permanently failed - chat will work without real-time updates');
        }
    }

    disconnect() {
        this.isConnected = false;
        this.stopHeartbeat();

        if (this.socket) {
            this.socket.close();
            this.socket = null;
        }
    }

    isOnChatPage() {
        return window.location.pathname.includes('/chat') ||
            window.location.pathname.includes('/my-chat');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    isWebSocketConnected() {
        return this.isConnected;
    }

    forceReconnect() {
        this.connectionFailedPermanently = false;
        this.reconnectAttempts = 0;
        this.reconnectDelay = 3000;
        this.connect();
    }
}

let chatWebSocket;

$(document).ready(function() {
    chatWebSocket = new ChatWebSocket();

    const config = window.globalWebSocketConfig;
    if (config && typeof config.isEmployee !== 'undefined') {
        chatWebSocket.isEmployee = config.isEmployee;
    }

    if (config) {
        chatWebSocket.connect();

        const chatId = config.selectedChatId || config.chatId;
        if (chatId) {
            chatWebSocket.joinChat(chatId);
        }
    } else {
        console.warn('No WebSocket configuration available - real-time notifications disabled');
    }

    $(document).on('click', '.chat-item', function() {
        const chatId = $(this).data('chat-id');
        if (chatId && chatWebSocket) {
            chatWebSocket.joinChat(chatId);
            $(this).find('.chat-badge').hide();
        }
    });

    $(window).on('beforeunload', function() {
        if (chatWebSocket) {
            chatWebSocket.disconnect();
        }
    });

    if (typeof YII_DEBUG !== 'undefined' && YII_DEBUG) {
        window.chatDebug = {
            reconnect: () => chatWebSocket.forceReconnect(),
            status: () => {
                const config = window.globalWebSocketConfig;
                console.log('WebSocket Status:', {
                    connected: chatWebSocket.isWebSocketConnected(),
                    attempts: chatWebSocket.reconnectAttempts,
                    permanentlyFailed: chatWebSocket.connectionFailedPermanently,
                    currentChatId: chatWebSocket.currentChatId,
                    isEmployee: chatWebSocket.isEmployee,
                    isGlobalMode: chatWebSocket.isGlobalMode,
                    websocketUrl: config?.websocketUrl,
                    tokenUrl: config?.urls?.generateWebsocketToken
                });
            },
            disconnect: () => chatWebSocket.disconnect(),
            config: () => {
                console.log('Global Config:', window.globalWebSocketConfig);
            }
        };
    }
});