<?php require_once 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>재고 관리 시스템</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav>
    <div class="nav-brand">재고 관리 시스템</div>
    <ul>
        <li><a href="index.php" class="active">재고 현황</a></li>
        <li><a href="products.php">상품 관리</a></li>
        <li><a href="stock_in.php">입고</a></li>
        <li><a href="stock_out.php">출고</a></li>
        <li><a href="history.php">이력 조회</a></li>
    </ul>
</nav>

<div class="container">
    <h2>재고 현황</h2>

    <?php
    $sql = "SELECT p.id, p.name, p.category, p.unit, p.min_stock, i.quantity
            FROM products p
            JOIN inventory i ON p.id = i.product_id
            ORDER BY p.category, p.name";
    $result = $conn->query($sql);

    $low_count = 0;
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        if ($row['quantity'] <= $row['min_stock']) $low_count++;
    }
    ?>

    <?php if ($low_count > 0): ?>
    <div class="alert alert-warning">
        ⚠️ 재고 부족 상품이 <strong><?= $low_count ?>개</strong> 있습니다.
    </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>상품명</th>
                <th>카테고리</th>
                <th>현재 재고</th>
                <th>단위</th>
                <th>최소 수량</th>
                <th>상태</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr class="<?= $row['quantity'] <= $row['min_stock'] ? 'low-stock' : '' ?>">
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><strong><?= $row['quantity'] ?></strong></td>
                <td><?= htmlspecialchars($row['unit']) ?></td>
                <td><?= $row['min_stock'] ?></td>
                <td>
                    <?php if ($row['quantity'] <= $row['min_stock']): ?>
                        <span class="badge badge-danger">부족</span>
                    <?php else: ?>
                        <span class="badge badge-ok">정상</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
