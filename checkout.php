<?php
include 'db.php';
session_start();

$cart = $_SESSION['cart'] ?? [];
if (!$cart) {
    // Display styled empty cart message
    echo '
    <!DOCTYPE html>
    <html lang="zh">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>购物车为空</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root {
                --primary-color: #F5F7FA;
                --primary-light: #FFFFFF;
                --primary-dark: #E1E5EB;
                --accent-color: #D3D9E4;
                --accent-dark: #B8C2CC;
                --text-color: #4A5568;
                --text-light: #718096;
                --dark-color: #2D3748;
                --danger-color: #E53E3E;
                --border-radius: 10px;
                --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                --box-shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.1);
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: "Inter", "Noto Sans SC", sans-serif;
            }

            body {
                background-color: var(--primary-color);
                color: var(--text-color);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }

            .empty-cart-container {
                max-width: 500px;
                width: 100%;
                background-color: var(--primary-light);
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow-lg);
                padding: 40px;
                text-align: center;
                border: 1px solid var(--accent-color);
            }

            .empty-cart-icon {
                font-size: 4rem;
                color: var(--accent-dark);
                margin-bottom: 20px;
            }

            .empty-cart-title {
                font-size: 1.8rem;
                margin-bottom: 15px;
                color: var(--text-color);
                font-weight: 600;
            }

            .empty-cart-message {
                color: var(--text-light);
                margin-bottom: 30px;
                line-height: 1.6;
            }

            .btn {
                display: inline-block;
                padding: 12px 24px;
                background-color: var(--accent-dark);
                color: white;
                border-radius: var(--border-radius);
                text-decoration: none;
                font-weight: 500;
                transition: all 0.3s ease;
            }

            .btn:hover {
                background-color: var(--dark-color);
                transform: translateY(-2px);
                box-shadow: var(--box-shadow);
            }

            .btn i {
                margin-right: 8px;
            }

            @media (max-width: 480px) {
                .empty-cart-container {
                    padding: 30px 20px;
                }
                
                .empty-cart-icon {
                    font-size: 3rem;
                }
                
                .empty-cart-title {
                    font-size: 1.5rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="empty-cart-container">
            <div class="empty-cart-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h2 class="empty-cart-title">您的购物车是空的</h2>
            <p class="empty-cart-message">您还没有添加任何商品到购物车，请浏览我们的菜单选择您喜欢的菜品</p>
            <a href="menu.php" class="btn">
                <i class="fas fa-utensils"></i> 浏览菜单
            </a>
        </div>
    </body>
    </html>
    ';
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$total = 0;

// 计算总价 & 整理购物车数据
$ids = implode(",", array_keys($cart));
$result = $conn->query("SELECT * FROM menu_items WHERE id IN ($ids)");
$items = [];
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $cart_item = $cart[$id];
    $qty = is_array($cart_item) ? ($cart_item['quantity'] ?? 1) : $cart_item;
    $subtotal = $qty * $row['price'];
    $items[] = [
        'id' => $id,
        'name' => $row['name'],
        'qty' => $qty,
        'subtotal' => $subtotal,
        'addons' => is_array($cart_item) ? ($cart_item['addons'] ?? []) : [],
        'remark' => is_array($cart_item) ? ($cart_item['remark'] ?? '') : '',
        'cooking_level' => is_array($cart_item) ? ($cart_item['cooking_level'] ?? null) : null
    ];
    $total += $subtotal;
}

// 插入订单
$user_id_sql = is_null($user_id) ? 'NULL' : intval($user_id);
$total_sql = number_format($total, 2, '.', '');
$conn->query("INSERT INTO orders (user_id, total_price) VALUES ($user_id_sql, $total_sql)");
$order_id = $conn->insert_id;

// 插入订单详情
foreach ($items as $item) {
    $conn->query("INSERT INTO order_items (order_id, menu_item_id, quantity) 
        VALUES ($order_id, {$item['id']}, {$item['qty']})");
}

unset($_SESSION['cart']); // 清空购物车
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>订单收据</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #F5F7FA;
            --primary-light: #FFFFFF;
            --primary-dark: #E1E5EB;
            --secondary-color: #FFFFFF;
            --accent-color: #D3D9E4;
            --accent-light: #E8EBF0;
            --accent-dark: #B8C2CC;
            --text-color: #4A5568;
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Noto Sans SC', sans-serif;
            background-color: var(--primary-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            background-color: var(--primary-light);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow-lg);
            overflow: hidden;
            border: 1px solid var(--accent-color);
        }

        .header {
            background-color: var(--text-dark);
            color: white;
            padding: 20px 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .success-message {
            background-color: rgba(56, 161, 105, 0.1);
            color: var(--success-color);
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid var(--accent-color);
        }

        .success-message i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .order-info {
            padding: 25px 30px;
            border-bottom: 1px solid var(--accent-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .info-item {
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
            display: block;
        }

        .info-value {
            color: var(--text-color);
        }

        .order-details {
            padding: 0 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
        }

        th {
            background-color: var(--accent-dark);
            color: var(--primary-light);
            text-align: left;
            padding: 12px 15px;
            font-weight: 600;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--accent-color);
        }

        tr:nth-child(even) {
            background-color: var(--primary-color);
        }

        tr:hover {
            background-color: var(--accent-light);
        }

        .total-row {
            background-color: var(--accent-dark) !important;
            color: white;
            font-weight: 600;
        }

        .footer {
            padding: 20px 30px;
            text-align: center;
            background-color: var(--primary-color);
            border-top: 1px solid var(--accent-color);
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--accent-dark);
            color: white;
            border-radius: var(--border-radius-sm);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background-color: var(--text-dark);
        }

        .btn i {
            margin-right: 8px;
        }

        .addon-list {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .cooking-level {
            display: inline-block;
            padding: 3px 8px;
            background-color: var(--accent-color);
            border-radius: var(--border-radius-sm);
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .container {
                margin: 15px auto;
            }
            
            .header, .order-info, .order-details {
                padding: 15px;
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 0.9rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>餐厅订单收据</h1>
        </div>
        
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <h2>订单提交成功</h2>
        </div>
        
        <div class="order-info">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">订单编号</span>
                    <span class="info-value">#<?php echo $order_id; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">订单日期</span>
                    <span class="info-value"><?php echo date('Y-m-d H:i'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">订单状态</span>
                    <span class="info-value">已确认</span>
                </div>
                <div class="info-item">
                    <span class="info-label">支付方式</span>
                    <span class="info-value">到店支付</span>
                </div>
            </div>
        </div>
        
        <div class="order-details">
            <table>
                <thead>
                    <tr>
                        <th>菜品名称</th>
                        <th>数量</th>
                        <th>备注</th>
                        <th>小计 (RM)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($items as $item) {
                        // 获取附加项名称
                        $addons_text = '';
                        if (!empty($item['addons'])) {
                            $addon_ids = implode(",", $item['addons']);
                            $addon_rs = $conn->query("SELECT name FROM addons WHERE id IN ($addon_ids)");
                            $addon_names = [];
                            while ($a = $addon_rs->fetch_assoc()) {
                                $addon_names[] = $a['name'];
                            }
                            $addons_text = implode(", ", $addon_names);
                        }
                        
                        // 获取烹饪程度
                        $cooking_level = '';
                        if (!empty($item['cooking_level'])) {
                            $cl_rs = $conn->query("SELECT name FROM cooking_levels WHERE id = " . intval($item['cooking_level']));
                            if ($cl_rs && $cl_row = $cl_rs->fetch_assoc()) {
                                $cooking_level = $cl_row['name'];
                            }
                        }
                        ?>
                        <tr>
                            <td>
                                <div><?php echo htmlspecialchars($item['name']); ?></div>
                                <?php if ($addons_text): ?>
                                <div class="addon-list">
                                    <small>附加: <?php echo $addons_text; ?></small>
                                </div>
                                <?php endif; ?>
                                <?php if ($cooking_level): ?>
                                <div class="cooking-level">
                                    <small><?php echo $cooking_level; ?></small>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $item['qty']; ?></td>
                            <td><?php echo htmlspecialchars($item['remark']); ?></td>
                            <td><?php echo number_format($item['subtotal'], 2); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr class="total-row">
                        <td colspan="3">总计</td>
                        <td>RM <?php echo number_format($total, 2); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <a href="orders.php" class="btn">
                <i class="fas fa-history"></i> 查看订单历史
            </a>
            <a href="menu.php" class="btn" style="margin-left: 10px;">
                <i class="fas fa-utensils"></i> 继续点餐
            </a>
        </div>
    </div>
</body>
</html>