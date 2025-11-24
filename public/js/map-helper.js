/**
 * MapHelper - Universal wrapper for Google Maps and OpenStreetMap
 * Provides a unified API regardless of the map provider
 */
class MapHelper {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.provider = window.mapProvider || 'openstreetmap';
        this.map = null;
        this.markers = [];
        this.circles = [];
        this.options = {
            center: { lat: options.lat || 5.4781, lng: options.lng || 10.4178 }, // Bafoussam, Cameroun by default
            zoom: options.zoom || 13,
            ...options
        };
    }

    /**
     * Initialize the map based on the provider
     */
    async init() {
        if (this.provider === 'google') {
            return this.initGoogleMap();
        } else {
            return this.initOpenStreetMap();
        }
    }

    /**
     * Initialize Google Maps
     */
    initGoogleMap() {
        const container = document.getElementById(this.containerId);
        if (!container) {
            throw new Error(`Container #${this.containerId} not found`);
        }

        this.map = new google.maps.Map(container, {
            center: this.options.center,
            zoom: this.options.zoom,
            mapTypeControl: true,
            streetViewControl: false,
        });

        return this.map;
    }

    /**
     * Initialize OpenStreetMap with Leaflet
     */
    initOpenStreetMap() {
        const container = document.getElementById(this.containerId);
        if (!container) {
            throw new Error(`Container #${this.containerId} not found`);
        }

        this.map = L.map(this.containerId).setView(
            [this.options.center.lat, this.options.center.lng],
            this.options.zoom
        );

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19,
        }).addTo(this.map);

        return this.map;
    }

    /**
     * Add a marker to the map
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     * @param {object} options - Marker options (title, draggable, popup, icon, etc.)
     * @returns {object} The created marker
     */
    addMarker(lat, lng, options = {}) {
        let marker;

        if (this.provider === 'google') {
            marker = new google.maps.Marker({
                position: { lat, lng },
                map: this.map,
                title: options.title || '',
                draggable: options.draggable || false,
                icon: options.icon || undefined,
            });

            if (options.popup) {
                const infoWindow = new google.maps.InfoWindow({
                    content: options.popup
                });
                marker.addListener('click', () => {
                    infoWindow.open(this.map, marker);
                });
            }

            if (options.onClick) {
                marker.addListener('click', () => options.onClick(marker));
            }

            if (options.onDragEnd) {
                marker.addListener('dragend', (event) => {
                    options.onDragEnd({
                        lat: event.latLng.lat(),
                        lng: event.latLng.lng()
                    });
                });
            }
        } else {
            // OpenStreetMap with Leaflet
            const markerOptions = {
                draggable: options.draggable || false,
            };

            if (options.icon) {
                markerOptions.icon = L.icon(options.icon);
            }

            marker = L.marker([lat, lng], markerOptions).addTo(this.map);

            if (options.title) {
                marker.bindTooltip(options.title);
            }

            if (options.popup) {
                marker.bindPopup(options.popup);
            }

            if (options.onClick) {
                marker.on('click', () => options.onClick(marker));
            }

            if (options.onDragEnd) {
                marker.on('dragend', (event) => {
                    const pos = event.target.getLatLng();
                    options.onDragEnd({
                        lat: pos.lat,
                        lng: pos.lng
                    });
                });
            }
        }

        this.markers.push(marker);
        return marker;
    }

    /**
     * Add a circle to the map
     * @param {number} lat - Center latitude
     * @param {number} lng - Center longitude
     * @param {number} radius - Radius in meters
     * @param {object} options - Circle options (color, fillColor, fillOpacity, etc.)
     * @returns {object} The created circle
     */
    addCircle(lat, lng, radius, options = {}) {
        let circle;

        if (this.provider === 'google') {
            circle = new google.maps.Circle({
                strokeColor: options.strokeColor || '#FF0000',
                strokeOpacity: options.strokeOpacity || 0.8,
                strokeWeight: options.strokeWeight || 2,
                fillColor: options.fillColor || '#FF0000',
                fillOpacity: options.fillOpacity || 0.2,
                map: this.map,
                center: { lat, lng },
                radius: radius,
                editable: options.editable || false,
            });
        } else {
            // OpenStreetMap with Leaflet
            circle = L.circle([lat, lng], {
                color: options.strokeColor || '#FF0000',
                fillColor: options.fillColor || '#FF0000',
                fillOpacity: options.fillOpacity || 0.2,
                radius: radius,
                weight: options.strokeWeight || 2,
            }).addTo(this.map);
        }

        this.circles.push(circle);
        return circle;
    }

    /**
     * Search for an address and return coordinates
     * Uses Nominatim API for OpenStreetMap, Google Geocoding for Google Maps
     * @param {string} query - Address to search
     * @returns {Promise} Promise resolving to {lat, lng, address}
     */
    async searchAddress(query) {
        if (this.provider === 'google') {
            return new Promise((resolve, reject) => {
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ address: query }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        resolve({
                            lat: results[0].geometry.location.lat(),
                            lng: results[0].geometry.location.lng(),
                            address: results[0].formatted_address
                        });
                    } else {
                        reject(new Error('Geocoding failed: ' + status));
                    }
                });
            });
        } else {
            // Use Nominatim API for OpenStreetMap
            const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`;

            try {
                const response = await fetch(url, {
                    headers: {
                        'User-Agent': 'AttendanceSystem/1.0'
                    }
                });
                const data = await response.json();

                if (data && data.length > 0) {
                    return {
                        lat: parseFloat(data[0].lat),
                        lng: parseFloat(data[0].lon),
                        address: data[0].display_name
                    };
                } else {
                    throw new Error('No results found');
                }
            } catch (error) {
                throw new Error('Geocoding failed: ' + error.message);
            }
        }
    }

    /**
     * Reverse geocode coordinates to get address
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     * @returns {Promise} Promise resolving to address string
     */
    async reverseGeocode(lat, lng) {
        if (this.provider === 'google') {
            return new Promise((resolve, reject) => {
                const geocoder = new google.maps.Geocoder();
                geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        resolve(results[0].formatted_address);
                    } else {
                        reject(new Error('Reverse geocoding failed: ' + status));
                    }
                });
            });
        } else {
            // Use Nominatim API for OpenStreetMap
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`;

            try {
                const response = await fetch(url, {
                    headers: {
                        'User-Agent': 'AttendanceSystem/1.0'
                    }
                });
                const data = await response.json();

                if (data && data.display_name) {
                    return data.display_name;
                } else {
                    throw new Error('No address found');
                }
            } catch (error) {
                throw new Error('Reverse geocoding failed: ' + error.message);
            }
        }
    }

    /**
     * Set the center of the map
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     * @param {number} zoom - Optional zoom level
     */
    setCenter(lat, lng, zoom = null) {
        if (this.provider === 'google') {
            this.map.setCenter({ lat, lng });
            if (zoom !== null) {
                this.map.setZoom(zoom);
            }
        } else {
            if (zoom !== null) {
                this.map.setView([lat, lng], zoom);
            } else {
                this.map.panTo([lat, lng]);
            }
        }
    }

    /**
     * Listen to map click events
     * @param {function} callback - Callback function receiving {lat, lng}
     */
    onClick(callback) {
        if (this.provider === 'google') {
            this.map.addListener('click', (event) => {
                callback({
                    lat: event.latLng.lat(),
                    lng: event.latLng.lng()
                });
            });
        } else {
            this.map.on('click', (event) => {
                callback({
                    lat: event.latlng.lat,
                    lng: event.latlng.lng
                });
            });
        }
    }

    /**
     * Clear all markers from the map
     */
    clearMarkers() {
        this.markers.forEach(marker => {
            if (this.provider === 'google') {
                marker.setMap(null);
            } else {
                marker.remove();
            }
        });
        this.markers = [];
    }

    /**
     * Clear all circles from the map
     */
    clearCircles() {
        this.circles.forEach(circle => {
            if (this.provider === 'google') {
                circle.setMap(null);
            } else {
                circle.remove();
            }
        });
        this.circles = [];
    }

    /**
     * Clear everything from the map
     */
    clearAll() {
        this.clearMarkers();
        this.clearCircles();
    }

    /**
     * Get the current map bounds
     * @returns {object} {north, south, east, west}
     */
    getBounds() {
        if (this.provider === 'google') {
            const bounds = this.map.getBounds();
            return {
                north: bounds.getNorthEast().lat(),
                south: bounds.getSouthWest().lat(),
                east: bounds.getNorthEast().lng(),
                west: bounds.getSouthWest().lng()
            };
        } else {
            const bounds = this.map.getBounds();
            return {
                north: bounds.getNorth(),
                south: bounds.getSouth(),
                east: bounds.getEast(),
                west: bounds.getWest()
            };
        }
    }

    /**
     * Fit the map to show all markers
     */
    fitMarkers() {
        if (this.markers.length === 0) return;

        if (this.provider === 'google') {
            const bounds = new google.maps.LatLngBounds();
            this.markers.forEach(marker => {
                bounds.extend(marker.getPosition());
            });
            this.map.fitBounds(bounds);
        } else {
            const group = L.featureGroup(this.markers);
            this.map.fitBounds(group.getBounds());
        }
    }

    /**
     * Get the underlying map object
     * @returns {object} Google Maps or Leaflet map instance
     */
    getMapInstance() {
        return this.map;
    }
}
