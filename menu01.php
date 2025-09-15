<?php
if (!isset($shop_domain)) {
    $shop_domain = $_GET['shop'] ?? '';
}
if (!isset($_GET['host'])) {
    echo '<!-- Advertencia: falta parámetro "host" -->';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Document</title>
</head>
<body>

<!-- Barra de navegación -->
<nav class="bg-white border-b border-gray-200 mb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <span class="text-xl font-bold text-blue-600">Quiero Mi Factura App Shopify</span>
                </div>
                <div class="hidden sm:-my-px sm:ml-6 sm:flex sm:space-x-8">
                    <a href="preferences.php?shop=<?= urlencode($shop_domain) ?>&host=<?= urlencode($_GET['host']) ?>" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-700 hover:border-blue-500 hover:text-blue-600">
                        Home
                    </a>
                    <a href="preferences.php?shop=<?= urlencode($shop_domain) ?>&host=<?= urlencode($_GET['host']) ?>" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-700 hover:border-blue-500 hover:text-blue-600">
                        Configuración
                    </a>
                    <a href="listado_pedidos.php?shop=<?= urlencode($shop_domain) ?>&host=<?= urlencode($_GET['host']) ?>" class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-700 hover:border-blue-500 hover:text-blue-600">
                        Órdenes
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
</body>
</html>
