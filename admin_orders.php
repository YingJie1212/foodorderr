<?php
session_start();
include 'db.php';


$filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT o.id AS order_id, 
               COALESCE(u.name, '访客') AS user_name, 
               o.total_price, o.created_at, o.status,
               oi.id AS order_item_id, oi.quantity, m.name AS menu_name
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN menu_items m ON oi.menu_item_id = m.id";

if ($filter) {
    $sql .= " WHERE o.status = '".$conn->real_escape_string($filter)."'";
}

$sql .= " ORDER BY o.created_at DESC";

$result = $conn->query($sql);

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[$row['order_id']]['user'] = $row['user_name'];
    $orders[$row['order_id']]['total'] = $row['total_price'];
    $orders[$row['order_id']]['created'] = $row['created_at'];
    $orders[$row['order_id']]['status'] = $row['status'];
    $orders[$row['order_id']]['items'][] = [
        'menu' => $row['menu_name'],
        'qty' => $row['quantity'],
        'order_item_id' => $row['order_item_id']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management System</title>
    <style>
        :root {
            --primary-bg: #f8f9fa;
            --secondary-bg: #e9ecef;
            --card-bg: #ffffff;
            --text-primary: #212529;
            --text-secondary: #495057;
            --border-color: #dee2e6;
            --accent-color: #6c757d;
            --hover-color: #e9ecef;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --border-radius: 0.375rem;
            --box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        body {
            background-color: var(--primary-bg);
            color: var(--text-primary);
            line-height: 1.6;
            padding: 2rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h2 {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            font-weight: 500;
        }
        
        .filter-form {
            margin-bottom: 2rem;
            background-color: var(--card-bg);
            padding: 1.25rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .form-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        label {
            font-weight: 500;
            color: var(--text-secondary);
            white-space: nowrap;
        }
        
        select, button, input {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            background-color: var(--card-bg);
            color: var(--text-primary);
            transition: var(--transition);
            font-size: 0.875rem;
        }
        
        select {
            min-width: 150px;
            cursor: pointer;
        }
        
        select:focus, button:focus, input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(108, 117, 125, 0.25);
        }
        
        button {
            background-color: var(--accent-color);
            color: white;
            cursor: pointer;
            font-weight: 500;
            border: none;
        }
        
        button:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }
        
        .order-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            border-left: 4px solid var(--accent-color);
        }
        
        .order-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .order-info {
            flex: 1;
            min-width: 250px;
        }
        
        .order-info p {
            margin-bottom: 0.5rem;
            display: flex;
            gap: 0.5rem;
        }
        
        .order-info strong {
            font-weight: 500;
            min-width: 100px;
            color: var(--text-secondary);
        }
        
        .status-form {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background-color: var(--warning-color);
            color: #000;
        }
        
        .status-preparing {
            background-color: var(--info-color);
            color: white;
        }
        
        .status-completed {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-cancelled {
            background-color: var(--danger-color);
            color: white;
        }
        
        .order-items {
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-color);
        }
        
        .order-items h4 {
            margin-bottom: 0.75rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .order-items ul {
            list-style-type: none;
        }
        
        .order-items li {
            padding: 0.5rem 0;
            border-bottom: 1px dashed var(--border-color);
            display: flex;
            justify-content: space-between;
        }
        
        .order-items li:last-child {
            border-bottom: none;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            color: var(--text-secondary);
        }
        
        .empty-state p {
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .form-group {
                width: 100%;
            }
            
            select {
                flex-grow: 1;
            }
            
            .order-header {
                flex-direction: column;
            }
            
            .status-form {
                width: 100%;
            }
            
            .status-form select {
                flex-grow: 1;
            }
            
            .status-form button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Order Management System</h2>
        
       
        <form method="get" class="filter-form">
            <div class="form-group">
                <label for="status">Filter by Status:</label>
                <select name="status" id="status" onchange="this.form.submit()">
                    <option value="">All Orders</option>
                    <option value="pending" <?= $filter=='pending'?'selected':'' ?>>Pending</option>
                    <option value="preparing" <?= $filter=='preparing'?'selected':'' ?>>Preparing</option>
                    <option value="completed" <?= $filter=='completed'?'selected':'' ?>>Completed</option>
                    <option value="cancelled" <?= $filter=='cancelled'?'selected':'' ?>>Cancelled</option>
                </select>
            </div>
        </form>
        
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <h3>No orders found</h3>
                <p>There are currently no orders matching your criteria.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $id => $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <p><strong>Order ID:</strong> <span>#<?= $id ?></span></p>
                            <p><strong>Customer:</strong> <span><?= $order['user'] ?></span></p>
                            <p><strong>Total:</strong> <span>RM<?= number_format($order['total'], 2) ?></span></p>
                            <p><strong>Order Time:</strong> <span><?= date('Y-m-d H:i', strtotime($order['created'])) ?></span></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?php 
                                        switch($order['status']) {
                                            case 'pending': echo 'Pending'; break;
                                            case 'preparing': echo 'Preparing'; break;
                                            case 'completed': echo 'Completed'; break;
                                            case 'cancelled': echo 'Cancelled'; break;
                                            default: echo $order['status'];
                                        }
                                    ?>
                                </span>
                            </p>
                        </div>
                        
                        <form method="post" action="update_status.php" class="status-form">
                            <input type="hidden" name="order_id" value="<?= $id ?>">
                            <select name="status">
                                <option value="pending" <?= $order['status']=='pending'?'selected':'' ?>>Pending</option>
                                <option value="preparing" <?= $order['status']=='preparing'?'selected':'' ?>>Preparing</option>
                                <option value="completed" <?= $order['status']=='completed'?'selected':'' ?>>Completed</option>
                                <option value="cancelled" <?= $order['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                            </select>
                            <button type="submit">Update Status</button>
                        </form>
                    </div>
                    
                    <div class="order-items">
                        <h4>Order Items</h4>
                        <ul>
                            <?php foreach ($order['items'] as $item): ?>
                                <li>
                                    <span><?= $item['menu'] ?></span>
                                    <span>× <?= $item['qty'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>