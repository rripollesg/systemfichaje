<?php
session_start();
//require_once 'security.php';
//verificarAcceso('admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
        header { background-color: #333; color: white; padding: 10px 20px; text-align: center; }
        main { max-width: 1000px; margin: 20px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
        .late { background-color: #ffcccc; }
        td.details-control::after { content: "+"; cursor: pointer; width: 20px; text-align: center; }
        tr.shown td.details-control::after { content: "-"; }
        .map-container { height: 300px; width: 100%; }
    </style>
</head>
<body>
    <header><h1>Panel de Administración</h1></header>
    <main class="container">
        <h2 class="mb-4">Registros de Entrada/Salida</h2>
        <table id="accessTable" class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th></th>
                    <th>Usuario</th>
                    <th>NIE</th>
                    <th>Tipo</th>
                    <th>Hora</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

		 <a class="nav-link text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-left"></i> Cerrar Sesión
                            </a>
        <h2 class="mb-4">Gráfica de Llegadas</h2>
        <canvas id="arrivalChart"></canvas>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />

    <script>
        async function loadAccessLogs() {
            try {
                const response = await fetch('get-access-logs.php', { credentials: 'include' });
                const logs = await response.json();
                const tableBody = document.querySelector('#accessTable tbody');
                tableBody.innerHTML = '';

                if (logs.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No hay registros disponibles.</td></tr>';
                    return;
                }

                const referenceTime = new Date();
                referenceTime.setHours(8, 0, 0, 0);

                logs.forEach(log => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="details-control"></td>
                        <td>${log.username || 'Usuario desconocido'}</td>
                        <td>${log.nie || 'NIE no disponible'}</td>
                        <td>${log.type}</td>
                        <td>${new Date(log.timestamp).toLocaleString()}</td>
                    `;

                    if (log.type === 'entrada' && new Date(log.timestamp) >= referenceTime) {
                        row.classList.add('late');
                    }

                    tableBody.appendChild(row);
                });

                $('#accessTable').DataTable({
                    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json' },
                    dom: 'Bfrtip',
                    buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                    order: [[4, 'desc']],
                    columns: [
                        { orderable: false, className: 'details-control' },
                        null, null, null, null
                    ]
                });

                // Manejar expansión de filas
                $('#accessTable').on('click', 'td.details-control', async function () {
                    const tr = $(this).closest('tr');
                    const row = $('#accessTable').DataTable().row(tr);
                    const log = logs[row.index()];

                    if (row.child.isShown()) {
                        row.child.hide();
                        tr.removeClass('shown');
                    } else {
                        row.child(`
                            <div class="map-container" id="map-${log.log_id}"></div>
                        `).show();
                        tr.addClass('shown');
                        initializeMap(log.latitude, log.longitude, log.log_id);
                    }
                });
            } catch (error) {
                console.error("Error al cargar los registros:", error);
            }
        }

        function initializeMap(lat, lng, logId) {
            const parsedLat = parseFloat(lat);
            const parsedLng = parseFloat(lng);

            if (isNaN(parsedLat) || isNaN(parsedLng)) {
                console.warn("Coordenadas inválidas:", lat, lng);
                return;
            }

            const map = L.map(`map-${logId}`).setView([parsedLat, parsedLng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
            L.marker([parsedLat, parsedLng]).addTo(map).bindPopup('Ubicación registrada');
        }

        // Resto del código (gráfica, registro de usuario) se mantiene igual
        window.onload = loadAccessLogs;
    </script>
</body>
</html>