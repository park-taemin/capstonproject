<?php
require_once 'config/db.php';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $quantity   = (int)$_POST['quantity'];
    $note       = $conn->real_escape_string(trim($_POST['note']));

    if ($quantity > 0) {
        $conn->query("UPDATE inventory SET quantity = quantity + $quantity WHERE product_id = $product_id");
        $conn->query("INSERT INTO transactions (product_id, type, quantity, note) VALUES ($product_id, 'in', $quantity, '$note')");
        $msg = '<div class="alert alert-success">입고 처리 완료되었습니다.</div>';
    } else {
        $msg = '<div class="alert alert-warning">수량은 1 이상이어야 합니다.</div>';
    }
}

$products = $conn->query("SELECT p.id, p.name, p.unit, i.quantity FROM products p JOIN inventory i ON p.id=i.product_id ORDER BY p.name");
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>입고 처리 - 재고 관리 시스템</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav>
    <div class="nav-brand">재고 관리 시스템</div>
    <ul>
        <li><a href="index.php">재고 현황</a></li>
        <li><a href="products.php">상품 관리</a></li>
        <li><a href="stock_in.php" class="active">입고</a></li>
        <li><a href="stock_out.php">출고</a></li>
        <li><a href="history.php">이력 조회</a></li>
    </ul>
</nav>

<div class="container">
    <h2>입고 처리</h2>
    <?= $msg ?>

    <div class="card">
        <form method="POST">
            <div class="form-row">
                <label>상품 선택</label>
                <select name="product_id" required>
                    <option value="">-- 상품 선택 --</option>
                    <?php while ($p = $products->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['name']) ?> (현재: <?= $p['quantity'] ?><?= htmlspecialchars($p['unit']) ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-row">
                <label>입고 수량</label>
                <input type="number" name="quantity" min="1" required placeholder="입고 수량 입력">
            </div>
            <div class="form-row">
                <label>비고</label>
                <input type="text" name="note" placeholder="입고 사유 또는 메모">
            </div>
            <button type="submit" class="btn btn-primary">입고 처리</button>
            <a href="index.php" class="btn btn-secondary">취소</a>
        </form>
    </div>
</div>
</body>
</html>
