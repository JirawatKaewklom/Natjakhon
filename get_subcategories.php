<?php
require_once 'config.php';

if (isset($_GET['category_id'])) {
    $category_id = intval($_GET['category_id']);
    $subcategories_query = $conn->query("SELECT * FROM subcategories WHERE category_id = '$category_id'");

    $subcategories = [];
    while ($subcategory = $subcategories_query->fetch_assoc()) {
        $subcategories[] = $subcategory;
    }

    header('Content-Type: application/json');
    echo json_encode($subcategories);
}
?>
