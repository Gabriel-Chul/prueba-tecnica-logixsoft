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
    let nextMarkerId = 1;
    let userMarker = null;
    let searchMarker = null;
    const listElement = document.getElementById('saved-list');
    const countElement = document.getElementById('marker-count');

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

    const formatCoords = (lat, lng) => `${lat.toFixed(4)}, ${lng.toFixed(4)}`;

    const updateCount = () => {
        if (countElement) {
            countElement.textContent = String(markers.length);
        }
    };

    const saveMarkers = () => {
        const data = markers.map((entry) => ({
            id: entry.id,
            labelBase: entry.labelBase,
            lat: entry.lat,
            lng: entry.lng,
        }));
        localStorage.setItem(storageKey, JSON.stringify(data));
        updateCount();
    };

    const clearMarkers = () => {
        markers.forEach((entry) => entry.marker.remove());
        markers = [];
        if (listElement) {
            listElement.innerHTML = '';
        }
        nextMarkerId = 1;
        if (searchMarker) {
            searchMarker.remove();
            searchMarker = null;
        }
        saveMarkers();
    };

    const buildLabel = (labelBase, lat, lng) => `${labelBase} - Lat: ${formatCoords(lat, lng)}`;

    const createListItem = (entry) => {
        if (!listElement) {
            return;
        }

        const item = document.createElement('li');
        item.className = 'map-item';
        item.dataset.id = String(entry.id);

        const title = document.createElement('div');
        title.className = 'map-item-title';
        title.textContent = buildLabel(entry.labelBase, entry.lat, entry.lng);

        const removeButton = document.createElement('button');
        removeButton.className = 'map-item-remove';
        removeButton.type = 'button';
        removeButton.setAttribute('aria-label', 'Eliminar marcador');
        removeButton.textContent = '🗑';

        removeButton.addEventListener('click', (event) => {
            event.stopPropagation();
            removeMarker(entry.id);
        });

        item.addEventListener('click', () => {
            map.flyTo([entry.lat, entry.lng], 15, { animate: true, duration: 0.6 });
            entry.marker.openPopup();
        });

        item.appendChild(title);
        item.appendChild(removeButton);
        listElement.appendChild(item);
    };

    const updateListItem = (entry) => {
        if (!listElement) {
            return;
        }

        const item = listElement.querySelector(`.map-item[data-id="${entry.id}"]`);
        if (!item) {
            return;
        }

        const title = item.querySelector('.map-item-title');
        if (title) {
            title.textContent = buildLabel(entry.labelBase, entry.lat, entry.lng);
        }
    };

    const removeMarker = (id) => {
        const index = markers.findIndex((entry) => entry.id === id);
        if (index === -1) {
            return;
        }

        const entry = markers[index];
        entry.marker.remove();
        markers.splice(index, 1);

        if (listElement) {
            const item = listElement.querySelector(`.map-item[data-id="${id}"]`);
            if (item) {
                item.remove();
            }
        }

        saveMarkers();
    };

    const addMarker = (lat, lng, labelBase = null, skipSave = false) => {
        const label = labelBase || `Marcador #${nextMarkerId++}`;
        const marker = L.marker([lat, lng], {
            draggable: true,
            icon: markerIcon,
        }).addTo(map);
        marker.bindPopup(label);

        const entry = {
            id: nextMarkerId - 1,
            labelBase: label,
            lat,
            lng,
            marker,
        };

        marker.on('dragend', () => {
            const position = marker.getLatLng();
            entry.lat = position.lat;
            entry.lng = position.lng;
            updateListItem(entry);
            saveMarkers();
        });

        markers.push(entry);
        createListItem(entry);
        if (!skipSave) {
            saveMarkers();
        }
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

            let maxId = 0;
            data.forEach((item) => {
                if (typeof item.lat === 'number' && typeof item.lng === 'number') {
                    const id = typeof item.id === 'number' ? item.id : ++maxId;
                    const label = item.labelBase || `Marcador #${id}`;
                    maxId = Math.max(maxId, id);
                    nextMarkerId = maxId + 1;
                    addMarker(item.lat, item.lng, label, true);
                }
            });
            saveMarkers();
            return true;
        } catch (error) {
            return false;
        }
    };

    const loaded = loadMarkers();
    if (!loaded) {
        addMarker(9.9281, -84.0907, 'Marcador #1', true);
        addMarker(9.9358, -84.0996, 'Marcador #2', true);
        addMarker(9.9156, -84.0794, 'Marcador #3', true);
        saveMarkers();
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
