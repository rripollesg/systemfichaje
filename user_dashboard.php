<?php
session_start();

// Verificar acceso
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['rol'] !== 'usuario') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
        }
        #accessButton {
            width: 200px;
            height: 200px;
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.3s ease;
            background-color: red;
        }
        #accessButton:hover {
            transform: scale(1.1);
        }
        .hidden {
            display: none;
        }
        #locationInfo {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="text-center mb-4">
        <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?></h2>
        <div id="locationInfo" class="hidden">
            <small>Ubicación registrada:</small>
            <div id="coordinates" class="fw-bold"></div>
        </div>
    </div>

    <button id="accessButton">Entrada</button>

    <div class="position-fixed top-0 end-0 p-3" style="z-index: 11;">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">Registro Exitoso</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const accessButton = document.getElementById('accessButton');
        const toastElement = document.getElementById('liveToast');
        let isEntry = true;
        let currentLocation = null;

        // Obtener ubicación del usuario
        function getLocation() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject('Geolocalización no soportada');
                }
                
                navigator.geolocation.getCurrentPosition(
                    position => {
                        const loc = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        document.getElementById('coordinates').textContent = 
                            `${loc.lat.toFixed(6)}, ${loc.lng.toFixed(6)}`;
                        document.getElementById('locationInfo').classList.remove('hidden');
                        resolve(loc);
                    },
                    error => {
                        console.error('Error obteniendo ubicación:', error);
                        reject('No se pudo obtener la ubicación');
                    }
                );
            });
        }

        // Mostrar notificación
        async function showNotification(type) {
            try {
                const location = await getLocation();
                const timestamp = new Date().toISOString();
                const toastBody = toastElement.querySelector('.toast-body');
                const toast = new bootstrap.Toast(toastElement, { delay: 3000 });

                const response = await fetch('save-access.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        userId: <?php echo $_SESSION['user_id']; ?>,
                        type: type,
                        timestamp: timestamp,
                        latitude: location.lat,
                        longitude: location.lng
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    toastBody.textContent = `Registro de ${type} exitoso`;
                    toastElement.querySelector('.toast-header').className = 'toast-header bg-success text-white';
                } else {
                    toastBody.textContent = `Error: ${result.message}`;
                    toastElement.querySelector('.toast-header').className = 'toast-header bg-danger text-white';
                }
                
                toast.show();
            } catch (error) {
                console.error('Error:', error);
                toastBody.textContent = 'Error al registrar el acceso';
                toastElement.querySelector('.toast-header').className = 'toast-header bg-danger text-white';
                toast.show();
            }
        }

        // Manejar clic en el botón
        accessButton.addEventListener('click', async () => {
            const type = isEntry ? 'entrada' : 'salida';
            await showNotification(type);
            
            // Alternar estado y texto del botón
            isEntry = !isEntry;
            accessButton.textContent = isEntry ? 'Entrada' : 'Salida';
            accessButton.style.backgroundColor = isEntry ? '#28a745' : '#dc3545';
        });

        // Cargar ubicación inicial
        getLocation().catch(error => {
            console.warn(error);
            document.getElementById('locationInfo').classList.add('hidden');
        });
    </script>
</body>
</html>