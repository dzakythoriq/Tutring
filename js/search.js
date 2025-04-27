/**
 * Tutring Search Page JavaScript
 * Handles interactive elements on the search page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Price range slider
    initPriceRangeSlider();
    
    // Star rating filter
    initRatingFilter();
    
    // Reset button
    initResetButton();
    
    // Submit form on filter change
    initAutoSubmit();
});

/**
 * Initialize price range slider functionality
 */
function initPriceRangeSlider() {
    const rangeInput = document.getElementById('price-range');
    if (!rangeInput) return;
    
    const minPriceDisplay = document.getElementById('min-price-display');
    const maxPriceDisplay = document.getElementById('max-price-display');
    const minPriceInput = document.getElementById('min-price');
    const maxPriceInput = document.getElementById('max-price');
    
    // Update display and hidden input when slider changes
    rangeInput.addEventListener('input', function() {
        maxPriceDisplay.textContent = this.value;
        maxPriceInput.value = this.value;
    });
}

/**
 * Initialize star rating filter functionality
 */
function initRatingFilter() {
    const stars = document.querySelectorAll('.rating-filter .star');
    if (stars.length === 0) return;
    
    const minRatingInput = document.getElementById('min-rating');
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.getAttribute('data-rating'));
            minRatingInput.value = rating;
            
            // Update active stars
            stars.forEach(s => {
                const sRating = parseInt(s.getAttribute('data-rating'));
                if (sRating <= rating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
            
            // Automatically submit the form when rating changes
            if (document.getElementById('auto-submit')?.checked) {
                document.getElementById('filter-form').submit();
            }
        });
    });
}

/**
 * Initialize reset button functionality
 */
function initResetButton() {
    const resetBtn = document.getElementById('reset-btn');
    if (!resetBtn) return;
    
    const filterForm = document.getElementById('filter-form');
    
    resetBtn.addEventListener('click', function() {
        // Reset search query
        const queryInput = document.getElementById('query');
        if (queryInput) queryInput.value = '';
        
        // Uncheck all subject checkboxes
        document.querySelectorAll('input[name="subjects[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Reset price range
        const rangeInput = document.getElementById('price-range');
        const minPriceDisplay = document.getElementById('min-price-display');
        const maxPriceDisplay = document.getElementById('max-price-display');
        const minPriceInput = document.getElementById('min-price');
        const maxPriceInput = document.getElementById('max-price');
        
        if (rangeInput) {
            rangeInput.value = 100;
            if (maxPriceDisplay) maxPriceDisplay.textContent = '100';
            if (minPriceInput) minPriceInput.value = '20';
            if (maxPriceInput) maxPriceInput.value = '100';
        }
        
        // Uncheck availability options
        document.querySelectorAll('input[name="availability[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Reset star rating
        const minRatingInput = document.getElementById('min-rating');
        const stars = document.querySelectorAll('.rating-filter .star');
        
        if (minRatingInput) minRatingInput.value = '0';
        stars.forEach(star => star.classList.remove('active'));
        
        // Submit the form
        if (filterForm) filterForm.submit();
    });
}

/**
 * Initialize auto-submit on filter changes
 */
function initAutoSubmit() {
    const autoSubmitCheckboxes = document.querySelectorAll('input[type="checkbox"][name="subjects[]"], input[type="checkbox"][name="availability[]"]');
    const filterForm = document.getElementById('filter-form');
    const autoSubmitEnabled = document.getElementById('auto-submit')?.checked || false;
    
    if (!filterForm || !autoSubmitEnabled) return;
    
    // Add event listeners to all checkboxes
    autoSubmitCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Add event listener to price range input (with debounce)
    const rangeInput = document.getElementById('price-range');
    if (rangeInput) {
        let debounceTimer;
        rangeInput.addEventListener('change', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    }
}

/**
 * Toggle advanced filter section visibility
 */
function toggleAdvancedFilters() {
    const advancedFilters = document.getElementById('advanced-filters');
    const toggleButton = document.getElementById('toggle-advanced-filters');
    
    if (advancedFilters && toggleButton) {
        if (advancedFilters.classList.contains('d-none')) {
            advancedFilters.classList.remove('d-none');
            toggleButton.innerHTML = 'Hide Advanced Filters <i class="fas fa-chevron-up"></i>';
        } else {
            advancedFilters.classList.add('d-none');
            toggleButton.innerHTML = 'Show Advanced Filters <i class="fas fa-chevron-down"></i>';
        }
    }
}

/**
 * Generate a schedule card for tutor availability
 */
function generateScheduleCard(schedule, tutorRate) {
    // Calculate duration and price
    const startTime = new Date(`2000-01-01T${schedule.start_time}`);
    const endTime = new Date(`2000-01-01T${schedule.end_time}`);
    const durationMs = endTime - startTime;
    const durationHours = durationMs / (1000 * 60 * 60);
    const totalPrice = (durationHours * tutorRate).toFixed(2);
    
    // Format times
    const formattedStartTime = startTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    const formattedEndTime = endTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    
    // Create card HTML
    return `
        <div class="col-md-6 mb-3">
            <div class="card h-100 border-primary">
                <div class="card-body">
                    <h6 class="card-title">
                        <i class="far fa-clock me-1"></i>
                        ${formattedStartTime} - ${formattedEndTime}
                    </h6>
                    <p class="card-text text-muted mb-1">
                        <small>Duration: ${Math.floor(durationHours)} hr ${Math.round((durationHours % 1) * 60)} min</small>
                    </p>
                    <p class="card-text mb-3">
                        <strong>Price: $${totalPrice}</strong>
                    </p>
                    <a href="booking.php?schedule=${schedule.id}" class="btn btn-primary w-100">
                        Book Now
                    </a>
                </div>
            </div>
        </div>
    `;
}

/**
 * Format date to display in a user-friendly format
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}