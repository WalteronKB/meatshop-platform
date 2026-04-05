<?php
	include '../connection.php';
?>
<?php
	session_start();
	if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
		header("Location: ../mrbloginpage.php");
		exit;
	}
    $current_user_role = $_SESSION['user_type'] ?? '';
    $account_page = ($current_user_role === 'admin' || $current_user_role === 'super_admin') ? 'account-admin.php' : 'account-staff.php';
    $is_rider = $current_user_role === 'rider';
    if (!in_array($current_user_role, ['super_admin', 'admin', 'rider'], true)) {
        if ($current_user_role === 'butcher') {
            header("Location: products-admin.php");
        } elseif ($current_user_role === 'cashier') {
            header("Location: orders-admin.php");
        } elseif ($current_user_role === 'finance') {
            header("Location: finances-admin.php");
        } else {
            header("Location: ../landpage.php");
        }
        exit;
    }
    $current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    $is_super_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'super_admin';
    $current_admin_shop_id = null;
    if ($current_user_id > 0) {
        $shop_lookup_query = "SELECT shop_id FROM mrb_users WHERE user_id = {$current_user_id} LIMIT 1";
        $shop_lookup_result = mysqli_query($conn, $shop_lookup_query);
        if ($shop_lookup_result && mysqli_num_rows($shop_lookup_result) > 0) {
            $shop_lookup_row = mysqli_fetch_assoc($shop_lookup_result);
            $current_admin_shop_id = isset($shop_lookup_row['shop_id']) ? (int)$shop_lookup_row['shop_id'] : null;
        }

        if (($current_admin_shop_id === null || $current_admin_shop_id <= 0) && !$is_super_admin) {
            $fallback_shop_query = "SELECT approved_shop_id FROM approved_shops WHERE user_id = {$current_user_id} ORDER BY (shop_status = 'active') DESC, updated_at DESC, approved_shop_id DESC LIMIT 1";
            $fallback_shop_result = mysqli_query($conn, $fallback_shop_query);
            if ($fallback_shop_result && mysqli_num_rows($fallback_shop_result) > 0) {
                $fallback_shop_row = mysqli_fetch_assoc($fallback_shop_result);
                $current_admin_shop_id = isset($fallback_shop_row['approved_shop_id']) ? (int)$fallback_shop_row['approved_shop_id'] : null;
            }
        }
    }

    $message_shop_scope_condition = "";
    $message_shop_scope_alias_condition = "";
    $order_scope_condition = "";
    if (!$is_super_admin) {
        if ($current_admin_shop_id !== null && $current_admin_shop_id > 0) {
            $message_shop_scope_condition = " AND shop_id = {$current_admin_shop_id}";
            $message_shop_scope_alias_condition = " AND m.shop_id = {$current_admin_shop_id}";
            $order_scope_condition = " AND shop_id = {$current_admin_shop_id}";
        } else {
            $message_shop_scope_condition = " AND 1 = 0";
            $message_shop_scope_alias_condition = " AND 1 = 0";
            $order_scope_condition = " AND 1 = 0";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">

	<link rel="stylesheet" href="admin.css">
	<style>
		.article-numbers {
			display: grid;
			grid-template-columns: repeat(6, 1fr);
			grid-template-rows: repeat(5, 1fr);
			grid-column-gap: 10px;
			grid-row-gap: 10px;
			}

			.div1 { grid-area: 1 / 1 / 4 / 5; }
			.div2 { grid-area: 1 / 5 / 4 / 7; }
			.div3 { grid-area: 4 / 1 / 6 / 3; }
			.div4 { grid-area: 4 / 3 / 6 / 5; }
			.div5 { grid-area: 4 / 5 / 6 / 7; }

			.modal-backdrop {
			z-index: 1040 !important;
			}

			.modal-backdrop {
			z-index: 1040 !important;
			}

			.modal {
			z-index: 1050 !important;
			}

			#sidebar {
			z-index: 1030 !important; /* Lower than modal z-index */
			}

			/* Additional styling for modals to ensure they're visible */
			.modal-content {
			box-shadow: 0 5px 15px rgba(0,0,0,.5);
			border: 1px solid rgba(0,0,0,.2);
			z-index: 1050 !important;
			}

			/* Enhanced User Account Display Styling */
			.user-account-btn {
				display: flex;
				align-items: center;
				padding: 15px 20px;
				margin: 8px 0;
				border-radius: 12px;
				background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
				border: 1px solid #dee2e6;
				transition: all 0.3s ease;
				box-shadow: 0 2px 4px rgba(0,0,0,0.05);
			}

			.user-account-btn:hover {
				background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
				transform: translateY(-2px);
				box-shadow: 0 4px 12px rgba(0,0,0,0.15);
				border-color: #6c757d;
			}

			.user-account-btn:active {
				transform: translateY(0);
				box-shadow: 0 2px 6px rgba(0,0,0,0.1);
			}

			.user-avatar {
				width: 50px;
				height: 50px;
				border-radius: 50%;
				object-fit: cover;
				border: 3px solid #fff;
				box-shadow: 0 2px 8px rgba(0,0,0,0.1);
				margin-right: 15px;
				flex-shrink: 0;
			}

			.user-info {
				flex: 1;
				display: flex;
				flex-direction: column;
				min-width: 0;
			}

			.user-name {
				font-weight: 600;
				font-size: 1.1rem;
				color: #2c3e50;
				margin-bottom: 4px;
				line-height: 1.2;
			}

			.last-message {
				color: #6c757d;
				font-size: 0.9rem;
				line-height: 1.3;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
				max-width: 250px;
			}

			.chat-status-indicator {
				width: 12px;
				height: 12px;
				border-radius: 50%;
				background-color: #28a745;
				border: 2px solid #fff;
				position: absolute;
				bottom: 2px;
				right: 2px;
			}

			.user-avatar-container {
				position: relative;
				display: inline-block;
			}

			/* Table styling improvements */
			.order table {
				border-collapse: separate;
				border-spacing: 0;
			}

			.order table tbody tr td {
				padding: 0;
				border: none;
			}

			.order .head {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: white;
				padding: 20px;
				border-radius: 12px 12px 0 0;
				margin-bottom: 0;
			}

			.order .head h3 {
				margin: 0;
				font-weight: 600;
				font-size: 1.3rem;
			}

			.table-data .order {
				border-radius: 12px;
				overflow: hidden;
				box-shadow: 0 4px 20px rgba(0,0,0,0.1);
			}

			/* Message preview styling */
			.last-message.no-messages {
				font-style: italic;
				color: #adb5bd;
			}

			/* Responsive adjustments */
			@media (max-width: 768px) {
				.user-account-btn {
					padding: 12px 15px;
				}
				
				.user-avatar {
					width: 40px;
					height: 40px;
					margin-right: 12px;
				}
				
				.user-name {
					font-size: 1rem;
				}
				
				.last-message {
					font-size: 0.85rem;
					max-width: 180px;
				}
			}

	</style>
</head>
<body>

	
<section id="sidebar" class="sidebar">
        <a href="#" class="brand">
                    <i class='bx bxs-restaurant'></i>
                    <span class="text">Meat Shop</span>
        </a>
		<ul class="side-menu top" style="padding: 0px;">
        <?php if ($is_rider): ?>
            <li>
                <a href="orders-admin.php">
                <i class='bx bxs-package'></i>
                <?php 
                    $all_unseen_query = "SELECT COUNT(*) AS all_unseen FROM mrb_orders WHERE seen_byadmin = 'false'{$order_scope_condition}";
                    $all_unseen_result = mysqli_query($conn, $all_unseen_query);
                    $all_unseen_count = mysqli_fetch_assoc($all_unseen_result)['all_unseen'];
                ?>
                <span class="text">Orders<?php
                    if($all_unseen_count > 0) {
                        echo "<span class='ms-2 badge bg-danger'>$all_unseen_count</span>";
                    }
                ?></span>
                </a>
            </li>
            <li class="active">
                <a href="chat-admin.php">
                <i class='bx bxs-chat'></i>
                <?php 
                    $messages_unseen_query = "SELECT COUNT(*) AS messages_unseen FROM mrb_messages m WHERE m.message_type = 'user-chat' AND m.seen_byadmin = 0{$message_shop_scope_alias_condition}";
                    $messages_unseen_result = mysqli_query($conn, $messages_unseen_query);
                    $messages_unseen_count = mysqli_fetch_assoc($messages_unseen_result)['messages_unseen'];
                ?>
                <span class="text">Messages<?php
                    if($messages_unseen_count > 0) {
                        echo "<span class='ms-2 badge bg-danger'>$messages_unseen_count</span>";
                    }
                ?></span>
                </a>
            </li>
        <?php else: ?>
        <li>
				<a href="analytics-admin.php">
				<i class='bx bxs-bar-chart-alt-2'></i>
				<span class="text">Analytics</span>
				</a>
			</li>
			<li>
				<a href="products-admin.php">
				<i class='bx bxs-cart'></i>
				<span class="text">Products</span>
				</a>
			</li>
			<li >
				<a href="orders-admin.php">
				<i class='bx bxs-package'></i>
				<?php 
					// Get unseen counts for each status
                    $all_unseen_query = "SELECT COUNT(*) AS all_unseen FROM mrb_orders WHERE seen_byadmin = 'false'{$order_scope_condition}";
					$all_unseen_result = mysqli_query($conn, $all_unseen_query);
					$all_unseen_count = mysqli_fetch_assoc($all_unseen_result)['all_unseen'];
					
				?>
				<span class="text">Orders<?php
					if($all_unseen_count > 0) {
							echo "<span class='ms-2 badge bg-danger'>$all_unseen_count</span>";
						}
				?></span>
				</a>
			</li>
			<li>
				<a href="messages-admin.php">
				<i class='bx bxs-user-account'></i>
				<span class="text">Accounts</span>
				</a>
			</li>
			<li class="active">
				<a href="chat-admin.php">
				<i class='bx bxs-chat'></i>
				<?php 
					// Get unseen messages count
                    $messages_unseen_query = "SELECT COUNT(*) AS messages_unseen FROM mrb_messages m WHERE m.message_type = 'user-chat' AND m.seen_byadmin = 0{$message_shop_scope_alias_condition}";
					$messages_unseen_result = mysqli_query($conn, $messages_unseen_query);
					$messages_unseen_count = mysqli_fetch_assoc($messages_unseen_result)['messages_unseen'];
				?>
				<span class="text">Messages<?php
					if($messages_unseen_count > 0) {
						echo "<span class='ms-2 badge bg-danger'>$messages_unseen_count</span>";
					}
				?></span>
				</a>
			</li>
			<li>
				<a href="team_admin.php">
				<i class='bx bxs-group'></i>
				<span class="text">Our Team</span>
				</a>
			</li>
			<?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] !== 'finance'): ?>
			<li>
				<a href="payroll-admin.php">
				<i class='bx bxs-wallet'></i>
				<span class="text">HR & Payroll</span>
				</a>
			</li>
			<li>
				<a href="suppliers-admin.php">
				<i class='bx bxs-truck'></i>
				<span class="text">Suppliers</span>
				</a>
			</li>
			<?php endif; ?>
			<?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'finance'): ?>
			<li>
				<a href="finances-admin.php">
				<i class='bx bxs-coin'></i>
				<span class="text">Finances</span>
				</a>
			</li>
			<?php endif; ?>
            <?php endif; ?>
			</ul>

			<ul class="side-menu" style="padding: 0px;">
			<li>
                <a href="<?php echo $account_page; ?>">
				<i class="bx bxs-user-circle"></i>
                <span class="text"><?php echo $current_user_role === 'admin' ? 'My Shop' : 'My Account'; ?></span>
				</a>
			</li>
			<li>
				<a href="javascript:void(0);" onclick="confirmLogout()">
				<i class='bx bx-power-off'></i>
				<span class="text">Logout</span>
				</a>
			</li>
		</ul>
	</section>
	<!-- SIDEBAR -->



	<!-- CONTENT -->
	<section id="content">
		<!-- NAVBAR -->
		<nav>
			<i class='bx bx-menu' ></i>
			<a href="#" class="nav-link">Categories</a>
			<form action="#" type="hidden">
				<div class="form-input">
					<input type="hidden"  placeholder="Search...">
					<button type="hidden" class="search-btn" style="opacity: 0;"><i class='bx bx-search' ></i></button>
				</div>
			</form>
			<input type="checkbox" id="switch-mode" hidden>
			<label for="switch-mode" class="switch-mode"></label>
			<?php 

				$user_pic = '';
				$query = "SELECT user_pic FROM mrb_users WHERE user_id = '{$_SESSION['user_id']}'";
				$result = mysqli_query($conn, $query);
				if ($result && mysqli_num_rows($result) > 0) {
					$row = mysqli_fetch_assoc($result);
					$user_pic =  "{$row['user_pic']}";
				} else {
					$user_pic = 'Images/anonymous.jpg'; // Default image if no user picture is found
				}
			
			?>

            <a href="<?php echo $account_page; ?>" class="profile">
				<img src="../<?php echo $user_pic?>">
			</a>
		</nav>
		<!-- NAVBAR -->

		<!-- MAIN -->
		 
		<main>
		<div class="head-title">
			<div class="left">
			<h1>Chats</h1>
			<ul class="breadcrumb">
				<li><a href="#">Dashboard</a></li>
				<li><i class='bx bx-chevron-right'></i></li>
				<li><a class="active" href="#">Chats</a></li>
			</ul>
			</div>
		</div>


		<!-- Editable Article Table -->
		<div class="table-data">
			<div class="order">
			<div class="head">
				<h3>Users</h3>
			</div>
			<table>
				<tbody>
				<?php
                    $query = "SELECT DISTINCT u.*
                            FROM mrb_users u
                            JOIN mrb_messages m ON m.user_id = u.user_id
                            WHERE m.message_type = 'user-chat'{$message_shop_scope_alias_condition}
                            ORDER BY u.user_name ASC";
                    $result = mysqli_query($conn, $query);
                    if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                    }

                    if (mysqli_num_rows($result) === 0) {
                        echo "<tr><td><div class='text-center text-muted py-4'>No chats yet</div></td></tr>";
                    }

                    while ($row = mysqli_fetch_assoc($result)) {
                        $getlastchat = "SELECT message FROM mrb_messages WHERE (user_id = {$row['user_id']}){$message_shop_scope_condition} ORDER BY message_id DESC LIMIT 1";
                        $lastchatresult = mysqli_query($conn, $getlastchat);
                        if (!$lastchatresult) {
                            die("Query failed: " . mysqli_error($conn));
                        }
                        $lastchat = mysqli_fetch_assoc($lastchatresult);
                        
                        // Get the last message or display a default message
                        $lastMessage = ($lastchat && isset($lastchat['message'])) ? $lastchat['message'] : "No messages yet";
                        
                        // Get unseen messages count for this specific user
                        $unseen_query = "SELECT COUNT(*) AS unseen_count FROM mrb_messages 
                                        WHERE user_id = {$row['user_id']} 
                                        AND message_type = 'user-chat' 
                                        AND seen_byadmin = 0{$message_shop_scope_condition}";
                        $unseen_result = mysqli_query($conn, $unseen_query);
                        $unseen_count = 0;
                        if($unseen_result) {
                            $unseen_row = mysqli_fetch_assoc($unseen_result);
                            $unseen_count = $unseen_row['unseen_count'];
                        }
                        
                        // Check if user image exists, otherwise use a default
                        $userImage = !empty($row['user_pic']) ? "../{$row['user_pic']}" : "../img/default-user.png";
                        
                        $badge_html = $unseen_count > 0 ? "<span class='badge bg-danger rounded-pill ms-2'>{$unseen_count}</span>" : "";
                        
                        echo "<tr>
                                <td>
                                    <button type='button' class='btn text-dark w-100 text-start user-account-btn' data-bs-toggle='modal' data-bs-target='#chatModal{$row['user_id']}' style=' border: none; background: transparent; box-shadow: none;'>
                                        <div class='user-avatar-container'>
                                            <img src='{$userImage}' class='user-avatar'>
                                            <div class='chat-status-indicator'></div>
                                        </div>
                                        <div class='user-info'>
                                            <span class='user-name'>{$row['user_name']} {$row['user_mname']} {$row['user_lname']} {$badge_html}</span>
                                            <span class='last-message'>{$lastMessage}</span>
                                        </div>
                                    </button>
                                    
                                </td>
                            </tr>";
                    }
                ?>

                <!-- Chat Modals -->
                <?php
                $query = "SELECT DISTINCT u.*
                        FROM mrb_users u
                        JOIN mrb_messages m ON m.user_id = u.user_id
                    WHERE m.message_type = 'user-chat'{$message_shop_scope_alias_condition}
                        ORDER BY u.user_name ASC";
                $result = mysqli_query($conn, $query);
                if (!$result) {
                    die("Query failed: " . mysqli_error($conn));
                }

                while ($row = mysqli_fetch_assoc($result)) {
                    $chat_user_id = (int)$row['user_id'];
                    $user_name = $row['user_name'];
                    $userImage = !empty($row['user_pic']) ? "../{$row['user_pic']}" : "../img/default-user.png";
                    
                    // Get chat history for this user
                    $chatQuery = "SELECT * FROM mrb_messages WHERE user_id = {$chat_user_id}{$message_shop_scope_condition} ORDER BY message_datesent ASC";
                    $chatResult = mysqli_query($conn, $chatQuery);
                    
                    echo "
                    <!-- Chat Modal for User ID: $chat_user_id -->
                    <div class='modal fade' id='chatModal{$chat_user_id}' tabindex='-1' aria-labelledby='chatModalLabel{$chat_user_id}' aria-hidden='true'>
                        <div class='modal-dialog modal-dialog-centered modal-lg'>
                            <div class='modal-content'>
                                <div class='modal-header'>
                                    <div class='d-flex align-items-center'>
                                        <img src='{$userImage}' alt='{$user_name}' class='rounded-circle me-2' style='width: 40px; height: 40px; object-fit: cover;'>
                                        <h5 class='modal-title' id='chatModalLabel{$chat_user_id}'>{$user_name}</h5>
                                    </div>
                                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                </div>
                                <div class='modal-body'>
                                    <div class='chat-container' style='height: 300px; overflow-y: auto; padding: 15px;'>
                                        <div class='messages-container' id='messagesContainer{$chat_user_id}'>";
                                        
                    if (mysqli_num_rows($chatResult) > 0) {
                        while ($chatRow = mysqli_fetch_assoc($chatResult)) {
                            $message = $chatRow['message'];
                            $sender_type = $chatRow['message_type'];
                            $timestamp = date('M j, g:i a', strtotime($chatRow['message_datesent']));
                            
                            if ($sender_type == 'admin') {
                                // Admin message (right-aligned)
                                echo "
                                <div class='message admin-message d-flex justify-content-end mb-2'>
                                    <div class='message-content bg-primary text-white p-2 rounded' style='max-width: 75%; word-wrap: break-word;'>
                                        <div>{$message}</div>
                                        <div class='text-end'><small class='text-light'>{$timestamp}</small></div>
                                    </div>
                                </div>";
                            } else {
                                // User message (left-aligned)
                                echo "
                                <div class='message user-message d-flex justify-content-start mb-2'>
                                    <div class='message-content bg-light p-2 rounded' style='max-width: 75%; word-wrap: break-word;'>
                                        <div>{$message}</div>
                                        <div class='text-end'><small class='text-muted'>{$timestamp}</small></div>
                                    </div>
                                </div>";
                            }
                        }
                    } else {
                        echo "<div class='text-center text-muted my-4'>No messages yet</div>";
                    }
                    
                    echo "          </div>
                                    </div>
                                    <div class='message-input mt-3'>
                                        <form id='sendMessageForm{$chat_user_id}' class='sendMessageForm'>
                                            <input type='hidden' name='user_id' value='{$chat_user_id}'>
                                            <div class='input-group'>
                                                <input type='text' class='form-control' id='messageInput{$chat_user_id}' placeholder='Type a message...' name='message' required>
                                                <button class='btn btn-primary text-dark' type='submit'>Send</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>";
                }
                ?>
				
				</tbody>
			</table>
			</div>
		</div>
		</main>
	</section>
	
	<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.min.js" integrity="sha384-RuyvpeZCxMJCqVUGFI0Do1mQrods/hhxYlcVfGPOfQtPJh0JCw12tUAZ/Mv10S7D" crossorigin="anonymous"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
            const myModal = document.getElementById('myModal');
            const myInput = document.getElementById('myInput');
            
            if (myModal && myInput) {
                myModal.addEventListener('shown.bs.modal', () => {
                    myInput.focus();
                });
            }
            });

            document.addEventListener('DOMContentLoaded', function() {
                const modals = document.querySelectorAll('.modal');
                const sidebar = document.getElementById('sidebar');
                
                modals.forEach(modal => {
                    modal.addEventListener('show.bs.modal', function () {
                    // On mobile, hide sidebar when modal opens
                    if (window.innerWidth <= 768) {
                        sidebar.classList.add('hide');
                    }
                    
                    // Mark messages as seen when modal opens
                    const modalId = this.id;
                    const userId = modalId.replace('chatModal', '');
                    
                    if (userId) {
                        fetch('mark_messages_seen.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'user_id=' + userId
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Mark messages response:', data);
                            if (data.success) {
                                console.log('Messages marked as seen for user ' + userId);
                                console.log('Remaining count:', data.remaining_count);
                                
                                // Update badge count without reloading - more specific selector
                                const messagesLink = document.querySelector('a[href="chat-admin.php"]');
                                if (messagesLink) {
                                    const badge = messagesLink.querySelector('.badge.bg-danger');
                                    console.log('Badge element found:', badge);
                                    
                                    if (data.remaining_count !== undefined) {
                                        if (data.remaining_count > 0) {
                                            if (badge) {
                                                badge.textContent = data.remaining_count;
                                            } else {
                                                // Create badge if it doesn't exist
                                                const textSpan = messagesLink.querySelector('.text');
                                                if (textSpan) {
                                                    const newBadge = document.createElement('span');
                                                    newBadge.className = 'ms-2 badge bg-danger';
                                                    newBadge.textContent = data.remaining_count;
                                                    textSpan.appendChild(newBadge);
                                                }
                                            }
                                        } else {
                                            if (badge) {
                                                badge.remove();
                                            }
                                        }
                                    }
                                }
                            }
                        })
                        .catch(error => console.error('Error marking messages as seen:', error));
                    }
                    });
                });
                });

                function confirmLogout() {
                    if (confirm("Are you sure you want to log out?")) {
                        window.location.href = "../logout.php";
                    }
                }
        </script>
        
	<script src="script.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Log all forms for debugging
        const allForms = document.querySelectorAll('form');
        console.log('All forms on page:', allForms.length);
        
        // Find all message forms
        const messageForms = document.querySelectorAll('.sendMessageForm');
        console.log('Forms with sendMessageForm class:', messageForms.length);
        
        // Store message timestamps for each user chat
        const lastMessageTimestamps = {};
        
        // Track processed messages by ID to prevent duplicates
        const processedMessageIds = {};
        
        // Initialize timestamps for each user (5 minutes ago)
        const chatModals = document.querySelectorAll('.modal');
        chatModals.forEach(modal => {
            const userId = modal.id.replace('chatModal', '');
            lastMessageTimestamps[userId] = Math.floor(Date.now() / 1000) - 300;
            processedMessageIds[userId] = new Set();
            
            // Initialize with existing message IDs
            const messageElements = modal.querySelectorAll('.message');
            messageElements.forEach((element, index) => {
                // Use index as a substitute for message ID since we don't have it in the HTML
                processedMessageIds[userId].add('existing-' + index);
            });
            
            console.log(`Initialized chat for user ${userId} with timestamp:`, lastMessageTimestamps[userId]);
        });
        
        // Unified scroll function for admin chat
        function scrollChatToBottom(userId) {
            const messagesContainer = document.getElementById('messagesContainer' + userId);
            if (messagesContainer) {
                // Use setTimeout to ensure DOM updates are complete
                setTimeout(() => {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    // Also try smooth scrolling as backup
                    messagesContainer.scrollTo({
                        top: messagesContainer.scrollHeight,
                        behavior: 'smooth'
                    });
                }, 100);
            } else {
                console.error(`Messages container not found for user ${userId}`);
            }
        }
        
        // Add message to chat function
        function addMessageToChat(userId, messageHTML, messageId) {
            if (!processedMessageIds[userId]) {
                processedMessageIds[userId] = new Set();
            }
            
            if (!processedMessageIds[userId].has(messageId)) {
                const messagesContainer = document.getElementById('messagesContainer' + userId);
                if (messagesContainer) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = messageHTML.trim();
                    
                    // Append each child node individually
                    while (tempDiv.firstChild) {
                        messagesContainer.appendChild(tempDiv.firstChild);
                    }
                    
                    // Mark as processed
                    processedMessageIds[userId].add(messageId);
                    
                    // Use the unified scroll function
                    scrollChatToBottom(userId);
                    return true;
                }
            }
            return false;
        }
        
        // Function to check for new messages
        function checkForNewMessages(userId) {
            if (!userId || !lastMessageTimestamps[userId]) return;
            
            const timestamp = lastMessageTimestamps[userId];
            console.log(`Checking messages for user ${userId}, last timestamp:`, timestamp);
            
            // Add cache-busting parameter
            fetch(`admin-check-messages.php?user_id=${userId}&last_time=${timestamp}&t=${Date.now()}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log(`Message check response for user ${userId}:`, data);
                    
                    if (data.success && data.messages && data.messages.length > 0) {
                        let addedMessages = 0;
                        
                        // Process messages in order
                        data.messages.forEach(message => {
                            if (addMessageToChat(userId, message.html, message.id)) {
                                addedMessages++;
                                console.log(`Added message ID: ${message.id} for user ${userId}, Type: ${message.type}`);
                            }
                        });
                        
                        // Update timestamp
                        if (data.latest_timestamp > lastMessageTimestamps[userId]) {
                            lastMessageTimestamps[userId] = data.latest_timestamp;
                            console.log(`Updated timestamp for user ${userId} to:`, lastMessageTimestamps[userId]);
                        }
                        
                        if (addedMessages > 0) {
                            console.log(`Added ${addedMessages} new messages for user ${userId}`);
                            
                            // Play notification sound (optional)
                            try {
                                const audio = new Audio("data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU");
                                audio.volume = 0.2;
                                audio.play().catch(err => console.log('Audio play prevented:', err));
                            } catch(e) {
                                console.log('Sound notification error:', e);
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error(`Error checking for messages for user ${userId}:`, error);
                });
        }
        
        // Setup message form submission
        messageForms.forEach((form, index) => {
            const userIdInput = form.querySelector('input[name="user_id"]');
            const messageInput = form.querySelector('.form-control');
            const userId = userIdInput ? userIdInput.value : null;
            
            console.log(`Form ${index + 1}:`, {
                'Has user_id input': !!userIdInput,
                'user_id value': userId || 'missing',
                'Has message input': !!messageInput
            });
            
            // Add event listener
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                console.log(`Form ${index + 1} submitted for user ${userId}`);
                
                // Get form data
                const formData = new FormData(this);
                formData.append('action', 'send_message');
                
                // Get values for display in chat
                const message = formData.get('message');
                
                console.log('Sending message:', {
                    userId: userId,
                    message: message
                });
                
                if (message) {
                    fetch('admin-sendmessage.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        return response.text().then(text => {
                            console.log('Raw response:', text);
                            try {
                                return JSON.parse(text);
                            } catch (e) {
                                console.error('Failed to parse response as JSON:', e);
                                throw new Error('Invalid JSON response');
                            }
                        });
                    })
                    .then(data => {
                        if (data.success) {
                            const messagesContainer = document.getElementById('messagesContainer' + userId);
                            
                            const messageElement = document.createElement('div');
                            messageElement.className = 'message admin-message d-flex justify-content-end mb-2';
                            
                            const now = new Date();
                            const timeString = now.toLocaleString('en-US', {
                                month: 'short',
                                day: 'numeric',
                                hour: 'numeric',
                                minute: '2-digit',
                                hour12: true
                            });
                            
                            messageElement.innerHTML = `
                                <div class="message-content bg-primary text-white p-2 rounded" style="max-width: 75%; word-wrap: break-word;">
                                    <div>${message}</div>
                                    <div class="text-end"><small class="text-light">${timeString}</small></div>
                                </div>
                            `;
                            
                            messagesContainer.appendChild(messageElement);
                            
                            // More specific selector to find and clear the input field
                            const inputField = document.getElementById('messageInput' + userId);
                            if (inputField) {
                                inputField.value = '';
                            } else {
                                // Fallback method
                                this.querySelector('input[name="message"]').value = '';
                            }
                                                        
                            // Use the unified scroll function
                            scrollChatToBottom(userId);
                            
                            // Trigger immediate check for the response
                            setTimeout(() => checkForNewMessages(userId), 500);
                        } else {
                            alert('Failed to send message: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error details:', error);
                    });
                }
            });
        });
        
        // Set up message checking for active chats
        chatModals.forEach(modal => {
            const userId = modal.id.replace('chatModal', '');
            
            // Start checking when modal is opened
            modal.addEventListener('shown.bs.modal', function() {
                console.log(`Chat modal opened for user ${userId}`);
                
                // Initial scroll to bottom
                scrollChatToBottom(userId);
                
                // Check for new messages immediately
                checkForNewMessages(userId);
                
                // Set interval for this specific user's chat
                if (!window['chatInterval' + userId]) {
                    window['chatInterval' + userId] = setInterval(() => {
                        checkForNewMessages(userId);
                    }, 3000); // Check every 3 seconds
                    console.log(`Started message checking interval for user ${userId}`);
                }
            });
            
            // Stop checking when modal is closed
            modal.addEventListener('hidden.bs.modal', function() {
                console.log(`Chat modal closed for user ${userId}`);
                
                // Clear the interval when the modal is closed
                if (window['chatInterval' + userId]) {
                    clearInterval(window['chatInterval' + userId]);
                    window['chatInterval' + userId] = null;
                    console.log(`Stopped message checking interval for user ${userId}`);
                }
            });
        });
        
        // Auto-scroll to the bottom of each chat when opened
        chatModals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', function() {
                const userId = this.id.replace('chatModal', '');
                scrollChatToBottom(userId);
            });
        });
    });

    function confirmLogout() {
        if (confirm("Are you sure you want to log out?")) {
            window.location.href = "../logout.php";
        }
    }
</script>
</body>
</html>