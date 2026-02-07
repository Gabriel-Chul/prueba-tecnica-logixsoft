document.addEventListener('DOMContentLoaded', () => {
    const mapElement = document.getElementById('map');
    if (!mapElement || typeof L === 'undefined') {
        return;
    }

    const defaultView = {
        center: [9.9281, -84.0907],
        zoom: 12,
    };

    const map = L.map('map', {
        zoomControl: true,
        minZoom: 3,
    }).setView(defaultView.center, defaultView.zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const storageKey = 'geoVisorMarkers';
    let markers = [];
    let userMarker = null;
    let searchMarker = null;

    const markerIcon = L.icon({
        iconUrl: 'assets/img/marcador.png',
        iconSize: [36, 36],
        iconAnchor: [18, 34],
        popupAnchor: [0, -28],
    });

    const userIcon = L.icon({
        iconUrl: 'assets/img/mi-posicion.png',
        iconSize: [32, 32],
        iconAnchor: [16, 16],
    });

    const saveMarkers = () => {
        const data = markers.map((marker) => {
            const position = marker.getLatLng();
            return { lat: position.lat, lng: position.lng };
        });
        localStorage.setItem(storageKey, JSON.stringify(data));
    };

    const clearMarkers = () => {
        markers.forEach((marker) => marker.remove());
        markers = [];
        if (searchMarker) {
            searchMarker.remove();
            searchMarker = null;
        }
        saveMarkers();
    };

    const addMarker = (lat, lng) => {
        const marker = L.marker([lat, lng], {
            draggable: true,
            icon: markerIcon,
        }).addTo(map);
        marker.on('dragend', saveMarkers);
        markers.push(marker);
        saveMarkers();
    };

    const showUserLocation = (position) => {
        const { latitude, longitude } = position.coords;
        if (userMarker) {
            userMarker.setLatLng([latitude, longitude]);
        } else {
            userMarker = L.marker([latitude, longitude], {
                draggable: false,
                title: 'Tu ubicacion',
                icon: userIcon,
            }).addTo(map);
        }

        map.setView([latitude, longitude], 14, { animate: true });
    };

    const requestGeolocation = () => {
        if (!('geolocation' in navigator)) {
            return;
        }

        navigator.geolocation.getCurrentPosition(
            showUserLocation,
            () => {
                // Si el usuario no da permiso, no hacemos nada.
            },
            {
                enableHighAccuracy: true,
                timeout: 8000,
                maximumAge: 0,
            }
        );
    };

    const setSearchMarker = (lat, lng, label) => {
        if (searchMarker) {
            searchMarker.setLatLng([lat, lng]);
        } else {
            searchMarker = L.marker([lat, lng], {
                draggable: false,
                icon: markerIcon,
            }).addTo(map);
        }

        if (label) {
            searchMarker.bindPopup(label).openPopup();
        }
    };

    const searchPlace = async (query) => {
        if (!query) {
            return;
        }

        try {
            const url = `search.php?q=${encodeURIComponent(query)}`;
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                },
            });
            if (!response.ok) {
                return;
            }

            const results = await response.json();
            if (!Array.isArray(results) || results.length === 0) {
                return;
            }

            const match = results[0];
            const lat = parseFloat(match.lat);
            const lng = parseFloat(match.lon);
            if (Number.isNaN(lat) || Number.isNaN(lng)) {
                return;
            }

            map.setView([lat, lng], 14, { animate: true });
            setSearchMarker(lat, lng, match.display_name || query);
        } catch (error) {
            // Si falla la busqueda, no bloqueamos al usuario.
        }
    };

    const loadMarkers = () => {
        const raw = localStorage.getItem(storageKey);
        if (!raw) {
            return false;
        }

        try {
            const data = JSON.parse(raw);
            if (!Array.isArray(data)) {
                return false;
            }

            data.forEach((item) => {
                if (typeof item.lat === 'number' && typeof item.lng === 'number') {
                    addMarker(item.lat, item.lng);
                }
            });
            return true;
        } catch (error) {
            return false;
        }
    };

    const loaded = loadMarkers();
    if (!loaded) {
        addMarker(9.9281, -84.0907);
        addMarker(9.9358, -84.0996);
        addMarker(9.9156, -84.0794);
    }


    const addButton = document.getElementById('add-marker');
    const locateButton = document.getElementById('locate-me');
    const resetButton = document.getElementById('reset-view');
    const clearButton = document.getElementById('clear-markers');
    const searchInput = document.getElementById('search-place');
    const searchButton = document.getElementById('search-place-btn');
    const searchForm = document.getElementById('search-form');

    if (addButton) {
        addButton.addEventListener('click', () => {
            const center = map.getCenter();
            addMarker(center.lat, center.lng);
        });
    }

    if (locateButton) {
        locateButton.addEventListener('click', () => {
            requestGeolocation();
        });
    }

    if (resetButton) {
        resetButton.addEventListener('click', () => {
            map.setView(defaultView.center, defaultView.zoom, { animate: true });
        });
    }

    if (clearButton) {
        clearButton.addEventListener('click', () => {
            clearMarkers();
        });
    }

    if (searchForm && searchInput) {
        searchForm.addEventListener('submit', (event) => {
            event.preventDefault();
            searchPlace(searchInput.value.trim());
        });
    } else if (searchButton && searchInput) {
        searchButton.addEventListener('click', () => {
            searchPlace(searchInput.value.trim());
        });
    }

    map.on('click', (event) => {
        addMarker(event.latlng.lat, event.latlng.lng);
    });
});
