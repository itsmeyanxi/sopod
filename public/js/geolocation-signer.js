/**
 * Geolocation Signer - Captures GPS coordinates when approving documents
 * Ensures digital signatures include location data for compliance and audit trails
 */

class GeolocationSigner {
    constructor() {
        this.isRequesting = false;
    }

    /**
     * Request geolocation and return coordinates
     */
    async getCoordinates() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation is not supported by this browser'));
                return;
            }

            this.isRequesting = true;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.isRequesting = false;
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        timestamp: new Date(position.timestamp).toISOString(),
                    });
                },
                (error) => {
                    this.isRequesting = false;
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0,
                }
            );
        });
    }

    /**
     * Reverse geocode coordinates to get location name
     * Uses OpenStreetMap Nominatim API (free, no API key required)
     */
    async reverseGeocode(latitude, longitude) {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`,
                {
                    headers: { 'Accept-Language': 'en' }
                }
            );
            const data = await response.json();
            return data.address?.city || data.address?.town || data.address?.county || 'Unknown Location';
        } catch (error) {
            console.warn('Could not reverse geocode location:', error);
            return `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
        }
    }

    /**
     * Request signature with geolocation
     * This should be called when user clicks "Approve" button
     */
    async requestSignatureWithLocation() {
        try {
            // Request permission and get location
            const coords = await this.getCoordinates();

            // Get location name
            const locationName = await this.reverseGeocode(coords.latitude, coords.longitude);

            return {
                success: true,
                latitude: coords.latitude,
                longitude: coords.longitude,
                locationName: locationName,
                timestamp: coords.timestamp,
                accuracy: coords.accuracy,
            };
        } catch (error) {
            console.error('Geolocation error:', error);
            return {
                success: false,
                error: error.message,
            };
        }
    }

    /**
     * Display location on map
     */
    displayLocationOnMap(latitude, longitude, containerId = 'locationMap') {
        const container = document.getElementById(containerId);
        if (!container) return;

        const mapHtml = `
            <iframe
                width="100%"
                height="300"
                style="border:0; border-radius: 8px;"
                loading="lazy"
                allowfullscreen=""
                src="https://www.openstreetmap.org/export/embed.html?bbox=${longitude - 0.01},${latitude - 0.01},${longitude + 0.01},${latitude + 0.01}&layer=mapnik&marker=${latitude},${longitude}">
            </iframe>
        `;
        container.innerHTML = mapHtml;
    }

    /**
     * Format coordinates for display
     */
    formatCoordinates(latitude, longitude) {
        return `${latitude.toFixed(6)}°, ${longitude.toFixed(6)}°`;
    }

    /**
     * Calculate distance between two coordinates (in kilometers)
     */
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth's radius in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a =
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
}

// Create global instance
const geolocationSigner = new GeolocationSigner();

/**
 * Helper function to attach geolocation capture to approve buttons
 */
function attachGeolocationToApproveButton(buttonSelector, formSelector) {
    const button = document.querySelector(buttonSelector);
    const form = document.querySelector(formSelector);

    if (!button || !form) return;

    button.addEventListener('click', async function(e) {
        // Prevent form submission initially
        e.preventDefault();

        // Show loading state
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Capturing signature location...';
        button.disabled = true;

        try {
            const result = await geolocationSigner.requestSignatureWithLocation();

            if (result.success) {
                // Create hidden inputs for coordinates
                let latInput = form.querySelector('input[name="approved_latitude"]');
                let lonInput = form.querySelector('input[name="approved_longitude"]');
                let locInput = form.querySelector('input[name="approved_location"]');

                if (!latInput) {
                    latInput = document.createElement('input');
                    latInput.type = 'hidden';
                    latInput.name = 'approved_latitude';
                    form.appendChild(latInput);
                }

                if (!lonInput) {
                    lonInput = document.createElement('input');
                    lonInput.type = 'hidden';
                    lonInput.name = 'approved_longitude';
                    form.appendChild(lonInput);
                }

                if (!locInput) {
                    locInput = document.createElement('input');
                    locInput.type = 'hidden';
                    locInput.name = 'approved_location';
                    form.appendChild(locInput);
                }

                // Fill in the values
                latInput.value = result.latitude;
                lonInput.value = result.longitude;
                locInput.value = result.locationName;

                // Show confirmation
                alert(`✓ Signature location captured!\n\nLocation: ${result.locationName}\nCoordinates: ${geolocationSigner.formatCoordinates(result.latitude, result.longitude)}\nAccuracy: ±${Math.round(result.accuracy)}m`);

                // Submit the form
                form.submit();
            } else {
                alert(`⚠ Could not capture location: ${result.error}\n\nDo you want to proceed without location data?`);

                // Ask user if they want to continue without location
                if (confirm('Continue approval without location data?')) {
                    form.submit();
                } else {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            }
        } catch (error) {
            alert(`Error: ${error.message}`);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    });
}
