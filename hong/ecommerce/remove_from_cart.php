<?php
session_start();

if (isset($_GET['index'])) {
    $index = (int) $_GET['index'];
    if (isset($_SESSION['cart'][$index])) {
        array_splice($_SESSION['cart'], $index, 1);
    }
}

header('Location: cart.php');
?>
