const myModal = document.getElementById('myModal')
        const myInput = document.getElementById('myInput')

        if (myModal && myInput) {
            myModal.addEventListener('shown.bs.modal', () => {
                myInput.focus()
            })
        }
        
        function toggleNavbar() {
            const navbar = document.querySelector(".collapsed-navbar");
            const button = navbar.querySelector(".button");

            if (navbar.classList.contains("hide")) {
                navbar.classList.remove("hide");
                navbar.classList.add("show");
            } else {
                navbar.classList.remove("show");
                navbar.classList.add("hide");
            }
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

        function sendChat() {
            const chatmain = document.querySelector('.chatbox');
            const chatbox = document.querySelector('.chats');
            const messageInput = document.querySelector('.chat-textfield');
            const message = messageInput.value.trim();
            
            if (message) {
            const now = new Date();
            // Format time with hours, minutes, and AM/PM
            const hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'pm' : 'am';
            const displayHours = hours % 12 || 12; // Convert 0 to 12 for 12 AM
            const time = `${displayHours}:${minutes} ${ampm}`;

            const chatMessage = document.createElement('div');
            chatMessage.classList.add('chat-message');
            chatMessage.innerHTML = `<p class="message">${message}</p>`;
            chatbox.appendChild(chatMessage);
            const messageTime = document.createElement('span');
            messageTime.classList.add('chat-time', 'message-time', 'white-text');
            messageTime.innerText = `Sent ${time}`;
            chatbox.appendChild(messageTime);

            messageInput.value = ''; // Clear the input field

            const chatReply = document.createElement('div');
            chatReply.classList.add('chat-reply');
            chatReply.innerHTML = `<p class="message">Feel free to ask any questions.</p>`;
            chatbox.appendChild(chatReply);
            const replyTime = document.createElement('span');
            replyTime.classList.add('chat-time', 'reply-time', 'white-text');
            replyTime.innerText = `Sent ${time}`;
            chatbox.appendChild(replyTime);
            
            // Use the improved scroll function
            scrollChatToBottom();
            }
        }


        function add_quantity(){
            let quantity = parseInt(document.getElementById("quantity").innerHTML);    
            const maxQuantity = 99; // Set your maximum order quantity
            
            if (quantity < maxQuantity) {
                quantity++;
                document.getElementById("quantity").innerHTML = quantity;
            }
        }
        
        function sub_quantity(){
            let quantity = parseInt(document.getElementById("quantity").innerHTML);    
            if (quantity > 1) { // Don't allow going below 1 item
                quantity--;
                document.getElementById("quantity").innerHTML = quantity;
            }
        }
// Replace the existing event listener code with this improved version
document.addEventListener('DOMContentLoaded', function() {
    // Clear any existing modal instances to prevent conflicts
    const existingModals = document.querySelectorAll('.modal');
    existingModals.forEach(modal => {
        const existingInstance = bootstrap.Modal.getInstance(modal);
        if (existingInstance) {
            existingInstance.dispose();
        }
    });

    // Initialize all modals properly
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modalElement => {
        // Ensure modal is properly initialized
        modalElement.addEventListener('show.bs.modal', function() {
            // Fix z-index issues
            this.style.zIndex = '1055';
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.style.zIndex = '1050';
            }
        });

        // Fix pointer events after modal is shown
        modalElement.addEventListener('shown.bs.modal', function() {
            this.style.pointerEvents = 'auto';
            const modalContent = this.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.pointerEvents = 'auto';
            }
        });
    });

    // Specifically target the button that opens the modal
    const buyNowBtn = document.querySelector('.BuyNow-btn[data-bs-toggle="modal"]');
    
    console.log("Buy Now button found:", buyNowBtn); // Debug log
    
    if (buyNowBtn) {
      buyNowBtn.addEventListener('click', function(e) {
        console.log("Buy Now button clicked"); // Debug log
        
        // Prevent any default behavior that might interfere
        e.preventDefault();
        e.stopPropagation();
        
        // Add a small delay to ensure the modal has time to open
        setTimeout(function() {
          // Get the current quantity value from the product page
          const quantityElement = document.getElementById('quantity');
          const modalQuantityElement = document.getElementById('quantity_modal');
          
          if (quantityElement && modalQuantityElement) {
            modalQuantityElement.textContent = quantityElement.textContent;
            console.log("Quantity updated in modal:", quantityElement.textContent);
          }
          
          // Update product info in modal
          const productTitle = document.querySelector('.indiv-product-title');
          const modalProductTitle = document.querySelector('#buynowmodel h3');
          
          if (productTitle && modalProductTitle) {
            modalProductTitle.textContent = productTitle.textContent;
          }
          
          // Update price in modal
          const productPrice = document.querySelector('.product-indiv-price');
          const modalProductPrice = document.querySelector('#price_modal');
          
          if (productPrice && modalProductPrice) {
            modalProductPrice.textContent = productPrice.textContent;
          }
        }, 100);
      });
    }

    // Location selection functionality
    const locationModal = document.getElementById('locationModal');
    if (locationModal) {
        const locationOptions = document.querySelectorAll('.location-option');
        const confirmLocationBtn = document.getElementById('confirmLocationBtn');
        const locationDisplay = document.getElementById('location_modal');
        let selectedLocation = null;

        locationOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Remove active class from all options
                locationOptions.forEach(opt => opt.classList.remove('active'));
                
                // Add active class to clicked option
                this.classList.add('active');
                
                // Get the location data
                selectedLocation = this.getAttribute('data-location');
                
                // Enable confirm button
                if (confirmLocationBtn) {
                    confirmLocationBtn.disabled = false;
                }
                
                console.log('Location selected:', selectedLocation);
            });
        });

        if (confirmLocationBtn) {
            confirmLocationBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (selectedLocation && locationDisplay) {
                    locationDisplay.textContent = selectedLocation;
                    
                    // Close location modal
                    const locationModalInstance = bootstrap.Modal.getInstance(locationModal);
                    if (locationModalInstance) {
                        locationModalInstance.hide();
                    }
                    
                    console.log('Location confirmed:', selectedLocation);
                }
            });
        }
    }

    // GCash modal fixes
    const gcashModal = document.getElementById('gcashQRModal');
    if (gcashModal) {
        // Ensure GCash modal appears on top
        gcashModal.addEventListener('show.bs.modal', function() {
            this.style.zIndex = '1065';
            // Hide other modals when GCash modal opens
            const otherModals = document.querySelectorAll('.modal:not(#gcashQRModal)');
            otherModals.forEach(modal => {
                const instance = bootstrap.Modal.getInstance(modal);
                if (instance) {
                    instance.hide();
                }
            });
        });

        // Handle GCash payment confirmation
        const confirmGCashBtn = document.getElementById('confirmGCashPayment');
        if (confirmGCashBtn) {
            confirmGCashBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const transactionId = document.getElementById('gcashTransactionId').value;
                if (!transactionId || transactionId.trim() === '') {
                    alert('Please enter the GCash transaction ID');
                    return;
                }
                
                // Call the existing processGCashOrder function
                if (typeof processGCashOrder === 'function') {
                    processGCashOrder();
                } else {
                    console.error('processGCashOrder function not found');
                }
            });
        }
    }

    // Order confirmation button
    const confirmOrderBtn = document.getElementById('confirmOrderBtn');
    if (confirmOrderBtn && !confirmOrderBtn.hasAttribute('data-inline-order-handler')) {
        confirmOrderBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Confirm Order button clicked');
            
            // Get order data
            const productIdAttr = this.getAttribute('data-product-id');
            const productIdFromUrl = new URLSearchParams(window.location.search).get('prod_id');
            const productId = productIdAttr || productIdFromUrl;
            const quantity = parseInt(document.getElementById('quantity_modal').textContent);
            const location = document.getElementById('location_modal').textContent;
            const paymentMethod = document.querySelector('input[name="payment-method"]:checked');

            // Validate data
            if (!location || location.trim() === '' || location === 'No location selected') {
                alert('Please select a delivery location.');
                return;
            }

            if (quantity <= 0) {
                alert('Please select a valid quantity.');
                return;
            }

            if (!paymentMethod) {
                alert('Please select a payment method.');
                return;
            }

            if (!productId || Number(productId) <= 0) {
                alert('Invalid product ID. Please refresh the page and try again.');
                return;
            }

            // Check if GCash is selected
            if (paymentMethod.value === 'gcash') {
                // Show GCash QR modal instead of processing order immediately
                if (typeof showGCashModal === 'function') {
                    showGCashModal(productId, quantity, location);
                } else {
                    console.error('showGCashModal function not found');
                }
            } else {
                // Process order directly for Cash on Delivery
                if (typeof processOrder === 'function') {
                    processOrder(productId, quantity, location, paymentMethod.value);
                } else {
                    console.error('processOrder function not found');
                }
            }
        });
    }
});