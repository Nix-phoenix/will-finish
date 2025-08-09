<?php
include 'includes/auth.php';
include 'db/connection.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$inventory_where = '';
if ($search !== '') {
    $search_escaped = $conn->real_escape_string($search);
    $inventory_where = "WHERE p.p_name LIKE '%$search_escaped%' OR p.type LIKE '%$search_escaped%' OR p.unit LIKE '%$search_escaped%' OR p.shelf LIKE '%$search_escaped%'";
}
$inventory_sql = "
    SELECT 
        p.p_name,
        p.qty,
        IFNULL((SELECT SUM(pod.qty) FROM PurchaseOrderDetail pod WHERE pod.p_id = p.p_id), 0)
        - IFNULL((SELECT SUM(sd.qty) FROM SellDetail sd WHERE sd.p_id = p.p_id), 0) AS qty_left
    FROM Product p
    $inventory_where
    ORDER BY qty_left DESC
    LIMIT 10
";
$inventory_result = $conn->query($inventory_sql);
while($row = $inventory_result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['p_name']) ?></td>
    <td><?= htmlspecialchars($row['qty']) ?></td>
    <td><?= htmlspecialchars($row['qty_left']) ?></td>
    <td><button class="dashboard-edit-btn">View</button></td>
</tr>
<?php endwhile; ?> 