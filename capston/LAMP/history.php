<?php require_once 'config/db.php'; ?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>이력 조회 - 재고 관리 시스템</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav>
    <div class="nav-brand">재고 관리 시스템</div>
    <ul>
        <li><a href="index.php">재고 현황</a></li>
        <li><a href="products.php">상품 관리</a></li>
        <li><a href="stock_in.php">입고</a></li>
        <li><a href="stock_out.php">출고</a></li>
        <li><a href="history.php" class="active">이력 조회</a></li>
    </ul>
</nav>

<div class="container">
    <h2>입출고 이력 조회</h2>

    <?php
    $sql = "SELECT t.id, p.name, t.type, t.quantity, p.unit, t.note, t.created_at
            FROM transactions t
            JOIN products p ON t.product_id = p.id
            ORDER BY t.created_at DESC
            LIMIT 100";
    $result = $conn->query($sql);
    ?>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>상품명</th>
                <th>구분</th>
                <th>수량</th>
                <th>비고</th>
                <th>처리 일시</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td>
                    <?php if ($row['type'] === 'in'): ?>
                        <span class="badge badge-ok">입고</span>
                    <?php else: ?>
                        <span class="badge badge-danger">출고</span>
                    <?php endif; ?>
                </td>
                <td><?= $row['quantity'] ?> <?= htmlspecialchars($row['unit']) ?></td>
                <td><?= htmlspecialchars($row['note'] ?? '') ?></td>
                <td><?= $row['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
