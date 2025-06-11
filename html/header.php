<?php global $client_id, $shop; ?>
<!DOCTYPE html>
<html>
<head>
    <title>QMF V4</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
<main>

    <div class="bg-blue-600 p-4 flex justify-between items-center mb-8">
        <div class="flex items-center space-x-6">
            <a href="index.php?shop=<?= urlencode($shop) ?>&host=<?= urlencode($_GET['host']) ?>" class="text-white font-semibold hover:underline">
                Home
            </a>
            <a href="preferences.php?shop=<?= urlencode($shop) ?>&host=<?= urlencode($_GET['host']) ?>" class="text-white font-semibold hover:underline">
                Configuración
            </a>
            <a href="listado_pedidos.php?shop=<?= urlencode($shop) ?>&host=<?= urlencode($_GET['host']) ?>" class="text-white font-semibold hover:underline">
                Órdenes
            </a>
        </div>
    </div>