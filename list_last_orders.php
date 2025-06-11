<?php
require_once 'db.php';
session_start();

// Simulamos cargar pedidos desde la base de datos
// Aquí debes reemplazarlo con tu consulta real, ejemplo:
$stmt = $pdo->prepare("SELECT id, order_number, name, total_price FROM orders ORDER BY id DESC LIMIT 20");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Pedidos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 p-6">
<div class="max-w-5xl mx-auto bg-white p-8 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Últimos Pedidos</h1>
        <a href="index_old.php" class="text-blue-600 hover:underline">← Volver al Home</a>
    </div>

    <form id="ordersForm" method="post">
        <div class="flex mb-4 gap-4">
            <button type="button" onclick="selectAll()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                Seleccionar todos
            </button>
            <button type="button" onclick="deselectAll()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                Deseleccionar todos
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full border rounded-lg">
                <thead class="bg-gray-200">
                <tr>
                    <th class="px-4 py-2"></th>
                    <th class="px-4 py-2 text-left">ID</th>
                    <th class="px-4 py-2 text-left">Número de Orden</th>
                    <th class="px-4 py-2 text-left">Nombre</th>
                    <th class="px-4 py-2 text-left">Importe</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr class="border-t">
                        <td class="px-4 py-2 text-center">
                            <input type="checkbox" name="order_ids[]" value="<?= htmlspecialchars($order['id']) ?>">
                        </td>
                        <td class="px-4 py-2"><?= htmlspecialchars($order['id']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($order['order_number']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($order['name']) ?></td>
                        <td class="px-4 py-2">$<?= number_format((float)$order['total_price'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            <button type="button" onclick="submitForm()" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded text-lg">
                Enviar seleccionados
            </button>
        </div>
    </form>
</div>

<script>
    function selectAll() {
        document.querySelectorAll('input[name="order_ids[]"]').forEach(el => el.checked = true);
    }

    function deselectAll() {
        document.querySelectorAll('input[name="order_ids[]"]').forEach(el => el.checked = false);
    }

    function submitForm() {
        const selected = [];
        document.querySelectorAll('input[name="order_ids[]"]:checked').forEach(el => {
            selected.push(el.value);
        });

        if (selected.length === 0) {
            alert('Debes seleccionar al menos un pedido.');
            return;
        }

        // Crear el JSON
        const jsonData = JSON.stringify({ orders: selected });

        // Enviar el JSON por POST usando Fetch API
        fetch('procesar_envio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: jsonData
        })
            .then(response => response.text())
            .then(data => {
                alert('Respuesta del servidor: ' + data);
                // Opcional: limpiar selección o recargar
            })
            .catch(error => {
                console.error('Error al enviar:', error);
                alert('Error al enviar la información.');
            });
    }
</script>
</body>
</html>

