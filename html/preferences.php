<?php
include_once '../includes/utils.php';
include_once 'header.php';
?>

    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">

        <h2 class="text-2xl font-semibold mb-4">Configuración QMF4 Shopify</h2>



        <?php if (!empty($mensaje)): ?>
            <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Usuario</label>
                <input type="text" name="usuario" value="<?= htmlspecialchars($row['qmf4_user'] ?? '') ?>" class="w-full mt-1 p-2 border rounded shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">RFC</label>
                <input type="text" name="rfc" value="<?= htmlspecialchars($row['rfc'] ?? '') ?>" class="w-full mt-1 p-2 border rounded shadow-sm">
            </div>



            <div>
                <label class="block text-sm font-medium text-gray-700">Uso de CFDI</label>
                <input type="text" name="uso_cfdi" value="<?= htmlspecialchars($shop['uso_cfdi'] ?? 'G03') ?>" class="w-full mt-1 p-2 border rounded shadow-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">URL de Sandbox</label>
                <input type="url" readonly name="url_testing" value="<?= htmlspecialchars($url_sandbox ?? '') ?>" class="w-full mt-1 p-2 border rounded shadow-sm read-only:bg-gray-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">URL de Producción</label>
                <input type="url" readonly name="url_prod" value="<?= htmlspecialchars($url_prod ?? '') ?>" class="w-full mt-1 p-2 border rounded shadow-sm read-only:bg-gray-200">
            </div>

            <div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                    Guardar
                </button>
            </div>
        </form>
    </div>
<?php
include_once 'footer.php';
