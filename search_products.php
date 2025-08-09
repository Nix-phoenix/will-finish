<?php
include 'includes/auth.php';
include 'db/connection.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
if ($search !== '') {
    $search_escaped = $conn->real_escape_string($search);
    $where = "WHERE p_name LIKE '%$search_escaped%' OR type LIKE '%$search_escaped%' OR unit LIKE '%$search_escaped%' OR shelf LIKE '%$search_escaped%'";
}
$product_sql = "SELECT p_id, p_name, price, qty, unit, shelf, type FROM Product $where";
$product_result = $conn->query($product_sql);
while($row = $product_result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['p_id']) ?></td>
    <td><?= htmlspecialchars($row['p_name']) ?></td>
    <td><?= htmlspecialchars($row['price']) ?></td>
    <td><?= htmlspecialchars($row['qty']) ?></td>
    <td><?= htmlspecialchars($row['unit']) ?></td>
    <td><?= htmlspecialchars($row['shelf']) ?></td>
    <td><?= htmlspecialchars($row['type']) ?></td>
    <td>
        <button type="button" class="dashboard-edit-btn openEditProductModal"
            data-pid="<?= htmlspecialchars($row['p_id']) ?>"
            data-pname="<?= htmlspecialchars($row['p_name']) ?>"
            data-price="<?= htmlspecialchars($row['price']) ?>"
            data-qty="<?= htmlspecialchars($row['qty']) ?>"
            data-unit="<?= htmlspecialchars($row['unit']) ?>"
            data-shelf="<?= htmlspecialchars($row['shelf']) ?>"
            data-type="<?= htmlspecialchars($row['type']) ?>"
        >ແກ້ໄຂ</button>
    </td>
</tr>
<?php endwhile; ?> 