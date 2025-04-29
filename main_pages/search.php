<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';
require_once ROOT_PATH . 'configs/functions.php';
require_once ROOT_PATH . 'models/tutor.model.php';
require_once ROOT_PATH . 'models/schedule.model.php';

// Initialize models
$tutorModel = new Tutor($conn);
$scheduleModel = new Schedule($conn);

// Get search parameters
$searchQuery = isset($_GET['query']) ? sanitize($_GET['query']) : '';
$selectedSubjects = isset($_GET['subjects']) ? $_GET['subjects'] : [];
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 1.00;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 10.00;
$selectedDate = isset($_GET['selected_date']) ? sanitize($_GET['selected_date']) : '';
$minRating = isset($_GET['min_rating']) ? floatval($_GET['min_rating']) : 0;

// Check if viewing a specific tutor
$viewTutor = isset($_GET['tutor']) && is_numeric($_GET['tutor']) ? (int)$_GET['tutor'] : null;

if ($viewTutor) {
    // Get tutor details
    $tutor = $tutorModel->getById($viewTutor);
    
    if (!$tutor) {
        setMessage('Tutor not found', 'error');
        redirect('main_pages/search.php');
    }
    
    // Get tutor's available schedules (future dates only)
    $currentDate = date('Y-m-d');
    $availableSchedules = $scheduleModel->getAvailableByTutor($viewTutor, $currentDate);
    
    // Get tutor's rating
    $rating = $tutorModel->getAverageRating($viewTutor);
    
    // Set flag for view mode
    $viewMode = true;
} else {
    // Search tutors
    if (!empty($searchQuery)) {
        $tutors = $tutorModel->searchBySubject($searchQuery);
    } else {
        $tutors = $tutorModel->getAll();
    }
    
    // Filter by subject if selected
    if (!empty($selectedSubjects) && is_array($selectedSubjects)) {
        $tutors = array_filter($tutors, function($tutor) use ($selectedSubjects) {
            return in_array($tutor['subject'], $selectedSubjects);
        });
    }
    
    // Filter by price range
    $tutors = array_filter($tutors, function($tutor) use ($minPrice, $maxPrice) {
        return $tutor['hourly_rate'] >= $minPrice && $tutor['hourly_rate'] <= $maxPrice;
    });
    
    // Filter by date if selected
    if (!empty($selectedDate)) {
        // This would require additional logic to filter tutors based on their availability on the selected date
        // For now, we'll just leave the tutors as is, but in a real implementation, you'd filter based on schedule
    }
    
    // Filter by minimum rating
    if ($minRating > 0) {
        $tutors = array_filter($tutors, function($tutor) use ($tutorModel, $minRating) {
            $rating = $tutorModel->getAverageRating($tutor['id']);
            return $rating >= $minRating;
        });
    }
    
    // Set flag for list mode
    $viewMode = false;
}

// Get all available subjects for filter
$allSubjects = $tutorModel->getAllSubjects();

// Include header
include_once ROOT_PATH . 'views/header.php';
?>

