/**
 * Google Maps Field JavaScript (Modern API)
 *
 * Uses async loading and Place Autocomplete Element (modern web component)
 * instead of deprecated callback pattern and legacy Autocomplete widget.
 *
 * @since 2.4.0
 */

class HKFNGoogleMapsField {
    constructor(fieldElement) {
        console.log('WFN: Constructing Google Maps field...');
        this.field = fieldElement;
        this.fieldKey = fieldElement.dataset.fieldKey;
        this.input = fieldElement.querySelector('.hkfn-address-autocomplete');
        this.mapElement = fieldElement.querySelector('.hkfn-map-display');
        this.statusElement = fieldElement.querySelector('.hkfn-address-status span');
        this.hiddenFields = {};
        this.autocompleteElement = null;

        console.log('WFN: Field key:', this.fieldKey);
        console.log('WFN: Input element:', this.input);
        console.log('WFN: Map element:', this.mapElement);

        // Get all hidden input fields
        this.initializeHiddenFields();

        // Initialize Google Maps components (async)
        this.initialize();

        console.log('WFN: Field construction complete');
    }

    async initialize() {
        // Initialize Google Maps components
        await this.initializeAutocomplete();
        await this.initializeMap();

        // Load existing data if present
        this.loadExistingData();
    }

    initializeHiddenFields() {
        const hiddenInputs = this.field.querySelectorAll('input[type="hidden"]');
        hiddenInputs.forEach(input => {
            const name = input.name;
            const fieldName = name.match(/\[([^\]]+)\]$/)?.[1];
            if (fieldName) {
                this.hiddenFields[fieldName] = input;
            }
        });
    }

    async initializeAutocomplete() {
        if (!window.google || !window.google.maps || !this.input) {
            console.warn('WFN: Google Maps API not loaded or input element not found');
            return;
        }

        // Wait for Places library to load (needed with async loading)
        if (!window.google.maps.places) {
            console.log('WFN: Waiting for Places library to load...');
            try {
                // Google Maps async loading requires importLibrary for places
                const { Place, Autocomplete } = await google.maps.importLibrary("places");
                console.log('WFN: Places library loaded via importLibrary');
            } catch (error) {
                console.error('WFN: Failed to load Places library:', error);
                return;
            }
        }

        // Check if Place Autocomplete Element is available
        // Note: The element is registered as a custom HTML element, not a JS class
        if (!window.google.maps.places) {
            console.warn('WFN: Places library not available, falling back to legacy widget');
            this.initializeLegacyAutocomplete();
            return;
        }

        // Check if the new Places API is available
        // The Place Autocomplete Element requires the new Places API (not just Places library)
        // For now, skip the element and use legacy widget to avoid API errors
        // This can be enabled later when API keys are upgraded
        const useModernElement = false; // Set to true when Places API (New) is enabled

        if (!useModernElement) {
            console.log('WFN: Using legacy Autocomplete widget (Place Autocomplete Element disabled by default)');
            this.initializeLegacyAutocomplete();
            return;
        }

        // Try to create the element to see if it's available
        const testElement = document.createElement('gmp-place-autocomplete');
        if (!testElement || typeof testElement.addEventListener !== 'function') {
            console.warn('WFN: Place Autocomplete Element not available, falling back to legacy widget');
            this.initializeLegacyAutocomplete();
            return;
        }

        try {
            // Create Place Autocomplete Element (modern web component)
            this.autocompleteElement = document.createElement('gmp-place-autocomplete');
            this.autocompleteElement.setAttribute('type', 'address');

            // Set country restriction to New Zealand using correct attribute
            this.autocompleteElement.setAttribute('country', 'nz');

            // Copy input attributes to autocomplete element
            this.autocompleteElement.setAttribute('placeholder', this.input.placeholder || 'Start typing an address...');
            this.autocompleteElement.setAttribute('id', this.input.id);

            // Set initial value if exists
            const currentAddress = this.getCurrentData().address;
            if (currentAddress) {
                this.autocompleteElement.value = currentAddress;
            }

            // Add CSS classes for styling
            this.autocompleteElement.className = 'hkfn-address-autocomplete hkfn-place-autocomplete-element';

            // Replace original input with autocomplete element
            this.input.parentNode.replaceChild(this.autocompleteElement, this.input);

            // Update reference to point to new element
            this.input = this.autocompleteElement;

            // Listen for place selection
            this.autocompleteElement.addEventListener('gmp-placeselect', async (event) => {
                await this.handlePlaceSelection(event);
            });

            // Listen for errors (e.g., Places API (New) not enabled)
            this.autocompleteElement.addEventListener('gmp-error', (event) => {
                console.error('WFN: Place Autocomplete Element error:', event);
                console.log('WFN: Falling back to legacy Autocomplete widget due to API error');

                // Replace element with original input
                const originalInput = document.createElement('input');
                originalInput.type = 'text';
                originalInput.className = 'hkfn-address-autocomplete';
                originalInput.placeholder = this.autocompleteElement.getAttribute('placeholder');
                originalInput.id = this.autocompleteElement.getAttribute('id');
                originalInput.value = this.autocompleteElement.value || '';

                this.autocompleteElement.parentNode.replaceChild(originalInput, this.autocompleteElement);
                this.input = originalInput;

                // Initialize legacy autocomplete
                this.initializeLegacyAutocomplete();
            });

            console.log('WFN: Place Autocomplete Element initialized successfully');

        } catch (error) {
            console.error('WFN: Failed to initialize Place Autocomplete Element:', error);
            // Restore original input if element creation failed
            this.initializeLegacyAutocomplete();
        }
    }

    /**
     * Fallback to legacy Autocomplete widget if Place Autocomplete Element unavailable
     */
    initializeLegacyAutocomplete() {
        if (!window.google || !window.google.maps || !window.google.maps.places) {
            console.warn('WFN: Google Maps Places API not available');
            return;
        }

        console.log('WFN: Using legacy Autocomplete widget');

        // Initialize legacy autocomplete
        this.autocomplete = new google.maps.places.Autocomplete(this.input, {
            types: ['establishment', 'geocode'],
            componentRestrictions: { country: 'nz' }
        });

        // Handle place selection
        this.autocomplete.addListener('place_changed', () => {
            this.handleLegacyPlaceSelection();
        });

        // Handle manual input changes
        this.input.addEventListener('input', () => {
            if (this.input.value !== this.getCurrentData().address) {
                this.clearAddressData();
            }
        });
    }

    async initializeMap() {
        if (!window.google || !window.google.maps || !this.mapElement) {
            console.warn('WFN: Google Maps API not loaded or map element not found');
            return;
        }

        // Ensure maps library is loaded (needed with async loading)
        if (!window.google.maps.Map) {
            console.log('WFN: Waiting for Maps library to load...');
            try {
                await google.maps.importLibrary("maps");
                console.log('WFN: Maps library loaded via importLibrary');
            } catch (error) {
                console.error('WFN: Failed to load Maps library:', error);
                return;
            }
        }

        const currentData = this.getCurrentData();

        // Default center (Wellington, NZ)
        const defaultCenter = { lat: -41.2865, lng: 174.7762 };
        const center = currentData.lat && currentData.lng
            ? { lat: parseFloat(currentData.lat), lng: parseFloat(currentData.lng) }
            : defaultCenter;

        // Initialize map
        this.map = new google.maps.Map(this.mapElement, {
            zoom: parseInt(currentData.zoom) || 14,
            center: center,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false
        });

        // Add marker if we have coordinates
        if (currentData.lat && currentData.lng) {
            this.addMarker(center);
        }
    }

    /**
     * Handle place selection from Place Autocomplete Element
     */
    async handlePlaceSelection(event) {
        try {
            const place = event.detail.place;

            if (!place) {
                console.warn('WFN: No place data in event');
                return;
            }

            // Fetch place details if needed
            let placeDetails = place;
            if (!place.geometry || !place.address_components) {
                // Need to fetch full details
                placeDetails = await this.fetchPlaceDetails(place.place_id);
            }

            if (!placeDetails || !placeDetails.geometry) {
                console.warn('WFN: No geometry data for selected place');
                this.updateStatus('Invalid place selected', 'error');
                return;
            }

            // Extract address components in ACFE-compatible format
            const addressData = this.extractAddressComponents(placeDetails);

            // Update all hidden fields
            this.updateHiddenFields(addressData);

            // Update map display
            this.updateMap(placeDetails.geometry.location, addressData.zoom);

            // Update status
            this.updateStatus(`✓ Address selected: ${addressData.address}`, 'success');

            console.log('WFN: Address data saved:', addressData);

        } catch (error) {
            console.error('WFN: Error handling place selection:', error);
            this.updateStatus('Error selecting address', 'error');
        }
    }

    /**
     * Fetch full place details using Places Service
     */
    async fetchPlaceDetails(placeId) {
        return new Promise((resolve, reject) => {
            if (!this.placesService) {
                this.placesService = new google.maps.places.PlacesService(this.map);
            }

            this.placesService.getDetails({
                placeId: placeId,
                fields: ['address_components', 'formatted_address', 'geometry', 'name', 'place_id']
            }, (place, status) => {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    resolve(place);
                } else {
                    reject(new Error(`Places service status: ${status}`));
                }
            });
        });
    }

    /**
     * Handle place selection from legacy Autocomplete widget (fallback)
     */
    handleLegacyPlaceSelection() {
        const place = this.autocomplete.getPlace();

        if (!place || !place.geometry) {
            console.warn('WFN: No geometry data for selected place');
            this.updateStatus('Invalid place selected', 'error');
            return;
        }

        // Extract address components in ACFE-compatible format
        const addressData = this.extractAddressComponents(place);

        // Update all hidden fields
        this.updateHiddenFields(addressData);

        // Update map display
        this.updateMap(place.geometry.location, addressData.zoom);

        // Update status
        this.updateStatus(`✓ Address selected: ${addressData.address}`, 'success');

        // Update input value to formatted address
        this.input.value = addressData.address;

        console.log('WFN: Address data saved:', addressData);
    }

    extractAddressComponents(place) {
        const components = place.address_components || [];

        // Initialize with ACFE-compatible structure
        const addressData = {
            address: place.formatted_address || '',
            lat: place.geometry.location.lat ? place.geometry.location.lat() : place.geometry.location.lat,
            lng: place.geometry.location.lng ? place.geometry.location.lng() : place.geometry.location.lng,
            zoom: 16,
            place_id: place.place_id || '',
            name: place.name || '',
            street_number: '',
            street_name: '',
            street_name_short: '',
            city: '',
            state: '',
            post_code: '',
            country: '',
            country_short: ''
        };

        // Parse Google's address components
        components.forEach(component => {
            const types = component.types;

            if (types.includes('street_number')) {
                addressData.street_number = component.long_name;
            }
            if (types.includes('route')) {
                addressData.street_name = component.long_name;
                addressData.street_name_short = component.short_name;
            }
            if (types.includes('locality') || types.includes('sublocality_level_1')) {
                addressData.city = component.long_name;
            }
            if (types.includes('administrative_area_level_1')) {
                addressData.state = component.long_name;
            }
            if (types.includes('postal_code')) {
                addressData.post_code = component.long_name;
            }
            if (types.includes('country')) {
                addressData.country = component.long_name;
                addressData.country_short = component.short_name;
            }
        });

        return addressData;
    }

    updateHiddenFields(addressData) {
        // Update each hidden field with the corresponding data
        Object.keys(addressData).forEach(key => {
            if (this.hiddenFields[key]) {
                this.hiddenFields[key].value = addressData[key] || '';
            }
        });

        // Trigger change events for ACF
        Object.values(this.hiddenFields).forEach(input => {
            const event = new Event('change', { bubbles: true });
            input.dispatchEvent(event);
        });
    }

    updateMap(location, zoom = 16) {
        if (!this.map) return;

        // Handle both LatLng object and plain object
        const latLng = location.lat && typeof location.lat === 'function'
            ? location
            : new google.maps.LatLng(location.lat, location.lng);

        // Update map center and zoom
        this.map.setCenter(latLng);
        this.map.setZoom(zoom);

        // Clear existing markers
        if (this.marker) {
            this.marker.setMap(null);
        }

        // Add new marker
        this.addMarker(latLng);
    }

    addMarker(location) {
        this.marker = new google.maps.Marker({
            position: location,
            map: this.map,
            draggable: false,
            title: 'Funeral Venue Location'
        });
    }

    getCurrentData() {
        const data = {};
        Object.keys(this.hiddenFields).forEach(key => {
            data[key] = this.hiddenFields[key].value;
        });
        return data;
    }

    loadExistingData() {
        const currentData = this.getCurrentData();

        // If we have existing data, update the input and map
        if (currentData.address) {
            // Set value on autocomplete element or input
            if (this.input) {
                this.input.value = currentData.address;
            }

            this.updateStatus(`✓ Address selected: ${currentData.address}`, 'success');

            if (currentData.lat && currentData.lng) {
                const location = {
                    lat: parseFloat(currentData.lat),
                    lng: parseFloat(currentData.lng)
                };
                this.updateMap(location, parseInt(currentData.zoom) || 16);
            }
        }
    }

    clearAddressData() {
        // Clear all hidden fields
        Object.values(this.hiddenFields).forEach(input => {
            input.value = '';
        });

        // Clear marker
        if (this.marker) {
            this.marker.setMap(null);
            this.marker = null;
        }

        // Update status
        this.updateStatus('No address selected', 'default');
    }

    updateStatus(message, type = 'default') {
        if (!this.statusElement) return;

        this.statusElement.textContent = message;

        // Update styling based on type
        this.statusElement.style.color = type === 'error' ? '#d63638' :
                                        type === 'success' ? '#00a32a' : '#666';
    }
}

