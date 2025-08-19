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

// Get search and filter parameters
$category_filter = $_GET['category'] ?? '';
$search_keyword = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'name_asc';

// Build SQL query
$sql = "SELECT * FROM menu_items WHERE 1";

if ($category_filter !== '') {
    $category_filter = $conn->real_escape_string($category_filter);
    $sql .= " AND category = '$category_filter'";
}

// 修正搜索条件，判断 menu_items 表是否有 description 字段
// 如果没有 description 字段，只搜索 name
if (!empty($search_keyword)) {
    $search_keyword = $conn->real_escape_string($search_keyword);
    $desc_exists = false;
    $desc_check = $conn->query("SHOW COLUMNS FROM menu_items LIKE 'description'");
    if ($desc_check && $desc_check->num_rows > 0) {
        $desc_exists = true;
    }
    if ($desc_exists) {
        $sql .= " AND (name LIKE '%$search_keyword%' OR description LIKE '%$search_keyword%')";
    } else {
        $sql .= " AND name LIKE '%$search_keyword%'";
    }
}

// Add sorting
switch ($sort_by) {
    case 'price_asc':
        $sql .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY price DESC";
        break;
    case 'popular':
        // Fallback: popularity column does not exist, use name ASC
        $sql .= " ORDER BY name ASC";
        break;
    default:
        $sql .= " ORDER BY name ASC";
}

$result = $conn->query($sql);

