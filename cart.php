<?php
include 'db.php';
session_start();

// Add to cart functionality
if (isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'];
    $qty = intval($_POST['quantity']);
    $addons = isset($_POST['addon']) ? $_POST['addon'] : [];
    $remark = isset($_POST['remark']) ? trim($_POST['remark']) : '';
    $cooking_level = isset($_POST['cooking_level']) ? $_POST['cooking_level'] : null;

    // Validate quantity
    if ($qty > 0 && $qty <= 20) {
        // 购物车结构: [item_id => [qty, addons, remark, cooking_level]]
        $_SESSION['cart'][$item_id] = [
            'quantity' => $qty,
            'addons' => $addons,
            'remark' => $remark,
            'cooking_level' => $cooking_level
        ];
        $_SESSION['success_message'] = "Item added to cart successfully!";
    } else {
        $_SESSION['error_message'] = "Please select a quantity between 1-20";
    }
    header("Location: menu.php");
    exit;
}

// Get cart item count
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Restaurant Ordering System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Light Gray Color Scheme */
        :root {
            --primary-color: #F5F7FA; /* Very light gray */
            --primary-light: #FFFFFF;
            --primary-dark: #E1E5EB;
            --secondary-color: #FFFFFF; /* White background */
            --accent-color: #D3D9E4; /* Light gray */
            --accent-light: #E8EBF0;
            --accent-dark: #B8C2CC;
            --text-color: #4A5568; /* Dark gray text */
            --text-light: #718096;
            --light-color: #FFFFFF;
            --dark-color: #2D3748;
            --success-color: #38A169;
            --warning-color: #DD6B20;
            --danger-color: #E53E3E;
            --border-radius: 10px;
            --border-radius-sm: 6px;
            --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            --box-shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --spacing-unit: 1rem;
        }

        /* Base Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Noto Sans SC', sans-serif;
        }

        body {
            background-color: var(--primary-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        /* Layout */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Top Navigation - Light Gray */
        .top-nav {
            background-color: var(--light-color);
            color: var(--text-color);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--box-shadow);
            border-bottom: 1px solid var(--accent-color);
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            letter-spacing: 0.5px;
        }

        .logo i {
            color: var(--text-color);
        }

        .user-nav {
            display: flex;
            align-items: center;
            gap: 1.75rem;
        }

        .nav-link {
            color: var(--text-color);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            font-size: 0.9rem;
            padding: 0.5rem 0;
            position: relative;
            letter-spacing: 0.3px;
        }

        .nav-link:hover {
            color: var(--accent-dark);
        }

        .nav-link i {
            font-size: 1.1rem;
        }

        .badge {
            position: absolute;
            top: -8px;
            right: -12px;
            background-color: var(--danger-color);
            color: var(--light-color);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        /* Cart Page Styles */
        .cart-container {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            border: 1px solid var(--accent-color);
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--accent-color);
        }

        .cart-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .cart-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: none;
        }

        .btn-primary {
            background-color: var(--accent-dark);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--text-light);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--text-color);
            border: 1px solid var(--accent-color);
        }

        .btn-outline:hover {
            background-color: var(--primary-color);
        }

        /* Empty Cart State */
        .empty-cart {
            text-align: center;
            padding: 3rem;
        }

        .empty-cart-icon {
            font-size: 4rem;
            color: var(--accent-color);
            margin-bottom: 1.5rem;
        }

        .empty-cart-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .empty-cart-text {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto 1.5rem;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        /* Cart Table Styles */
        .cart-table {
            width: 100%;
            margin-bottom: 2rem;
            border-collapse: collapse;
        }

        .cart-table th {
            background: var(--primary-color);
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
        }

        .cart-table td {
            padding: 10px 8px;
            border-bottom: 1px solid var(--accent-color);
        }

        .cart-table tfoot td {
            padding: 12px 8px;
            font-weight: 600;
        }

        .btn-delete, .btn-edit {
            color: var(--danger-color);
            font-weight: 600;
            text-decoration: underline;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            margin-left: 10px;
        }

        .btn-edit {
            color: var(--warning-color);
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .modal-container {
            background: #fff;
            padding: 2rem;
            border-radius: var(--border-radius);
            width: 100%;
            max-width: 500px;
            box-shadow: var(--box-shadow-lg);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-light);
        }

        .modal-title {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--accent-color);
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-dark);
            box-shadow: 0 0 0 3px rgba(184, 194, 204, 0.3);
        }

        .form-number {
            width: 80px;
            padding: 0.5rem;
            border: 1px solid var(--accent-color);
            border-radius: var(--border-radius-sm);
        }

        .addon-options {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .addon-option {
            display: flex;
            align-items: center;
        }

        .addon-option input {
            margin-right: 0.5rem;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-cancel {
            background-color: var(--accent-color);
            color: var(--text-color);
        }

        .btn-cancel:hover {
            background-color: var(--accent-dark);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .top-nav {
                padding: 0.8rem 1rem;
            }
            
            .user-nav {
                gap: 1rem;
            }
            
            .nav-link span {
                display: none;
            }
            
            .nav-link i {
                font-size: 1.2rem;
            }
            
            .badge {
                top: -5px;
                right: -5px;
            }
            
            .container {
                padding: 1rem;
            }
            
            .cart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .cart-actions {
                width: 100%;
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }

            .cart-table {
                display: block;
                overflow-x: auto;
            }

            .modal-container {
                width: 95%;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-container">
            <a href="menu.php" class="logo">
                <i class="fas fa-utensils"></i>
                <span>Restaurant</span>
            </a>
            <div class="user-nav">
                <a href="cart.php" class="nav-link" title="Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Cart</span>
                    <?php if ($cart_count > 0): ?>
                        <span class="badge"><?= $cart_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="orders.php" class="nav-link" title="Orders">
                    <i class="fas fa-history"></i>
                    <span>Orders</span>
                </a>
                <a href="logout.php" class="nav-link" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="cart-container">
            <div class="cart-header">
                <h1 class="cart-title">Your Cart</h1>
                <div class="cart-actions">
                    <a href="menu.php" class="btn btn-outline">
                        <i class="fas fa-chevron-left"></i> Continue Shopping
                    </a>
                </div>
            </div>

            <?php if ($cart_count > 0): ?>
                <form method="post" action="cart.php">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Add-ons</th>
                                <th>Remark</th>
                                <th>Cooking Level</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $total = 0;
                        foreach ($_SESSION['cart'] as $item_id => $cart_item):
                            // 获取商品信息
                            $item_sql = "SELECT * FROM menu_items WHERE id=" . intval($item_id);
                            $item_result = $conn->query($item_sql);
                            $item = $item_result ? $item_result->fetch_assoc() : null;
                            if (!$item) continue;
                            $price = floatval($item['price']);
                            $qty = intval($cart_item['quantity']);
                            // 计算加料价格
                            $addon_names = [];
                            $addon_total = 0;
                            if (!empty($cart_item['addons'])) {
                                $addon_ids = array_map('intval', $cart_item['addons']);
                                if (count($addon_ids) > 0) {
                                    $addon_sql = "SELECT name, price FROM addons WHERE id IN (" . implode(',', $addon_ids) . ")";
                                    $addon_result = $conn->query($addon_sql);
                                    if ($addon_result) {
                                        while ($addon = $addon_result->fetch_assoc()) {
                                            $addon_names[] = $addon['name'] . ($addon['price'] > 0 ? " (+RM" . number_format($addon['price'],2) . ")" : "");
                                            $addon_total += floatval($addon['price']);
                                        }
                                    }
                                }
                            }
                            $remark = htmlspecialchars($cart_item['remark'] ?? '');
                            $cooking_level = '';
                            if (!empty($cart_item['cooking_level'])) {
                                $cl_sql = "SELECT name FROM cooking_levels WHERE id=" . intval($cart_item['cooking_level']);
                                $cl_result = $conn->query($cl_sql);
                                if ($cl_result && $cl_row = $cl_result->fetch_assoc()) {
                                    $cooking_level = htmlspecialchars($cl_row['name']);
                                }
                            }
                            $subtotal = ($price + $addon_total) * $qty;
                            $total += $subtotal;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td>RM <?= number_format($price,2) ?></td>
                            <td><?= $qty ?></td>
                            <td>
                                <?= implode('<br>', $addon_names) ?>
                            </td>
                            <td><?= $remark ?></td>
                            <td><?= $cooking_level ?></td>
                            <td>RM <?= number_format($subtotal,2) ?></td>
                            <td>
                                <button type="button" class="btn-delete" onclick="deleteItem(<?= $item_id ?>)">Remove</button>
                                <button type="button" class="btn-edit" data-item-id="<?= $item_id ?>">Edit</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" style="text-align:right;">Total:</td>
                                <td>RM <?= number_format($total,2) ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <a href="checkout.php" class="btn btn-primary" id="checkoutBtn"><i class="fas fa-credit-card"></i> Checkout</a>
                </form>
                <!-- Edit Modal -->
                <div id="editModal" class="modal-overlay">
                    <div class="modal-container">
                        <button class="modal-close" id="editModalClose">&times;</button>
                        <h3 class="modal-title">Edit Cart Item</h3>
                        <form id="editCartForm" method="post" action="cart.php">
                            <input type="hidden" name="edit_item_id" id="editItemId">
                            
                            <div class="form-group">
                                <label class="form-label">Quantity</label>
                                <input type="number" name="edit_quantity" id="editQuantity" min="1" max="20" class="form-number">
                            </div>
                            
                            <div class="form-group" id="editAddonsContainer">
                                <label class="form-label">Add-ons</label>
                                <div class="addon-options" id="addonOptions">
                                    <!-- Dynamic addons will be inserted here -->
                                </div>
                            </div>
                            
                            <div class="form-group" id="editCookingLevelContainer">
                                <label class="form-label">Cooking Level</label>
                                <select name="edit_cooking_level" id="editCookingLevel" class="form-control">
                                    <option value="">Select</option>
                                    <!-- Dynamic cooking levels will be inserted here -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Remark</label>
                                <input type="text" name="edit_remark" id="editRemark" class="form-control" placeholder="Any special requests?">
                            </div>
                            
                            <div class="modal-actions">
                                <button type="button" class="btn btn-cancel" id="cancelEdit">Cancel</button>
                                <button type="submit" name="edit_cart" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
                <script>
                // Checkout确认弹窗
                document.getElementById('checkoutBtn')?.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to checkout?')) {
                        window.location.href = 'checkout.php';
                    }
                });
                // Cart data from PHP session
                var cartData = {};
                <?php foreach ($_SESSION['cart'] as $item_id => $cart_item): ?>
                cartData[<?= json_encode($item_id) ?>] = <?= json_encode($cart_item) ?>;
                <?php endforeach; ?>
                
                // Addons and cooking levels data
                var addonsData = {};
                var cookingLevelsData = {};
                <?php foreach ($_SESSION['cart'] as $item_id => $cart_item):
                    // 获取所有加料
                    $addon_sql = "SELECT id, name, price FROM addons a JOIN menu_item_addons mia ON a.id = mia.addon_id WHERE mia.menu_item_id = $item_id";
                    $addon_result = $conn->query($addon_sql);
                    $addons_all = [];
                    if ($addon_result) {
                        while ($row = $addon_result->fetch_assoc()) {
                            $addons_all[] = $row;
                        }
                    }
                    // 获取熟度
                    $cl_sql = "SELECT cl.id, cl.name FROM cooking_levels cl JOIN menu_item_cooking_levels micl ON cl.id = micl.cooking_level_id WHERE micl.menu_item_id = $item_id";
                    $cl_result = $conn->query($cl_sql);
                    $cooking_levels = [];
                    if ($cl_result) {
                        while ($row = $cl_result->fetch_assoc()) {
                            $cooking_levels[] = $row;
                        }
                    }
                ?>
                addonsData[<?= json_encode($item_id) ?>] = <?= json_encode($addons_all) ?>;
                cookingLevelsData[<?= json_encode($item_id) ?>] = <?= json_encode($cooking_levels) ?>;
                <?php endforeach; ?>

                // Open edit modal
                function openEditModal(itemId) {
                    var data = cartData[itemId];
                    document.getElementById('editModal').style.display = 'flex';
                    document.getElementById('editItemId').value = itemId;
                    document.getElementById('editQuantity').value = data.quantity;
                    document.getElementById('editRemark').value = data.remark || '';
                    
                    // Populate addons
                    var addonsHtml = '';
                    var allAddons = addonsData[itemId] || [];
                    if (allAddons.length > 0) {
                        for (var i = 0; i < allAddons.length; i++) {
                            var addon = allAddons[i];
                            var checked = (data.addons && data.addons.indexOf(addon.id.toString()) !== -1 || 
                                         data.addons && data.addons.indexOf(addon.id) !== -1) ? 'checked' : '';
                            var priceText = addon.price > 0 ? '(+RM' + Number(addon.price).toFixed(2) + ')' : '(Free)';
                            
                            addonsHtml += `
                                <div class="addon-option">
                                    <input type="checkbox" name="edit_addon[]" value="${addon.id}" id="addon-${addon.id}" ${checked}>
                                    <label for="addon-${addon.id}">${addon.name} ${priceText}</label>
                                </div>
                            `;
                        }
                    } else {
                        addonsHtml = '<p>No add-ons available for this item</p>';
                    }
                    document.getElementById('addonOptions').innerHTML = addonsHtml;
                    
                    // Populate cooking levels
                    var clHtml = '<option value="">Select</option>';
                    var allCL = cookingLevelsData[itemId] || [];
                    if (allCL.length > 0) {
                        for (var j = 0; j < allCL.length; j++) {
                            var cl = allCL[j];
                            var selected = (data.cooking_level == cl.id) ? 'selected' : '';
                            clHtml += `<option value="${cl.id}" ${selected}>${cl.name}</option>`;
                        }
                    }
                    document.getElementById('editCookingLevel').innerHTML = clHtml;
                    
                    // Show/hide cooking level section based on availability
                    document.getElementById('editCookingLevelContainer').style.display = 
                        allCL.length > 0 ? 'block' : 'none';
                }
                
                // Delete item function
                function deleteItem(itemId) {
                    if (confirm('Are you sure you want to remove this item from your cart?')) {
                        window.location.href = 'cart.php?delete=' + itemId;
                    }
                }
                
                // Bind edit buttons
                document.querySelectorAll('.btn-edit').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var itemId = this.getAttribute('data-item-id');
                        openEditModal(itemId);
                    });
                });
                
                // Close modal
                document.getElementById('editModalClose').addEventListener('click', function() {
                    document.getElementById('editModal').style.display = 'none';
                });
                
                document.getElementById('cancelEdit').addEventListener('click', function() {
                    document.getElementById('editModal').style.display = 'none';
                });
                
                // Close modal when clicking outside
                document.getElementById('editModal').addEventListener('click', function(e) {
                    if (e.target === this) {
                        document.getElementById('editModal').style.display = 'none';
                    }
                });
                </script>
            <?php else: ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h2 class="empty-cart-title">Your cart is empty</h2>
                    <p class="empty-cart-text">Browse our menu to find something delicious to order</p>
                    <a href="menu.php" class="btn btn-primary">
                        <i class="fas fa-utensils"></i> Browse Menu
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>