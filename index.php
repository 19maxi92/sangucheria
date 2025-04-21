<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Fábrica de Sánguches - Tomar Pedido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">

        <!-- Botones alineados a la izquierda -->
        <div>
            <a href="clientes_fijos.php" class="btn btn-success me-2">Clientes Fijos</a>
            <a href="ver_pedidos.php" class="btn btn-primary">Ver Pedidos</a>
        </div>

        <h2>Tomar Pedido</h2>
    </div>

    <form id="pedidoForm">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="nombre" class="form-label">Nombre:</label>
                <input type="text" class="form-control" name="nombre" required>
            </div>
            <div class="col-md-6">
                <label for="apellido" class="form-label">Apellido:</label>
                <input type="text" class="form-control" name="apellido" required>
            </div>
            <div class="col-md-6">
                <label for="cantidad" class="form-label">Cantidad de Sánguches:</label>
                <input type="number" class="form-control" name="cantidad" id="cantidad" min="1" required>
            </div>
            <div class="col-md-6">
                <label for="planchas" class="form-label">Planchas:</label>
                <input type="text" class="form-control" name="planchas" id="planchas" readonly>
            </div>
            <div class="col-md-6">
                <label for="contacto" class="form-label">Número de Contacto:</label>
                <input type="text" class="form-control" name="contacto" required>
            </div>
            <div class="col-md-6">
                <label for="direccion" class="form-label">Dirección:</label>
                <input type="text" class="form-control" name="direccion" required>
            </div>
            <div class="col-md-6">
                <label for="modalidad" class="form-label">Modalidad:</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="modalidad" id="retiro" value="Retira" required>
                    <label class="form-check-label" for="retiro">Retira</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="modalidad" id="envio" value="Envío" required>
                    <label class="form-check-label" for="envio">Envío</label>
                </div>
            </div>
            <div class="col-12">
                <label for="observaciones" class="form-label">Observaciones:</label>
                <textarea class="form-control" name="observaciones" rows="3"></textarea>
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-success">Guardar Pedido</button>
            </div>
        </div>
    </form>
</div>

<script>
// Calcular planchas en base a la cantidad
document.getElementById('cantidad').addEventListener('input', function() {
    const cantidad = parseInt(this.value) || 0;
    const planchas = (cantidad / 24).toFixed(2);
    document.getElementById('planchas').value = planchas;
});

// Enviar pedido sin recargar
document.getElementById('pedidoForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const form = event.target;
    const datos = new FormData(form);

    fetch('guardar_pedido.php', {
        method: 'POST',
        body: datos
    })
    .then(response => response.text())
    .then(data => {
        const alerta = document.createElement('div');
        alerta.className = 'alert alert-success text-center';
        alerta.innerText = '✅ Pedido tomado correctamente.';
        document.body.prepend(alerta);

        setTimeout(() => {
            alerta.remove();
        }, 2000);

        form.reset();
        document.getElementById('planchas').value = '';
    })
    .catch(error => {
        alert('❌ Error al guardar el pedido.');
        console.error('Error:', error);
    });
});
</script>

</body>
</html>
