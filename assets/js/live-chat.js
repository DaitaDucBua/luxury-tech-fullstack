/**
 * Live Chat Widget
 */

(function() {
    'use strict';
    
    let conversationId = null;
    let lastMessageId = 0;
    let pollInterval = null;
    
    // Initialize chat widget
    function init() {
        createChatWidget();
        bindEvents();
    }
    
    // Create chat widget HTML
    function createChatWidget() {
        const html = `
            <!-- Chat Button -->
            <button class="chat-button" id="chatButton">
                <i class="fas fa-comments"></i>
                <span class="badge" id="chatBadge" style="display: none;">0</span>
            </button>
            
            <!-- Chat Window -->
            <div class="chat-window" id="chatWindow">
                <div class="chat-header">
                    <div class="chat-header-info">
                        <h5>LuxuryTech Support</h5>
                        <p>Chúng tôi luôn sẵn sàng hỗ trợ bạn</p>
                    </div>
                    <div class="chat-header-actions">
                        <button id="chatMinimize"><i class="fas fa-minus"></i></button>
                        <button id="chatClose"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                
                <div class="chat-messages" id="chatMessages">
                    <!-- Messages will be loaded here -->
                </div>
                
                <div class="chat-input">
                    <form class="chat-input-form" id="chatForm">
                        <div class="chat-input-wrapper">
                            <input type="text" 
                                   class="chat-input-field" 
                                   id="chatInput" 
                                   placeholder="Nhập tin nhắn..."
                                   autocomplete="off">
                            <input type="file" 
                                   id="chatFileInput" 
                                   accept="image/*" 
                                   style="display: none;">
                            <button type="button" class="chat-input-attach" id="chatAttach">
                                <i class="fas fa-paperclip"></i>
                            </button>
                        </div>
                        <button type="submit" class="chat-input-send">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        `;
        
        $('body').append(html);
    }
    
    // Bind events
    function bindEvents() {
        // Toggle chat window
        $('#chatButton').on('click', toggleChat);
        $('#chatMinimize').on('click', toggleChat);
        $('#chatClose').on('click', closeChat);
        
        // Send message
        $('#chatForm').on('submit', sendMessage);
        
        // Attach file
        $('#chatAttach').on('click', () => $('#chatFileInput').click());
        $('#chatFileInput').on('change', uploadImage);
    }
    
    // Toggle chat window
    function toggleChat() {
        const $window = $('#chatWindow');
        
        if ($window.hasClass('show')) {
            $window.removeClass('show');
            stopPolling();
        } else {
            $window.addClass('show');
            loadConversation();
        }
    }
    
    // Close chat
    function closeChat() {
        if (confirm('Bạn có muốn kết thúc cuộc trò chuyện?')) {
            $.post('ajax/chat.php', {
                action: 'close_conversation',
                conversation_id: conversationId
            }, function() {
                $('#chatWindow').removeClass('show');
                conversationId = null;
                lastMessageId = 0;
                stopPolling();
            });
        }
    }
    
    // Load conversation
    function loadConversation() {
        const url = (typeof SITE_URL !== 'undefined' && SITE_URL ? SITE_URL + '/' : '') + 'ajax/chat.php';
        console.log('Live Chat: Loading conversation from', url);
        
        $.get(url, { action: 'get_conversation' })
            .done(function(response) {
                console.log('Live Chat: Conversation response', response);
                if (response.success) {
                    conversationId = response.conversation.id;
                    console.log('Live Chat: Conversation ID =', conversationId);
                    loadMessages();
                    startPolling();
                } else {
                    console.error('Live Chat: Failed to load conversation', response);
                    $('#chatMessages').html('<div class="chat-error">Không thể tải cuộc trò chuyện. Vui lòng thử lại.</div>');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Live Chat: AJAX error', { status, error, responseText: xhr.responseText });
                $('#chatMessages').html('<div class="chat-error">Lỗi kết nối: ' + error + '</div>');
            });
    }
    
    // Load messages
    function loadMessages(append = false) {
        if (!conversationId) {
            console.warn('Live Chat: No conversation ID');
            return $.Deferred().reject();
        }
        
        const url = (typeof SITE_URL !== 'undefined' && SITE_URL ? SITE_URL + '/' : '') + 'ajax/chat.php';
        console.log('Live Chat: Loading messages', { conversationId, lastMessageId: append ? lastMessageId : 0 });
        
        return $.get(url, {
            action: 'get_messages',
            conversation_id: conversationId,
            last_message_id: append ? lastMessageId : 0
        })
            .done(function(response) {
                console.log('Live Chat: Messages response', response);
                if (response.success) {
                    if (!append) {
                        $('#chatMessages').empty();
                        lastMessageId = 0; // Reset khi load lại toàn bộ
                    }
                    
                    if (response.messages && response.messages.length > 0) {
                        // Lọc chỉ lấy tin nhắn mới thực sự
                        const newMessages = response.messages.filter(msg => {
                            // Chỉ append tin nhắn có id > lastMessageId và chưa có trong DOM
                            if (msg.id <= lastMessageId) {
                                return false;
                            }
                            // Kiểm tra xem đã có trong DOM chưa (tránh duplicate)
                            return $('#chatMessages').find(`[data-message-id="${msg.id}"]`).length === 0;
                        });
                        
                        if (newMessages.length > 0) {
                            console.log('Live Chat: Adding', newMessages.length, 'new messages');
                            newMessages.forEach((msg, index) => {
                                setTimeout(() => {
                                    appendMessage(msg, true);
                                }, index * 50); // Stagger animation
                                if (msg.id > lastMessageId) {
                                    lastMessageId = msg.id;
                                }
                            });
                        } else {
                            console.log('Live Chat: No new messages to add');
                        }
                    }
                } else {
                    console.error('Live Chat: Failed to load messages', response);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Live Chat: Load messages error', { status, error, responseText: xhr.responseText });
            });
    }
    
    // Append message to chat
    function appendMessage(msg, isNew = false) {
        // Kiểm tra xem tin nhắn đã tồn tại chưa (tránh duplicate)
        const msgId = msg.id || msg.temp_id;
        if (msgId && $('#chatMessages').find(`[data-message-id="${msgId}"]`).length > 0) {
            console.log('Live Chat: Message already exists, skipping', msgId);
            return;
        }
        
        const time = new Date(msg.created_at).toLocaleTimeString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Lấy avatar dựa trên sender_type (không có sender_name trong database)
        let avatar = '?';
        if (msg.sender_type === 'bot') {
            avatar = 'B';
        } else if (msg.sender_type === 'customer' || msg.sender_type === 'user') {
            avatar = 'K'; // Khách
        } else if (msg.sender_type === 'admin') {
            avatar = 'A';
        }
        
        let content = '';
        if (msg.attachment_url) {
            content = `<img src="${msg.attachment_url}" alt="Image" style="max-width: 200px; border-radius: 8px;">`;
        } else {
            content = escapeHtml(msg.message);
        }
        
        // Thêm class 'user' cho tin nhắn của customer để căn bên phải
        let messageClass = msg.sender_type;
        if (msg.sender_type === 'customer' || msg.sender_type === 'user') {
            messageClass = 'user customer'; // Thêm cả 2 class để chắc chắn
        }
        
        console.log('Live Chat: Message class =', messageClass, 'sender_type =', msg.sender_type);
        
        const newMessageClass = isNew ? 'new-message' : '';
        const html = `
            <div class="chat-message ${messageClass} ${newMessageClass}" data-message-id="${msgId || ''}">
                <div class="chat-message-avatar">${avatar}</div>
                <div class="chat-message-content">
                    <div class="chat-message-bubble">${content}</div>
                    <div class="chat-message-time">${time}</div>
                </div>
            </div>
        `;
        
        const $message = $(html);
        $('#chatMessages').append($message);
        
        // Scroll to bottom với smooth animation
        setTimeout(() => {
            scrollToBottom();
        }, 100);
        
        // Remove new-message class sau animation
        if (isNew) {
            setTimeout(() => {
                $message.removeClass('new-message');
            }, 1000);
        }
    }
    
    // Send message
    function sendMessage(e) {
        e.preventDefault();
        
        const $input = $('#chatInput');
        const message = $input.val().trim();
        
        if (!message) {
            console.warn('Live Chat: Empty message');
            return;
        }
        
        if (!conversationId) {
            console.error('Live Chat: No conversation ID');
            alert('Vui lòng đợi cuộc trò chuyện được tải...');
            return;
        }
        
        const url = (typeof SITE_URL !== 'undefined' && SITE_URL ? SITE_URL + '/' : '') + 'ajax/chat.php';
        console.log('Live Chat: Sending message', { conversationId, message, url });
        
        // Clear input ngay lập tức để user có thể nhập tiếp
        $input.val('').focus();
        
        // Disable input while sending
        $input.prop('disabled', true);
        const $submitBtn = $('#chatForm button[type="submit"]');
        $submitBtn.prop('disabled', true);
        
        // Hiển thị tin nhắn đang gửi (optimistic UI)
        const tempId = 'temp-' + Date.now();
        const tempMessage = {
            id: null, // Không có id thật
            temp_id: tempId, // Dùng temp_id để track
            sender_type: 'user',
            message: message,
            created_at: new Date().toISOString()
        };
        appendMessage(tempMessage, true);
        const $tempMessage = $('#chatMessages').find(`[data-message-id="${tempId}"]`);
        $tempMessage.addClass('sending');
        
        $.post(url, {
            action: 'send_message',
            conversation_id: conversationId,
            message: message
        })
            .done(function(response) {
                console.log('Live Chat: Send message response', response);
                if (response.success) {
                    // Đảm bảo input đã được clear (phòng trường hợp user nhập thêm)
                    $input.val('');
                    // Xóa tin nhắn tạm
                    $tempMessage.remove();
                    
                    // Nếu response có tin nhắn mới, append trực tiếp thay vì reload
                    if (response.message && response.message.id) {
                        // Cập nhật lastMessageId để tránh duplicate
                        if (response.message.id > lastMessageId) {
                            lastMessageId = response.message.id;
                        }
                        // Append tin nhắn mới từ server (có cả bot reply nếu có)
                        appendMessage(response.message, true);
                        
                        // Nếu có bot reply, append luôn
                        if (response.bot_reply && response.bot_reply.id) {
                            setTimeout(() => {
                                appendMessage(response.bot_reply, true);
                                if (response.bot_reply.id > lastMessageId) {
                                    lastMessageId = response.bot_reply.id;
                                }
                            }, 500);
                        }
                    } else {
                        // Fallback: reload messages nếu không có trong response
                        loadMessages(true);
                    }
                } else {
                    console.error('Live Chat: Failed to send message', response);
                    $tempMessage.remove();
                    // Đảm bảo input vẫn được clear dù có lỗi
                    $input.val('');
                    alert('Không thể gửi tin nhắn: ' + (response.message || 'Lỗi không xác định'));
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Live Chat: Send message error', { status, error, responseText: xhr.responseText });
                $tempMessage.remove();
                // Đảm bảo input vẫn được clear dù có lỗi
                $input.val('');
                alert('Lỗi kết nối: ' + error + '\nVui lòng kiểm tra console để xem chi tiết.');
            })
            .always(function() {
                $input.prop('disabled', false);
                $submitBtn.prop('disabled', false);
                // Đảm bảo input được clear và focus lại
                $input.val('').focus();
            });
    }
    
    // Upload image
    function uploadImage() {
        const file = this.files[0];
        if (!file || !conversationId) return;
        
        const formData = new FormData();
        formData.append('action', 'upload_image');
        formData.append('conversation_id', conversationId);
        formData.append('image', file);
        
        $.ajax({
            url: 'ajax/chat.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    loadMessages(true);
                }
            }
        });
        
        // Reset input
        $('#chatFileInput').val('');
    }
    
    // Start polling for new messages
    function startPolling() {
        stopPolling(); // Clear existing interval
        let isPolling = false; // Flag để tránh concurrent requests
        pollInterval = setInterval(() => {
            // Chỉ poll nếu không có request nào đang chạy và chat window đang mở
            if (!isPolling && conversationId && $('#chatWindow').hasClass('show')) {
                isPolling = true;
                loadMessages(true).always(() => {
                    isPolling = false;
                });
            }
        }, 5000); // Tăng lên 5 giây để giảm tải và tránh duplicate
    }
    
    // Stop polling
    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }
    
    // Scroll to bottom
    function scrollToBottom() {
        const $messages = $('#chatMessages');
        $messages.animate({
            scrollTop: $messages[0].scrollHeight
        }, 300, 'swing');
    }
    
    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Initialize on document ready
    $(document).ready(init);
    
    // Export API
    window.LiveChat = {
        open: function() {
            if (!$('#chatWindow').hasClass('show')) {
                toggleChat();
            }
        },
        close: closeChat
    };
})();

