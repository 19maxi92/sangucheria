<?php
// admin/modules/pedidos/gestionar_repartidores.php
// Gestión de repartidores

require_once '../../config.php';
requireLogin();

$pdo = getConnection();

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        switch ($accion) {
            case 'agregar':
                $stmt = $pdo->prepare("
                    INSERT INTO repartidores (nombre, apellido, telefono, zona_asignada, vehiculo, activo)
                    VALUES (:nombre, :apellido, :telefono, :zona, :vehiculo, 1)
                ");
                $stmt->execute([
                    'nombre' => $_POST['nombre'],
                    'apellido' => $_POST['apellido'],
                    'telefono' => $_POST['telefono'],
                    'zona' => $_POST['zona'],
                    'vehiculo' => $_POST['vehiculo']
                ]);
                $_SESSION['mensaje'] = "Repartidor agregado exitosamente";
                break;

            case 'editar':
                $stmt = $pdo->prepare("
                    UPDATE repartidores
                    SET nombre = :nombre,
                        apellido = :apellido,
                        telefono = :telefono,
                        zona_asignada = :zona,
                        vehiculo = :vehiculo
                    WHERE id = :id
                ");
                $stmt->execute([
                    'nombre' => $_POST['nombre'],
                    'apellido' => $_POST['apellido'],
                    'telefono' => $_POST['telefono'],
                    'zona' => $_POST['zona'],
                    'vehiculo' => $_POST['vehiculo'],
                    'id' => $_POST['id']
                ]);
                $_SESSION['mensaje'] = "Repartidor actualizado exitosamente";
                break;

            case 'toggle_activo':
                $stmt = $pdo->prepare("UPDATE repartidores SET activo = NOT activo WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['mensaje'] = "Estado del repartidor actualizado";
                break;

            case 'eliminar':
                $stmt = $pdo->prepare("DELETE FROM repartidores WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['mensaje'] = "Repartidor eliminado";
                break;
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Obtener repartidores
$repartidores = $pdo->query("
    SELECT r.*,
           COUNT(p.id) as pedidos_asignados
    FROM repartidores r
    LEFT JOIN pedidos p ON r.id = p.repartidor_id AND DATE(p.fecha_entrega) = CURDATE()
    GROUP BY r.id
    ORDER BY r.nombre ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Repartidores | Santa Catalina</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-6">

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-users text-blue-500 mr-2"></i>
                Gestión de Repartidores
            </h1>
            <div class="flex space-x-3">
                <button onclick="mostrarFormulario()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-plus mr-2"></i>Nuevo Repartidor
                </button>
                <a href="delivery_simple.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-map-marked-alt mr-2"></i>Mapa Delivery
                </a>
                <a href="ver_pedidos.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>

        <!-- Mensajes -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($_SESSION['mensaje']) ?>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-gray-700"><?= count($repartidores) ?></div>
                <div class="text-sm text-gray-500">Total Repartidores</div>
            </div>
            <div class="bg-green-100 p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-green-700">
                    <?= count(array_filter($repartidores, fn($r) => $r['activo'])) ?>
                </div>
                <div class="text-sm text-green-600">Activos</div>
            </div>
            <div class="bg-yellow-100 p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-yellow-700">
                    <?= count(array_filter($repartidores, fn($r) => !$r['activo'])) ?>
                </div>
                <div class="text-sm text-yellow-600">Inactivos</div>
            </div>
            <div class="bg-blue-100 p-4 rounded-lg shadow text-center">
                <div class="text-2xl font-bold text-blue-700">
                    <?= array_sum(array_column($repartidores, 'pedidos_asignados')) ?>
                </div>
                <div class="text-sm text-blue-600">Pedidos Hoy</div>
            </div>
        </div>

        <!-- Formulario (oculto por defecto) -->
        <div id="formulario" class="hidden bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4" id="tituloForm">Nuevo Repartidor</h2>
            <form method="POST" id="formRepartidor">
                <input type="hidden" name="accion" id="accion" value="agregar">
                <input type="hidden" name="id" id="repartidor_id">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre:</label>
                        <input type="text" name="nombre" id="nombre" required
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Apellido:</label>
                        <input type="text" name="apellido" id="apellido" required
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono:</label>
                        <input type="tel" name="telefono" id="telefono"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Zona Asignada:</label>
                        <input type="text" name="zona" id="zona" placeholder="Ej: Centro, Nueva Córdoba"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Vehículo:</label>
                        <select name="vehiculo" id="vehiculo"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccionar...</option>
                            <option value="Moto">Moto</option>
                            <option value="Bicicleta">Bicicleta</option>
                            <option value="Auto">Auto</option>
                            <option value="A pie">A pie</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="ocultarFormulario()"
                            class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-save mr-2"></i>Guardar
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Repartidores -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold">Lista de Repartidores</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zona</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehículo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pedidos Hoy</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($repartidores as $rep): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">
                                        <?= htmlspecialchars($rep['nombre'] . ' ' . $rep['apellido']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= htmlspecialchars($rep['telefono'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= htmlspecialchars($rep['zona_asignada'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= htmlspecialchars($rep['vehiculo'] ?? '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($rep['activo']): ?>
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">
                                            Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full font-semibold">
                                        <?= $rep['pedidos_asignados'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick='editarRepartidor(<?= json_encode($rep) ?>)'
                                                class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="accion" value="toggle_activo">
                                            <input type="hidden" name="id" value="<?= $rep['id'] ?>">
                                            <button type="submit"
                                                    class="text-yellow-600 hover:text-yellow-900 bg-yellow-50 hover:bg-yellow-100 px-2 py-1 rounded">
                                                <i class="fas fa-<?= $rep['activo'] ? 'ban' : 'check' ?>"></i>
                                            </button>
                                        </form>
                                        <button onclick="eliminarRepartidor(<?= $rep['id'] ?>, '<?= htmlspecialchars($rep['nombre'] . ' ' . $rep['apellido']) ?>')"
                                                class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-2 py-1 rounded">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($repartidores)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-users text-4xl mb-4"></i>
                                    <p>No hay repartidores registrados</p>
                                    <button onclick="mostrarFormulario()"
                                            class="mt-4 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                                        Agregar Primer Repartidor
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function mostrarFormulario() {
            document.getElementById('formulario').classList.remove('hidden');
            document.getElementById('tituloForm').textContent = 'Nuevo Repartidor';
            document.getElementById('accion').value = 'agregar';
            document.getElementById('formRepartidor').reset();
        }

        function ocultarFormulario() {
            document.getElementById('formulario').classList.add('hidden');
        }

        function editarRepartidor(rep) {
            document.getElementById('formulario').classList.remove('hidden');
            document.getElementById('tituloForm').textContent = 'Editar Repartidor';
            document.getElementById('accion').value = 'editar';
            document.getElementById('repartidor_id').value = rep.id;
            document.getElementById('nombre').value = rep.nombre;
            document.getElementById('apellido').value = rep.apellido;
            document.getElementById('telefono').value = rep.telefono || '';
            document.getElementById('zona').value = rep.zona_asignada || '';
            document.getElementById('vehiculo').value = rep.vehiculo || '';

            // Scroll al formulario
            document.getElementById('formulario').scrollIntoView({ behavior: 'smooth' });
        }

        function eliminarRepartidor(id, nombre) {
            if (confirm(`¿Estás seguro de eliminar al repartidor "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
