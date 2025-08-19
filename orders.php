<?php
include 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

// 修复SQL语法错误，支持未登录时显示全部订单
$where = '';
if (!empty($user_id)) {
    $where = "WHERE user_id = " . intval($user_id);
}
$result = $conn->query("SELECT * FROM orders $where ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>历史订单</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        h2 {
            color: #444;
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .order-card {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #fafafa;
            transition: all 0.3s ease;
        }
        .order-card:hover {
            background-color: #f0f0f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eaeaea;
        }
        .order-details {
            color: #666;
            margin-top: 10px;
        }
        .order-item {
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        a {
            color: #555;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        a:hover {
            color: #222;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #e0e0e0;
            color: #333;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #d0d0d0;
            color: #000;
        }
        .text-muted {
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>历史订单</h2>
        <a href="menu.php" class="btn">返回菜单</a>
        
        <?php if ($result->num_rows > 0): ?>
            <?php while ($order = $result->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <strong>订单号: <?= $order['id'] ?></strong>
                        <span class="text-muted"><?= $order['created_at'] ?></span>
                    </div>
                    <div>
                        <strong>总价:</strong> RM<?= number_format($order['total_price'], 2) ?>
                    </div>
                    
                    <div class="order-details">
                        <?php
                        $order_id = $order['id'];
                        $items = $conn->query("
                            SELECT m.name, oi.quantity FROM order_items oi 
                            JOIN menu_items m ON oi.menu_item_id = m.id 
                            WHERE oi.order_id = $order_id
                        ");
                        while ($item = $items->fetch_assoc()): ?>
                            <div class="order-item">
                                <?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>暂无订单记录</p>
        <?php endif; ?>
    </div>
</body>
</html>