/**
 * Initialize all Google Maps fields on the page
 * Uses modern async loading with polling for API availability
 */
function initializeHKFNGoogleMapsFields() {
    console.log('WFN: Initializing Google Maps fields...');
    console.log('WFN: window.google available:', !!window.google);
    console.log('WFN: window.google.maps available:', !!(window.google && window.google.maps));

    // Find all Google Maps fields on the page
    const fields = document.querySelectorAll('.hkfn-google-maps-field');
    console.log('WFN: Found', fields.length, 'Google Maps fields');

    if (fields.length === 0) {
        console.log('WFN: No .hkfn-google-maps-field elements found on page');
        return;
    }

    fields.forEach(field => {
        try {
            // Check if field already initialized (avoid double-initialization)
            if (field.dataset.hkfnInitialized === 'true') {
                console.log('WFN: Field already initialized:', field.dataset.fieldKey);
                return;
            }

            console.log('WFN: Initializing field:', field.dataset.fieldKey);
            new HKFNGoogleMapsField(field);

            // Mark as initialized
            field.dataset.hkfnInitialized = 'true';

            console.log('WFN: Successfully initialized field:', field.dataset.fieldKey);
        } catch (error) {
            console.error('WFN: Failed to initialize Google Maps field:', error);
        }
    });
}

