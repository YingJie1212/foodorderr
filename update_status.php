<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];

    $allowed = ['pending', 'preparing', 'completed', 'cancelled'];
    if (!in_array($status, $allowed)) {
        die("非法状态值");
    }

    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $order_id);

    if ($stmt->execute()) {
        header("Location: admin_orders.php?msg=success");
    } else {
        die("更新失败: " . $stmt->error); 
    }
    exit;
}
