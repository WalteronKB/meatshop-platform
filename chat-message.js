
    if (window.chatInitialized !== true) {
        window.chatInitialized = true;
        
        document.addEventListener('DOMContentLoaded', function () {
            const chatsContainer = document.querySelector('.chats');
            const chatTextField = document.querySelector('.chat-textfield');
            const sendButton = document.querySelector('.send-button');
            const messageForm = document.querySelector('.usermessagebox');
            const chatShopInput = messageForm ? messageForm.querySelector('input[name="chat-shop-id"]') : null;
            const chatShopId = chatShopInput ? parseInt(chatShopInput.value || '0', 10) : 0;
            const chatProductInput = messageForm ? messageForm.querySelector('input[name="chat-product-id"]') : null;
            const chatProductId = chatProductInput ? parseInt(chatProductInput.value || '0', 10) : 0;
            
            let lastMessageTimestamp = Math.floor(Date.now() / 1000) - 300; // Start by checking last 5 minutes
            
            // Track processed messages by ID to prevent duplicates
            let processedMessageIds = new Set();
            
            // Initialize with any message IDs already on the page
            if (typeof initialMessageIds !== 'undefined') {
                initialMessageIds.forEach(id => {
                    processedMessageIds.add(id);
                });
                console.log('Initialized with', processedMessageIds.size, 'existing message IDs');
            }
            
            function scrollChatToBottom() {
                // Try multiple selectors to find the correct chat container
                const chatContainer = document.querySelector('.chatbox') || document.querySelector('.chats');
                
                if (chatContainer) {
                    // Use setTimeout to ensure DOM updates are complete
                    setTimeout(() => {
                        chatContainer.scrollTop = chatContainer.scrollHeight;
                        // Also try smooth scrolling as backup
                        chatContainer.scrollTo({
                            top: chatContainer.scrollHeight,
                            behavior: 'smooth'
                        });
                    }, 100);
                } else {
                    console.error('Chat container not found for scrolling');
                }
            }
            
            function playNotificationSound() {
                try {
                    const audio = new Audio("data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU");
                    audio.volume = 0.2;
                    audio.play().catch(err => console.log('Audio play prevented:', err));
                } catch(e) {
                    console.log('Sound notification error:', e);
                }
            }
            
            // Add a message to the chat UI
            function addMessageToChat(messageHTML, messageId) {
                if (!processedMessageIds.has(messageId)) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = messageHTML.trim();
                    
                    // Append each child node individually to ensure proper DOM structure
                    while (tempDiv.firstChild) {
                        chatsContainer.appendChild(tempDiv.firstChild);
                    }
                    
                    // Mark this message as processed
                    processedMessageIds.add(messageId);
                    return true;
                }
                return false;
            }
            
            // Handle sending a new message
            function sendMessage(event) {
                event.preventDefault();
                
                const messageText = chatTextField.value.trim();
                if (!messageText) return;
                
                // Disable the form temporarily to prevent double-sending
                chatTextField.disabled = true;
                sendButton.disabled = true;
                
                console.log("Sending message:", messageText);
                
                // Create a new FormData instance instead of using the form directly
                const formData = new FormData();
                formData.append('chat-text-field', messageText);
                if (chatShopId > 0) {
                    formData.append('chat-shop-id', chatShopId);
                }
                if (chatProductId > 0) {
                    formData.append('chat-product-id', chatProductId);
                }
                // We don't need to append chat-text-button since we modified the PHP file
                
                fetch('process_chat.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log("Raw response:", response);
                    return response.text().then(text => {
                        console.log("Response text:", text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error("Failed to parse JSON:", e);
                            throw new Error("Invalid JSON response");
                        }
                    });
                })
                .then(data => {
                    console.log("Parsed data:", data);
                    
                    if (data.success) {
                        // Clear the input field
                        chatTextField.value = '';
                        
                        // Add the message to the UI immediately (don't wait for polling)
                        const messageHTML = `
                            <div class='chat-message'>
                                <p class='message'>${data.message}</p>
                            </div>
                            <span class='chat-time message-time white-text'>${data.time}</span>
                        `;
                        
                        // Use the real message ID if available, otherwise create a temp ID
                        const messageId = data.id ? data.id : 'temp-' + Date.now();
                        addMessageToChat(messageHTML, messageId);
                        scrollChatToBottom();
                        
                        // Trigger immediate check for new messages to get the real ID
                        setTimeout(checkForNewMessages, 500);
                    } else {
                        console.error('Failed to send message:', data.error || 'Unknown error');
                        alert('Failed to send message. Please try again or refresh the page.');
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    alert('Error sending message. Please try again or refresh the page.');
                })
                .finally(() => {
                    // Re-enable the form
                    chatTextField.disabled = false;
                    sendButton.disabled = false;
                    chatTextField.focus();
                });
            }
            
            function checkForNewMessages() {
                console.log('Checking for new messages, last timestamp:', lastMessageTimestamp);
                
                // Add cache-busting parameter to prevent caching
                const shopParam = chatShopId > 0 ? `&shop_id=${chatShopId}` : '';
                fetch(`check_new_messages.php?last_time=${lastMessageTimestamp}${shopParam}&t=${Date.now()}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Message check response:', data);
                        
                        if (data.success && data.messages && data.messages.length > 0) {
                            let addedMessages = 0;
                            
                            // Process messages in order they were received
                            data.messages.forEach(message => {
                                if (addMessageToChat(message.html, message.id)) {
                                    addedMessages++;
                                    console.log('Added message ID:', message.id, 'Type:', message.type);
                                }
                            });
                            
                            // Update timestamp to latest message timestamp for next poll
                            if (data.latest_timestamp > lastMessageTimestamp) {
                                lastMessageTimestamp = data.latest_timestamp;
                                console.log('Updated timestamp to:', lastMessageTimestamp);
                            }
                            
                            if (addedMessages > 0) {
                                console.log(`Added ${addedMessages} new messages`);
                                playNotificationSound();
                                scrollChatToBottom();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error checking for messages:', error);
                    });
            }
            
            // Initialize chat and start polling if chat container exists
            if (chatsContainer) {
                // Initial scroll to bottom on page load
                setTimeout(scrollChatToBottom, 200);
                
                // Add event listener for form submission
                if (messageForm) {
                    messageForm.addEventListener('submit', sendMessage);
                    console.log('Added submit event listener to chat form');
                } else {
                    console.error('Chat form not found!');
                }
                
                // Start checking for messages
                checkForNewMessages();
                
                // Set interval for checking (every 3 seconds)
                window.chatInterval = setInterval(checkForNewMessages, 3000);
            } else {
                console.error('Chat container not found!');
            }
        });
    } else {
        console.log('Chat already initialized, preventing duplicate initialization');
    }
  