/**
 * Wait for Google Maps API to load using polling with timeout
 */
function waitForGoogleMapsAPI() {
    console.log('WFN: Waiting for Google Maps API to load...');

    let attempts = 0;
    const maxAttempts = 100; // 100 attempts * 100ms = 10 seconds

    const checkInterval = setInterval(() => {
        attempts++;

        if (window.google && window.google.maps && window.google.maps.places) {
            console.log('WFN: Google Maps API loaded successfully');
            clearInterval(checkInterval);
            initializeHKFNGoogleMapsFields();
        } else if (attempts >= maxAttempts) {
            console.error('WFN: Google Maps API failed to load within 10 seconds');
            clearInterval(checkInterval);

            // Show error message in fields
            const fields = document.querySelectorAll('.hkfn-google-maps-field');
            fields.forEach(field => {
                const statusElement = field.querySelector('.hkfn-address-status span');
                if (statusElement) {
                    statusElement.textContent = '⚠ Google Maps API failed to load. Check API key configuration.';
                    statusElement.style.color = '#d63638';
                }
            });
        }
    }, 100);
}

/**
 * Initialize when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('WFN: DOM ready, checking for Google Maps API...');

    // Check if Google Maps API is already loaded
    if (window.google && window.google.maps && window.google.maps.places) {
        console.log('WFN: Google Maps API already loaded');
        initializeHKFNGoogleMapsFields();
    } else {
        console.log('WFN: Google Maps API not yet loaded, starting polling...');
        waitForGoogleMapsAPI();
    }
});

/**
 * Legacy callback function for backward compatibility with feature flag
 * This will be called if site uses: add_filter('hkfn_use_modern_google_maps_api', '__return_false')
 */
