/**
 * Google Maps Field JavaScript
 * Handles Google Places autocomplete and map display
 * 
 * @since 2.0.0
 */

class WFNGoogleMapsField {
    constructor(fieldElement) {
        console.log('WFN: Constructing Google Maps field...');
        this.field = fieldElement;
        this.fieldKey = fieldElement.dataset.fieldKey;
        this.input = fieldElement.querySelector('.wfn-address-autocomplete');
        this.mapElement = fieldElement.querySelector('.wfn-map-display');
        this.statusElement = fieldElement.querySelector('.wfn-address-status span');
        this.hiddenFields = {};
        
        console.log('WFN: Field key:', this.fieldKey);
        console.log('WFN: Input element:', this.input);
        console.log('WFN: Map element:', this.mapElement);
        
        // Get all hidden input fields
        this.initializeHiddenFields();
        
        // Initialize Google Maps components
        this.initializeAutocomplete();
        this.initializeMap();
        
        // Load existing data if present
        this.loadExistingData();
        
        console.log('WFN: Field construction complete');
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
    
    initializeAutocomplete() {
        if (!window.google || !this.input) {
            console.warn('Google Maps API not loaded or input element not found');
            return;
        }
        
        // Initialize autocomplete with New Zealand bias
        this.autocomplete = new google.maps.places.Autocomplete(this.input, {
            types: ['establishment', 'geocode'],
            componentRestrictions: { country: 'nz' }
        });
        
        // Handle place selection
        this.autocomplete.addListener('place_changed', () => {
            this.handlePlaceSelection();
        });
        
        // Handle manual input changes
        this.input.addEventListener('input', () => {
            // Clear data if user manually types something different
            if (this.input.value !== this.getCurrentData().address) {
                this.clearAddressData();
            }
        });
    }
    
    initializeMap() {
        if (!window.google || !this.mapElement) {
            console.warn('Google Maps API not loaded or map element not found');
            return;
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
    
    handlePlaceSelection() {
        const place = this.autocomplete.getPlace();
        
        if (!place || !place.geometry) {
            console.warn('No geometry data for selected place');
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
        
        console.log('Address data saved:', addressData);
    }
    
    extractAddressComponents(place) {
        const components = place.address_components || [];
        
        // Initialize with ACFE-compatible structure
        const addressData = {
            address: place.formatted_address || '',
            lat: place.geometry.location.lat(),
            lng: place.geometry.location.lng(),
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
        
        // Update map center and zoom
        this.map.setCenter(location);
        this.map.setZoom(zoom);
        
        // Clear existing markers
        if (this.marker) {
            this.marker.setMap(null);
        }
        
        // Add new marker
        this.addMarker(location);
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
            this.input.value = currentData.address;
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

// Global initialization function called by Google Maps API
// Define globally to ensure it's available when Maps API loads
function initWFNGoogleMaps() {
    console.log('WFN: Google Maps API callback fired!');
    console.log('WFN: window.google available:', !!window.google);
    console.log('WFN: window.google.maps available:', !!(window.google && window.google.maps));
    
    // Initialize all Google Maps fields on the page
    const fields = document.querySelectorAll('.wfn-google-maps-field');
    console.log('WFN: Found', fields.length, 'Google Maps fields');
    
    if (fields.length === 0) {
        console.warn('WFN: No .wfn-google-maps-field elements found on page');
    }
    
    fields.forEach(field => {
        try {
            console.log('WFN: Initializing field:', field.dataset.fieldKey);
            new WFNGoogleMapsField(field);
            console.log('WFN: Successfully initialized field:', field.dataset.fieldKey);
        } catch (error) {
            console.error('WFN: Failed to initialize Google Maps field:', error);
        }
    });
}

// Make sure it's also available on window for callback
window.initWFNGoogleMaps = initWFNGoogleMaps;

// Initialize for dynamically added fields (ACF repeaters, etc.)
if (typeof acf !== 'undefined') {
    acf.addAction('ready_field/type=wfn_google_maps', function(field) {
        // Check if field exists and has the expected structure
        if (field && field.length > 0 && field[0] && typeof field[0].querySelector === 'function') {
            const fieldElement = field[0].querySelector('.wfn-google-maps-field');
            if (fieldElement) {
                new WFNGoogleMapsField(fieldElement);
            }
        } else if (field && field.querySelector) {
            // Alternative: field might be the DOM element directly
            const fieldElement = field.querySelector('.wfn-google-maps-field');
            if (fieldElement) {
                new WFNGoogleMapsField(fieldElement);
            }
        }
    });
    
    acf.addAction('append_field/type=wfn_google_maps', function(field) {
        // Check if field exists and has the expected structure
        if (field && field.length > 0 && field[0] && typeof field[0].querySelector === 'function') {
            const fieldElement = field[0].querySelector('.wfn-google-maps-field');
            if (fieldElement) {
                new WFNGoogleMapsField(fieldElement);
            }
        } else if (field && field.querySelector) {
            // Alternative: field might be the DOM element directly
            const fieldElement = field.querySelector('.wfn-google-maps-field');
            if (fieldElement) {
                new WFNGoogleMapsField(fieldElement);
            }
        }
    });
}

// Fallback initialization if Google Maps API loads before DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if Google Maps API is already loaded
    if (window.google && window.google.maps) {
        initWFNGoogleMaps();
    }
});