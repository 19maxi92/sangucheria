<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes Fijos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Clientes Fijos</h2>
        <a href="index.php" class="btn btn-secondary">Volver a Pedidos</a>
    </div>

    <h4>Lista de Clientes Fijos</h4>
    <div id="listaClientes" class="mb-4"></div>

    <div class="text-center mb-4">
        <button class="btn btn-primary" onclick="mostrarFormulario()">➕ Agregar Cliente Fijo</button>
    </div>

    <!-- Formulario oculto para agregar cliente -->
    <div id="formularioCliente" class="border rounded p-3 bg-white" style="display:none;">
        <h5>Nuevo Cliente Fijo</h5>
        <form id="formNuevoCliente">
            <div class="mb-2">
                <label>Nombre:</label>
                <input type="text" class="form-control" name="nombre" required>
            </div>
            <div class="mb-2">
                <label>Apellido:</label>
                <input type="text" class="form-control" name="apellido" required>
            </div>
            <div class="mb-2">
                <label>N° de Contacto:</label>
                <input type="text" class="form-control" name="contacto" required>
            </div>
            <div class="mb-2">
                <label>Dirección:</label>
                <input type="text" class="form-control" name="direccion" required>
            </div>
            <div class="mb-2">
                <label>Modalidad:</label>
                <input type="text" class="form-control" name="modalidad" required>
            </div>
            <div class="mb-2">
                <label>Observación:</label>
                <textarea class="form-control" name="observacion" rows="3" required></textarea>
            </div>
            <div class="mb-2">
                <label>Cantidad de Sándwiches:</label>
                <input type="number" class="form-control" name="cantidad" min="1" value="1" required>
            </div>
            <button type="submit" class="btn btn-success">Guardar Cliente</button>
            <button type="button" class="btn btn-secondary ms-2" onclick="cerrarFormulario()">Cancelar</button>
        </form>
    </div>
</div>

<script>
// Cargar clientes fijos desde localStorage
function cargarClientes() {
    const contenedor = document.getElementById('listaClientes');
    contenedor.innerHTML = '';
    const clientes = JSON.parse(localStorage.getItem('clientesFijos')) || [];

    if (clientes.length === 0) {
        contenedor.innerHTML = '<div class="alert alert-warning">No hay clientes fijos cargados.</div>';
        return;
    }

    clientes.forEach((cliente, index) => {
        const pedidos = cliente.pedidos || 1; // Si no tiene pedidos, asumimos que tiene 1 por defecto
        const cantidadSanguches = pedidos * cliente.cantidad;
        const planchas = (cantidadSanguches / 24).toFixed(2); // Calcular planchas
        const div = document.createElement('div');
        div.className = 'border rounded p-3 mb-2 bg-white';
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div><strong>${cliente.nombre} ${cliente.apellido}</strong> - Pedidos: ${pedidos} - Planchas: ${planchas}</div>
                <div>
                    <button class="btn btn-outline-success btn-sm" onclick="cambiarPedidos(${index}, 1)">+</button>
                    <input type="number" class="form-control d-inline-block text-center mx-1" id="pedidos_${index}" style="width:60px;" min="1" value="${pedidos}">
                    <button class="btn btn-outline-danger btn-sm" onclick="cambiarPedidos(${index}, -1)">-</button>
                    <button class="btn btn-primary btn-sm ms-2" onclick="realizarPedido(${index})">Realizar Pedido</button>
                    <button class="btn btn-danger btn-sm ms-2" onclick="eliminarCliente(${index})">Eliminar Cliente</button>
                </div>
            </div>
        `;
        contenedor.appendChild(div);
    });
}

// Sumar o restar cantidad de pedidos
function cambiarPedidos(index, cambio) {
    const input = document.getElementById(`pedidos_${index}`);
    let pedidos = parseInt(input.value) + cambio;
    if (pedidos < 1) pedidos = 1;
    input.value = pedidos;
    actualizarCliente(index, pedidos);
}

// Actualizar cliente en localStorage
function actualizarCliente(index, pedidos) {
    const clientes = JSON.parse(localStorage.getItem('clientesFijos')) || [];
    clientes[index].pedidos = pedidos; // Actualiza el número de pedidos
    localStorage.setItem('clientesFijos', JSON.stringify(clientes));
    cargarClientes(); // Recargar la lista de clientes
}

// Confirmar el pedido
function realizarPedido(index) {
    const clientes = JSON.parse(localStorage.getItem('clientesFijos')) || [];
    const cliente = clientes[index];
    const cantidad = document.getElementById(`pedidos_${index}`).value;

    // Aquí se podría guardar el pedido en otro array o enviarlo al backend
    alert(`Pedido confirmado:\n${cantidad} x ${cliente.cantidad} sándwiches para ${cliente.nombre} ${cliente.apellido}`);
}

// Eliminar cliente fijo
function eliminarCliente(index) {
    const clientes = JSON.parse(localStorage.getItem('clientesFijos')) || [];
    clientes.splice(index, 1);  // Eliminar el cliente de la lista
    localStorage.setItem('clientesFijos', JSON.stringify(clientes));
    alert('Cliente eliminado correctamente.');
    cargarClientes();  // Recargar la lista después de eliminar
}

// Mostrar formulario de nuevo cliente
function mostrarFormulario() {
    document.getElementById('formularioCliente').style.display = 'block';
}

// Ocultar formulario
function cerrarFormulario() {
    document.getElementById('formularioCliente').style.display = 'none';
}

document.getElementById('formNuevoCliente').addEventListener('submit', function(event) {
    event.preventDefault();

    const datos = new FormData(this);
    const cliente = {
        nombre: datos.get('nombre'),
        apellido: datos.get('apellido'),
        contacto: datos.get('contacto'),
        direccion: datos.get('direccion'),
        modalidad: datos.get('modalidad'),
        observacion: datos.get('observacion'),
        cantidad: parseInt(datos.get('cantidad')),
        pedidos: 1, // Inicialmente tiene 1 pedido cargado
        pedido: 'Pedido Fijo'  // Pedido asignado por defecto
    };

    const clientes = JSON.parse(localStorage.getItem('clientesFijos')) || [];
    clientes.push(cliente);
    localStorage.setItem('clientesFijos', JSON.stringify(clientes));

    alert('✅ Cliente fijo guardado correctamente.');
    cerrarFormulario();
    cargarClientes();
});

cargarClientes();
</script>

</body>
</html>
