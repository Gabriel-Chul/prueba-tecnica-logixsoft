<?php

require __DIR__ . '/../app/bootstrap.php';

if (!current_user()) {
    redirect('index.php');
}

$user = current_user();

?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mapa interactivo</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin="">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="map-page">
    <main class="map-shell">
        <header class="map-header">
            <div class="map-brand">
                <strong>GeoVisor</strong>
                <span>Panel Geografico</span>
            </div>
            <div class="map-actions">
                <form class="map-search" id="search-form">
                    <input id="search-place" type="text" placeholder="Buscar lugar" aria-label="Buscar lugar">
                    <button class="map-button" id="search-place-btn" type="submit">Buscar</button>
                </form>
                <button class="map-button secondary" id="locate-me" type="button">Mi ubicacion</button>
                <button class="map-button" id="add-marker" type="button">Agregar marcador</button>
                <button class="map-button secondary" id="reset-view" type="button">Reset vista</button>
                <button class="map-button danger" id="clear-markers" type="button">Limpiar</button>
                <a class="map-button secondary" href="logout.php">Cerrar sesion</a>
            </div>
        </header>

        <div id="map" class="map-canvas" aria-label="Mapa interactivo"></div>

        <div class="map-footer">
            Usuario: <?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?> · Arrastra los marcadores para reubicarlos.
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
    <script src="assets/js/map.js"></script>
</body>
</html>
