<?php
require_once 'config/db.php';

$msg = '';

// 등록
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    $name      = $conn->real_escape_string(trim($_POST['name']));
    $category  = $conn->real_escape_string(trim($_POST['category']));
    $unit      = $conn->real_escape_string(trim($_POST['unit']));
    $min_stock = (int)$_POST['min_stock'];

    $conn->query("INSERT INTO products (name, category, unit, min_stock) VALUES ('$name','$category','$unit',$min_stock)");
    $pid = $conn->insert_id;
    $conn->query("INSERT INTO inventory (product_id, quantity) VALUES ($pid, 0)");
    $msg = '<div class="alert alert-success">상품이 등록되었습니다.</div>';
}

// 삭제
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM products WHERE id=$id");
    $msg = '<div class="alert alert-success">상품이 삭제되었습니다.</div>';
}

$products = $conn->query("SELECT p.*, i.quantity FROM products p JOIN inventory i ON p.id=i.product_id ORDER BY p.id DESC");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>상품 관리 - 재고 관리 시스템</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav>
    <div class="nav-brand">재고 관리 시스템</div>
    <ul>
        <li><a href="index.php">재고 현황</a></li>
        <li><a href="products.php" class="active">상품 관리</a></li>
        <li><a href="stock_in.php">입고</a></li>
        <li><a href="stock_out.php">출고</a></li>
        <li><a href="history.php">이력 조회</a></li>
    </ul>
</nav>

<div class="container">
    <h2>상품 관리</h2>
    <?= $msg ?>

    <div class="card">
        <h3>신규 상품 등록</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <label>상품명</label>
                <input type="text" name="name" required placeholder="상품명 입력">
            </div>
            <div class="form-row">
                <label>카테고리</label>
                <input type="text" name="category" required placeholder="카테고리 입력">
            </div>
            <div class="form-row">
                <label>단위</label>
                <input type="text" name="unit" value="개" required>
            </div>
            <div class="form-row">
                <label>최소 재고 수량</label>
                <input type="number" name="min_stock" value="10" min="0" required>
            </div>
            <button type="submit" class="btn btn-primary">등록</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th><th>상품명</th><th>카테고리</th><th>단위</th>
                <th>현재재고</th><th>최소수량</th><th>관리</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $products->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><?= htmlspecialchars($row['unit']) ?></td>
                <td><?= $row['quantity'] ?></td>
                <td><?= $row['min_stock'] ?></td>
                <td>
                    <a href="products.php?delete=<?= $row['id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('삭제하시겠습니까?')">삭제</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