function initWFNGoogleMaps() {
    console.log('WFN: Legacy callback function called');
    initializeHKFNGoogleMapsFields();
}

// Make legacy callback available globally
window.initWFNGoogleMaps = initWFNGoogleMaps;

/**
 * Initialize for dynamically added ACF fields (repeaters, flexible content, etc.)
 */
if (typeof acf !== 'undefined') {
    acf.addAction('ready_field/type=hkfn_google_maps', function(field) {
        console.log('WFN: ACF ready_field action triggered');

        // Wait for Google Maps API if not loaded yet
        if (!window.google || !window.google.maps) {
            console.log('WFN: Google Maps API not loaded, waiting...');
            waitForGoogleMapsAPI();
            return;
        }

        // Check if field exists and has the expected structure
        let fieldElement = null;

        if (field && field.length > 0 && field[0] && typeof field[0].querySelector === 'function') {
            fieldElement = field[0].querySelector('.hkfn-google-maps-field');
        } else if (field && field.querySelector) {
            fieldElement = field.querySelector('.hkfn-google-maps-field');
        }

        if (fieldElement && fieldElement.dataset.hkfnInitialized !== 'true') {
            console.log('WFN: Initializing ACF field:', fieldElement.dataset.fieldKey);
            new HKFNGoogleMapsField(fieldElement);
            fieldElement.dataset.hkfnInitialized = 'true';
        }
    });

    acf.addAction('append_field/type=hkfn_google_maps', function(field) {
        console.log('WFN: ACF append_field action triggered');

        // Wait for Google Maps API if not loaded yet
        if (!window.google || !window.google.maps) {
            console.log('WFN: Google Maps API not loaded, waiting...');
            waitForGoogleMapsAPI();
            return;
        }

        // Check if field exists and has the expected structure
        let fieldElement = null;

        if (field && field.length > 0 && field[0] && typeof field[0].querySelector === 'function') {
            fieldElement = field[0].querySelector('.hkfn-google-maps-field');
        } else if (field && field.querySelector) {
            fieldElement = field.querySelector('.hkfn-google-maps-field');
        }

        if (fieldElement && fieldElement.dataset.hkfnInitialized !== 'true') {
            console.log('WFN: Initializing appended ACF field:', fieldElement.dataset.fieldKey);
            new HKFNGoogleMapsField(fieldElement);
            fieldElement.dataset.hkfnInitialized = 'true';
        }
    });
}