<div class="container py-4">
    <?php if ($viewMode): ?>
        <!-- Tutor Detail View -->
        <!-- Keep your existing tutor detail view here -->
    <?php else: ?>
        <!-- Tutor Search and Listing with New UI -->
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4">Available Tutors</h2>
            </div>
        </div>
        
        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-md-3">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Filter Tutors</h6>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo BASE_URL; ?>/main_pages/search.php" method="get" id="filter-form">
                            <!-- Search -->
                            <div class="mb-3">
                                <label for="query" class="form-label">Search</label>
                                <input type="text" class="form-control" id="query" name="query" 
                                       placeholder="Search tutors..." value="<?= htmlspecialchars($searchQuery) ?>">
                            </div>
                            
                            <!-- Subjects -->
                            <div class="mb-3">
                                <label class="form-label">Subjects</label>
                                <?php foreach ($allSubjects as $subject): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="subjects[]" 
                                           value="<?= htmlspecialchars($subject) ?>" id="subject-<?= htmlspecialchars($subject) ?>"
                                           <?= in_array($subject, $selectedSubjects) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="subject-<?= htmlspecialchars($subject) ?>">
                                        <?= htmlspecialchars($subject) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Price Range - Updated to $1-$10 -->
                            <div class="mb-3">
                                <label class="form-label">Price Range</label>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>$<span id="min-price-display"><?= number_format($minPrice, 2) ?></span></span>
                                    <span>$<span id="max-price-display"><?= number_format($maxPrice, 2) ?></span>/hr</span>
                                </div>
                                <input type="hidden" name="min_price" id="min-price" value="<?= $minPrice ?>">
                                <input type="hidden" name="max_price" id="max-price" value="<?= $maxPrice ?>">
                                <input type="range" class="form-range" min="1" max="10" step="0.5" id="price-range" value="<?= $maxPrice ?>">
                            </div>
                            
                            <!-- Availability - Using Date Picker Instead of Options -->
                            <div class="mb-3">
                                <label class="form-label">Availability</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="selected_date" name="selected_date" 
                                           value="<?= $selectedDate ?>" min="<?= date('Y-m-d') ?>">
                                    <button class="btn btn-outline-secondary" type="button" id="clear-date">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Select a date to find tutors available on that day</small>
                            </div>
                            
                            <!-- Minimum Rating -->
                            <div class="mb-3">
                                <label class="form-label">Minimum Rating</label>
                                <div class="rating-filter">
                                    <input type="hidden" name="min_rating" id="min-rating" value="<?= $minRating ?>">
                                    <div class="d-flex">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <span class="star<?= $i <= $minRating ? ' active' : '' ?>" data-rating="<?= $i ?>">
                                                <i class="fas fa-star"></i>
                                            </span>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Filter Buttons -->
                            <div class="d-flex justify-content-between">
                                <button type="button" id="reset-btn" class="btn btn-secondary">Reset</button>
                                <button type="submit" class="btn btn-primary">Apply</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Tutor Listings -->
            <div class="col-md-9">
                <?php if (empty($tutors)): ?>
                    <div class="alert alert-info">
                        <p class="mb-0">No tutors found matching your criteria. Please try different filters.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($tutors as $tutor): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card tutor-card h-100 shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div>
                                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($tutor['name']) ?>&background=random" 
                                                     alt="<?= htmlspecialchars($tutor['name']) ?>" 
                                                     class="tutor-avatar rounded-circle" style="width: 64px; height: 64px;">
                                            </div>
                                            <div class="ms-3">
                                                <h5 class="card-title mb-1"><?= htmlspecialchars($tutor['name']) ?></h5>
                                                <p class="text-muted mb-0"><?= htmlspecialchars($tutor['subject']) ?></p>
                                                <div class="tutor-rating">
                                                    <?php
                                                    $rating = $tutorModel->getAverageRating($tutor['id']) ?: 0;
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        if ($i <= $rating) {
                                                            echo '<i class="fas fa-star text-warning"></i>';
                                                        } elseif ($i - 0.5 <= $rating) {
                                                            echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                                        } else {
                                                            echo '<i class="far fa-star text-warning"></i>';
                                                        }
                                                    }
                                                    ?>
                                                    <span class="ms-1 text-muted"><?= $rating ? number_format($rating, 1) : 'No ratings' ?></span>
                                                </div>
                                            </div>
                                            <div class="ms-auto">
                                                <span class="h5 text-primary"><?= formatCurrency($tutor['hourly_rate']) ?>/hr</span>
                                            </div>
                                        </div>
                                        
                                        <p class="card-text mb-3">
                                            <?= strlen($tutor['bio']) > 100 ? substr(htmlspecialchars($tutor['bio']), 0, 100) . '...' : htmlspecialchars($tutor['bio']) ?>
                                        </p>
                                        
                                        <div class="d-flex justify-content-between">
                                            <a href="<?php echo BASE_URL; ?>/main_pages/search.php?tutor=<?= $tutor['id'] ?>" 
                                               class="btn btn-outline-primary flex-grow-1 me-2">View Profile</a>
                                               
                                            <?php if (isLoggedIn() && isStudent()): ?>
                                                <a href="<?php echo BASE_URL; ?>/main_pages/search.php?tutor=<?= $tutor['id'] ?>#schedules" 
                                                   class="btn btn-primary flex-grow-1">Book Session</a>
                                            <?php elseif (!isLoggedIn()): ?>
                                                <a href="<?php echo BASE_URL; ?>/main_pages/login.php" 
                                                   class="btn btn-primary flex-grow-1">Login to Book</a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary flex-grow-1" disabled>Student Account Required</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Price range slider - Updated for $1-$10 range
    const rangeInput = document.getElementById('price-range');
    const minPriceDisplay = document.getElementById('min-price-display');
    const maxPriceDisplay = document.getElementById('max-price-display');
    const minPriceInput = document.getElementById('min-price');
    const maxPriceInput = document.getElementById('max-price');
    
    rangeInput.addEventListener('input', function() {
        maxPriceDisplay.textContent = parseFloat(this.value).toFixed(2);
        maxPriceInput.value = this.value;
    });
    
    // Star rating filter
    const stars = document.querySelectorAll('.rating-filter .star');
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
        });
    });
    
    // Clear date button
    const clearDateBtn = document.getElementById('clear-date');
    const datePicker = document.getElementById('selected_date');
    
    clearDateBtn.addEventListener('click', function() {
        datePicker.value = '';
    });
    
    // Reset button
    document.getElementById('reset-btn').addEventListener('click', function() {
        document.getElementById('query').value = '';
        
        // Uncheck all subject checkboxes
        document.querySelectorAll('input[name="subjects[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Reset price range
        rangeInput.value = 10;
        maxPriceDisplay.textContent = '10.00';
        minPriceInput.value = 1;
        maxPriceInput.value = 10;
        
        // Clear date
        datePicker.value = '';
        
        // Reset star rating
        minRatingInput.value = 0;
        stars.forEach(star => star.classList.remove('active'));
        
        // Submit the form
        document.getElementById('filter-form').submit();
    });
});
</script>

<style>
.tutor-avatar {
    object-fit: cover;
}

.rating-filter .star {
    cursor: pointer;
    color: #ccc;
    font-size: 24px;
    padding: 0 2px;
}

.rating-filter .star.active {
    color: #ffc107;
}

.rating-filter .star:hover {
    color: #ffc107;
}

.rating-filter .star:hover ~ .star {
    color: #ccc;
}
</style>

<?php
// Include footer
include_once ROOT_PATH . 'views/footer.php';
?>