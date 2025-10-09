/**
 * Flatpickr Date Range Picker Initialization
 *
 * Implements lazy loading - only loads Flatpickr assets when user interacts with date field.
 * Provides range selection with WordPress date format compatibility.
 *
 * @since 2.4.0
 */
(function() {
    'use strict';

    var flatpickrLoaded = false;
    var flatpickrInstance = null;

    /**
     * Load Flatpickr assets dynamically
     */
    function loadFlatpickrAssets(callback) {
        if (flatpickrLoaded) {
            if (callback) callback();
            return;
        }

        // Check if wfnFlatpickr is available
        if (typeof wfnFlatpickr === 'undefined') {
            console.error('WFN: Flatpickr configuration not loaded. wfnFlatpickr is undefined.');
            return;
        }

        var assetsToLoad = 2;
        var assetsLoaded = 0;

        function checkAllLoaded() {
            assetsLoaded++;
            if (assetsLoaded === assetsToLoad) {
                flatpickrLoaded = true;
                if (callback) callback();
            }
        }

        // Load CSS
        var cssLink = document.createElement('link');
        cssLink.rel = 'stylesheet';
        cssLink.href = wfnFlatpickr.cssUrl;
        cssLink.onload = checkAllLoaded;
        cssLink.onerror = function() {
            console.error('WFN: Failed to load Flatpickr CSS from ' + wfnFlatpickr.cssUrl);
            checkAllLoaded();
        };
        document.head.appendChild(cssLink);

        // Load JS
        var script = document.createElement('script');
        script.src = wfnFlatpickr.jsUrl;
        script.onload = checkAllLoaded;
        script.onerror = function() {
            console.error('WFN: Failed to load Flatpickr JS from ' + wfnFlatpickr.jsUrl);
            checkAllLoaded();
        };
        document.head.appendChild(script);
    }

    /**
     * Initialize Flatpickr on date range input
     */
    function initializeFlatpickr() {
        var dateInput = document.getElementById('wfn-date-range');
        if (!dateInput || flatpickrInstance) {
            return;
        }

        // Get hidden input fields for backend compatibility
        var dateFromInput = document.getElementById('wfn-date-from');
        var dateToInput = document.getElementById('wfn-date-to');

        if (!dateFromInput || !dateToInput) {
            console.error('WFN: Hidden date inputs not found');
            return;
        }

        // Check if we have existing date values to display
        var existingFrom = dateFromInput.value;
        var existingTo = dateToInput.value;
        var defaultDates = null;

        if (existingFrom && existingTo) {
            defaultDates = [existingFrom, existingTo];
            // Display existing dates in visible input (formatted for readability)
            dateInput.value = formatDateDisplay(existingFrom) + ' to ' + formatDateDisplay(existingTo);
        }

        // Initialize Flatpickr with range mode
        flatpickrInstance = flatpickr(dateInput, {
            mode: 'range',
            dateFormat: 'Y-m-d',  // Internal format (stored in hidden inputs)
            altInput: true,  // Use alternate input for display
            altFormat: 'j M Y',  // Display format (e.g., "1 Sep 2025")
            allowInput: false,  // Don't allow manual input
            clickOpens: true,

            // Set default dates if they exist
            defaultDate: defaultDates,

            // On date selection, update hidden inputs
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    // Format dates as Y-m-d for backend using the Date objects
                    var from = formatDate(selectedDates[0]);
                    var to = formatDate(selectedDates[1]);

                    dateFromInput.value = from;
                    dateToInput.value = to;
                } else if (selectedDates.length === 1) {
                    // Single date selected (start of range)
                    dateFromInput.value = formatDate(selectedDates[0]);
                    dateToInput.value = '';
                } else {
                    // No dates selected (cleared)
                    dateFromInput.value = '';
                    dateToInput.value = '';
                }
            },

            // On picker close, trigger search form update if applicable
            onClose: function(selectedDates, dateStr, instance) {
                // Trigger change event for search functionality
                var event = new Event('change', { bubbles: true });
                dateInput.dispatchEvent(event);
            }
        });

        // Add clear button functionality
        var clearButton = dateInput.nextElementSibling;
        if (clearButton && clearButton.classList.contains('wfn-date-clear')) {
            clearButton.addEventListener('click', function(e) {
                e.preventDefault();
                if (flatpickrInstance) {
                    flatpickrInstance.clear();
                    dateFromInput.value = '';
                    dateToInput.value = '';

                    // Trigger change event
                    var event = new Event('change', { bubbles: true });
                    dateInput.dispatchEvent(event);
                }
            });
        }
    }

    /**
     * Format date as Y-m-d for backend compatibility
     */
    function formatDate(date) {
        var year = date.getFullYear();
        var month = ('0' + (date.getMonth() + 1)).slice(-2);
        var day = ('0' + date.getDate()).slice(-2);
        return year + '-' + month + '-' + day;
    }

    /**
     * Format date for display (human readable)
     */
    function formatDateDisplay(dateStr) {
        if (!dateStr) return '';

        // Parse Y-m-d format manually to avoid timezone issues
        var parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr; // Return as-is if invalid format

        var year = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10);
        var day = parseInt(parts[2], 10);

        var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                          'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        // Format as "1 Sep 2025" (common readable format)
        return day + ' ' + monthNames[month - 1] + ' ' + year;
    }

    /**
     * Initialize on DOM ready
     */
    function init() {
        var dateInput = document.getElementById('wfn-date-range');
        if (!dateInput) {
            return;
        }

        // Check if we have existing date values and display them immediately
        var dateFromInput = document.getElementById('wfn-date-from');
        var dateToInput = document.getElementById('wfn-date-to');

        if (dateFromInput && dateToInput) {
            var existingFrom = dateFromInput.value;
            var existingTo = dateToInput.value;

            if (existingFrom && existingTo) {
                // Display existing dates in visible input immediately (formatted for readability)
                var displayFrom = formatDateDisplay(existingFrom);
                var displayTo = formatDateDisplay(existingTo);

                dateInput.value = displayFrom + ' to ' + displayTo;
                dateInput.placeholder = ''; // Clear placeholder when showing dates
            }
        }

        // Lazy load: Load Flatpickr assets only when user focuses on date field
        var hasInteracted = false;

        function handleInteraction() {
            if (hasInteracted) return;
            hasInteracted = true;

            // Show loading indicator
            dateInput.placeholder = 'Loading calendar...';
            dateInput.disabled = true;

            // Load assets and initialize
            loadFlatpickrAssets(function() {
                // Wait for Flatpickr global to be available
                var checkInterval = setInterval(function() {
                    if (typeof flatpickr !== 'undefined') {
                        clearInterval(checkInterval);
                        dateInput.placeholder = wfnFlatpickr.placeholder || 'Select date range...';
                        dateInput.disabled = false;
                        initializeFlatpickr();

                        // Trigger click to open calendar immediately
                        if (flatpickrInstance) {
                            flatpickrInstance.open();
                        }
                    }
                }, 50);
            });
        }

        // Attach lazy loading to click and focus events
        dateInput.addEventListener('click', handleInteraction);
        dateInput.addEventListener('focus', handleInteraction);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
