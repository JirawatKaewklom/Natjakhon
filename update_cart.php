<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $index = intval($_POST['index']);
    $quantity = intval($_POST['quantity']);
    
    if (isset($_SESSION['cart'][$index])) {
        $_SESSION['cart'][$index]['quantity'] = $quantity;
    }
    
    header('Location: cart.php');
    exit();
}
?>