// Get cart item count
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Restaurant Ordering System</title>
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
            --sidebar-width: 280px;
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
        .app-container {
            display: grid;
            grid-template-columns: var(--sidebar-width) 1fr;
            min-height: 100vh;
        }

        .main-content {
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
            grid-column: 1 / -1;
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

        /* Sidebar - White */
        .sidebar {
            background-color: var(--light-color);
            border-right: 1px solid var(--accent-color);
            padding: 2rem 1.5rem;
            position: sticky;
            top: 70px;
            height: calc(100vh - 70px);
            overflow-y: auto;
        }

        .sidebar-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar-title i {
            font-size: 1rem;
        }

        /* Search Bar */
        .search-bar {
            background-color: var(--light-color);
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid var(--accent-color);
        }

        .search-input {
            flex: 1;
            padding: 0.8rem 1.2rem;
            border: 1px solid var(--accent-color);
            border-radius: var(--border-radius);
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: var(--light-color);
            color: var(--text-color);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-dark);
            box-shadow: 0 0 0 2px rgba(211, 217, 228, 0.2);
        }

        .search-btn {
            padding: 0.8rem 1.5rem;
            background-color: var(--accent-dark);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .search-btn:hover {
            background-color: var(--text-light);
        }

        /* Filter Form */
        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .filter-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-control {
            padding: 0.8rem 1rem;
            border: 1px solid var(--accent-color);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            transition: var(--transition);
            background-color: var(--light-color);
            color: var(--text-color);
            width: 100%;
        }

        .filter-control:focus {
            outline: none;
            border-color: var(--accent-dark);
            box-shadow: 0 0 0 2px rgba(211, 217, 228, 0.2);
        }

        .filter-control.select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 14px;
            padding-right: 2.5rem;
        }

        .filter-btn {
            padding: 0.8rem;
            background-color: var(--accent-dark);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .filter-btn:hover {
            background-color: var(--text-light);
        }

        /* Menu Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.75rem;
            margin-bottom: 3rem;
        }

        .menu-item {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            border: 1px solid var(--accent-color);
        }

        .menu-item:hover {
            box-shadow: var(--box-shadow-lg);
            transform: translateY(-5px);
        }

        .menu-item-img-container {
            position: relative;
            height: 220px;
            width: 100%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f3f3;
        }

        .menu-item-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #f3f3f3;
            transition: var(--transition);
        }

        .menu-item:hover .menu-item-img {
            transform: scale(1.05);
        }

        .menu-item-badge {
            position: absolute;
            top: 0.75rem;
            left: 0.75rem;
            background-color: var(--danger-color);
            color: var(--light-color);
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 1;
        }

        .menu-item-category {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 0.25rem 0.75rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid var(--accent-color);
        }

        .menu-item-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .menu-item-header {
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }

        .menu-item-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .menu-item-desc {
            color: var(--text-light);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .menu-item-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-color);
            white-space: nowrap;
        }

        .menu-item-footer {
            margin-top: auto;
        }

        .menu-item-form {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .quantity-input {
            position: relative;
            flex: 1;
        }

        .quantity-input input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--accent-color);
            border-radius: var(--border-radius);
            text-align: center;
            font-size: 0.9rem;
            transition: var(--transition);
            color: var(--text-color);
            background-color: var(--light-color);
        }

        .quantity-input input:focus {
            border-color: var(--accent-dark);
            outline: none;
        }

        .add-to-cart-btn {
            flex: 2;
            background-color: var(--accent-dark);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .add-to-cart-btn:hover {
            background-color: var(--text-light);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background-color: var(--light-color);
            box-shadow: var(--box-shadow);
            border-left: 4px solid transparent;
        }

        .alert-success {
            border-left-color: var(--success-color);
            color: var(--success-color);
            background-color: rgba(56, 161, 105, 0.1);
        }

        .alert-error {
            border-left-color: var(--danger-color);
            color: var,--danger-color);
            background-color: rgba(229, 62, 62, 0.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            grid-column: 1 / -1;
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid var(--accent-color);
        }

        .empty-state-icon {
            font-size: 3rem;
            color: var(--text-light);
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .empty-state-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .empty-state-text {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto 1.5rem;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .reset-btn {
            padding: 0.8rem 1.5rem;
            background-color: var(--accent-dark);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .reset-btn:hover {
            background-color: var(--text-light);
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal-container {
            background: var(--light-color);
            border-radius: var(--border-radius);
            max-width: 400px;
            width: 90vw;
            padding: 2rem;
            box-shadow: var(--box-shadow-lg);
            position: relative;
            animation: modalFadeIn 0.3s ease-out;
            border: 1px solid var(--accent-color);
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--accent-dark);
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--danger-color);
        }

        .modal-title {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .modal-price {
            margin-bottom: 1rem;
            color: var(--text-color);
            font-weight: 600;
        }

        .modal-form-group {
            margin-bottom: 1.2rem;
        }

        .modal-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .modal-input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--accent-color);
            border-radius: var(--border-radius-sm);
            font-size: 0.9rem;
            background-color: var(--light-color);
        }

        .modal-addon-option {
            display: block;
            margin-bottom: 0.5rem;
        }

        .modal-total {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 1.5rem 0;
            color: var(--text-color);
        }

        .modal-submit {
            width: 100%;
            padding: 0.9rem;
            background-color: var(--accent-dark);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-submit:hover {
            background-color: var(--text-light);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 992px) {
            .app-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
                height: auto;
                border-right: none;
                border-bottom: 1px solid var(--accent-color);
                padding: 1.5rem;
            }
            
            .main-content {
                padding: 1.5rem;
            }
            
            .search-bar {
                flex-direction: column;
                align-items: stretch;
                padding: 1.2rem;
            }
            
            .filter-form {
                grid-template-columns: repeat(2, 1fr);
            }
        }

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
            
            .menu-item-img-container {
                height: 200px;
            }
            
            .menu-item-content {
                padding: 1.2rem;
            }
            
            .menu-item-title {
                font-size: 1.1rem;
            }
            
            .menu-item-price {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                grid-template-columns: 1fr;
            }
            
            .menu-item-form {
                flex-direction: column;
            }
            
            .quantity-input, .add-to-cart-btn {
                width: 100%;
            }
            
            .logo span {
                font-size: 1.2rem;
            }
            
            .menu-item-img-container {
                height: 180px;
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
            </div>
        </div>
    </nav>

    <div class="app-container">
        <!-- Sidebar with Filters -->
        <aside class="sidebar">
            <h2 class="sidebar-title">
                <i class="fas fa-filter"></i>
                Filters
            </h2>
            
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="category" class="filter-label">Category</label>
                    <select id="category" name="category" class="filter-control select" onchange="this.form.submit()">
                        <option value=""<?= ($category_filter === '' ? ' selected' : '') ?>>All Categories</option>
                        <?php
                        $category_query = $conn->query("SELECT id, name FROM categories ORDER BY name");
                        while ($cat = $category_query->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>"<?= ($category_filter === $cat['name'] ? ' selected' : '') ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sort" class="filter-label">Sort By</label>
                    <select id="sort" name="sort" class="filter-control select">
                        <option value="name_asc" <?= $sort_by == 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                        <option value="name_desc" <?= $sort_by == 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                        <option value="price_asc" <?= $sort_by == 'price_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
                        <option value="price_desc" <?= $sort_by == 'price_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
                        <option value="popular" <?= $sort_by == 'popular' ? 'selected' : '' ?>>Most Popular</option>
                    </select>
                </div>

                <button type="submit" class="filter-btn">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </form>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Search Bar -->
            <form method="GET" class="search-bar">
                <input type="text" name="search" class="search-input" placeholder="Search menu items..." value="<?= htmlspecialchars($search_keyword) ?>">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>

            <!-- Alerts -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $_SESSION['success_message'] ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $_SESSION['error_message'] ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Menu Grid -->
            <div class="menu-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        // 查询该菜品的加料选项
                        $addons = [];
                        $addon_sql = "SELECT a.id, a.name, a.price FROM addons a JOIN menu_item_addons mia ON a.id = mia.addon_id WHERE mia.menu_item_id = " . intval($row['id']);
                        $addon_result = $conn->query($addon_sql);
                        if ($addon_result) {
                            while ($addon_row = $addon_result->fetch_assoc()) {
                                // 自动合并同类加料（如 hot/cold）
                                $addons[] = $addon_row;
                            }
                        }
                        // 查询该菜品支持的熟度选项
                        $cooking_levels = [];
                        $cooking_sql = "SELECT cl.id, cl.name FROM cooking_levels cl JOIN menu_item_cooking_levels micl ON cl.id = micl.cooking_level_id WHERE micl.menu_item_id = " . intval($row['id']);
                        $cooking_result = $conn->query($cooking_sql);
                        if ($cooking_result) {
                            while ($cl_row = $cooking_result->fetch_assoc()) {
                                $cooking_levels[] = $cl_row;
                            }
                        }
                        ?>
                        <div class="menu-item">
                            <div class="menu-item-img-container">
                                <?php
                                $img_file = !empty($row['image']) ? 'uploads/' . $row['image'] : '';
                                if (!empty($img_file) && file_exists($img_file)) {
                                    $img_src = $img_file;
                                } else {
                                    $img_src = 'uploads/default.png';
                                }
                                ?>
                                <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="menu-item-img">
                                <?php if (isset($row['is_spicy']) && $row['is_spicy']): ?>
                                    <span class="menu-item-badge">Spicy</span>
                                <?php endif; ?>
                                <span class="menu-item-category"><?= htmlspecialchars($row['category']) ?></span>
                            </div>
                            <div class="menu-item-content">
                                <div class="menu-item-header">
                                    <h3 class="menu-item-title"><?= htmlspecialchars($row['name']) ?></h3>
                                    <div class="menu-item-price">RM<?= number_format($row['price'], 2) ?></div>
                                </div>
                                <?php if(isset($row['description']) && !empty($row['description'])): ?>
                                    <p class="menu-item-desc"><?= htmlspecialchars($row['description']) ?></p>
                                <?php endif; ?>
                                <div class="menu-item-footer">
                                    <button type="button" class="add-to-cart-btn" onclick='openAddToCartModal({
                                        id: <?= json_encode($row['id']) ?>,
                                        name: <?= json_encode($row['name']) ?>,
                                        price: <?= json_encode($row['price']) ?>,
                                        addons: <?= json_encode($addons) ?>,
                                        cooking_levels: <?= json_encode($cooking_levels) ?>
                                    })'>
                                        <i class="fas fa-cart-plus"></i>
                                        <span>Add to Cart</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h3 class="empty-state-title">No Menu Items Found</h3>
                        <p class="empty-state-text">Try adjusting your search or filter criteria</p>
                        <a href="menu.php" class="reset-btn">
                            <i class="fas fa-undo"></i> Reset Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add to Cart Modal -->
    <div id="addToCartModal" class="modal-overlay">
        <div class="modal-container">
            <button class="modal-close" onclick="closeAddToCartModal()">&times;</button>
            <h3 class="modal-title" id="modalItemName"></h3>
            <div class="modal-price">Price: RM<span id="modalItemPrice"></span></div>
            <form id="addToCartForm" method="POST">
                <input type="hidden" name="item_id" id="modalItemId">
                <div class="modal-form-group">
                    <label for="modalQuantity" class="modal-label">Quantity</label>
                    <input type="number" name="quantity" id="modalQuantity" value="1" min="1" max="20" class="modal-input">
                </div>
                <div class="modal-form-group" id="modalAddonsContainer">
                    <label class="modal-label">Add-ons</label>
                    <!-- 动态生成加料选项 -->
                </div>
                <div class="modal-form-group" id="modalCookingLevelContainer" style="display:none">
                    <label for="modalCookingLevel" class="modal-label">Cooking Level</label>
                    <select name="cooking_level" id="modalCookingLevel" class="modal-input"></select>
                </div>
                <div class="modal-form-group">
                    <label for="modalRemark" class="modal-label">Remark</label>
                    <input type="text" name="remark" id="modalRemark" class="modal-input" placeholder="Any special request?">
                </div>
                <div class="modal-total">Total: RM<span id="modalTotalPrice"></span></div>
                <button type="submit" name="add_to_cart" class="modal-submit">
                    <i class="fas fa-cart-plus"></i> Add to Cart
                </button>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openAddToCartModal(item) {
            const modal = document.getElementById('addToCartModal');
            modal.style.display = 'flex';
            document.getElementById('modalItemName').textContent = item.name;
            document.getElementById('modalItemPrice').textContent = Number(item.price).toFixed(2);
            document.getElementById('modalItemId').value = item.id;
            document.getElementById('modalQuantity').value = 1;
            document.getElementById('modalRemark').value = '';
            // 动态生成加料选项
            const addonsContainer = document.getElementById('modalAddonsContainer');
            addonsContainer.innerHTML = '<label class="modal-label">Add-ons</label>';
            if (item.addons && item.addons.length > 0) {
                addonsContainer.style.display = '';
                item.addons.forEach(function(addon) {
                    if (addon && addon.name) {
                        const priceText = addon.price > 0 ? `(+RM${Number(addon.price).toFixed(2)})` : '(Free)';
                        const label = document.createElement('label');
                        label.className = 'modal-addon-option';
                        label.innerHTML = `<input type="checkbox" name="addon[]" value="${addon.id}" data-price="${addon.price}"> ${addon.name} ${priceText}`;
                        addonsContainer.appendChild(label);
                    }
                });
            } else {
                addonsContainer.style.display = 'none';
            }
            // 动态生成熟度选项
            const cookingContainer = document.getElementById('modalCookingLevelContainer');
            const cookingSelect = document.getElementById('modalCookingLevel');
            if (item.cooking_levels && item.cooking_levels.length > 0) {
                cookingContainer.style.display = '';
                cookingSelect.innerHTML = '';
                item.cooking_levels.forEach(function(cl) {
                    const opt = document.createElement('option');
                    opt.value = cl.id;
                    opt.textContent = cl.name;
                    cookingSelect.appendChild(opt);
                });
            } else {
                cookingContainer.style.display = 'none';
                cookingSelect.innerHTML = '';
            }
            updateModalTotalPrice();
            // 重新绑定事件
            setTimeout(function() {
                document.querySelectorAll('#addToCartForm input[type=checkbox]').forEach(cb => {
                    cb.addEventListener('change', updateModalTotalPrice);
                });
            }, 50);
        }

        function closeAddToCartModal() {
            document.getElementById('addToCartModal').style.display = 'none';
        }

        function updateModalTotalPrice() {
            const base = Number(document.getElementById('modalItemPrice').textContent);
            const qty = Number(document.getElementById('modalQuantity').value);
            let addonTotal = 0;
            document.querySelectorAll('#addToCartForm input[type=checkbox]:checked').forEach(cb => {
                addonTotal += Number(cb.getAttribute('data-price'));
            });
            const total = (base + addonTotal) * qty;
            document.getElementById('modalTotalPrice').textContent = total.toFixed(2);
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Quantity input validation
            document.getElementById('modalQuantity').addEventListener('change', function() {
                if (this.value < 1) this.value = 1;
                if (this.value > 20) this.value = 20;
                updateModalTotalPrice();
            });
            
            // Add-on checkbox changes
            document.querySelectorAll('#addToCartForm input[type=checkbox]').forEach(cb => {
                cb.addEventListener('change', updateModalTotalPrice);
            });
            
            // Close modal when clicking outside
            document.getElementById('addToCartModal').addEventListener('click', function(e) {
                if (e.target === this) closeAddToCartModal();
            });
            
            // Initialize total price
            updateModalTotalPrice();
        });
    </script>
</body>
</html>