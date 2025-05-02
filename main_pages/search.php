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
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <div>
                                <?php if (!empty($tutor['photo'])): ?>
                                    <img src="<?php echo BASE_URL . '/' . $tutor['photo']; ?>" alt="<?= htmlspecialchars($tutor['name']) ?>" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                                <?php else: ?>
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($tutor['name']) ?>&background=random" alt="<?= htmlspecialchars($tutor['name']) ?>" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                            <div class="ms-4">
                                <h2 class="mb-1"><?= htmlspecialchars($tutor['name']) ?></h2>
                                <p class="text-muted mb-0"><i class="fas fa-book me-2"></i><?= htmlspecialchars($tutor['subject']) ?></p>
                                <div class="mt-2">
                                    <?php
                                    if ($rating) {
                                        echo '<div class="d-flex align-items-center">';
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star text-warning"></i>';
                                            } elseif ($i - 0.5 <= $rating) {
                                                echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                            } else {
                                                echo '<i class="far fa-star text-warning"></i>';
                                            }
                                        }
                                        echo '<span class="ms-2">(' . $rating . ' out of 5)</span>';
                                        echo '</div>';
                                    } else {
                                        echo '<span class="text-muted">No ratings yet</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="ms-auto text-end">
                                <h3 class="text-primary mb-2"><?= formatCurrency($tutor['hourly_rate']) ?></h3>
                                <p class="text-muted mb-0">per hour</p>
                            </div>
                        </div>
                        
                        <h5 class="mb-3">About Me</h5>
                        <p><?= nl2br(htmlspecialchars($tutor['bio'])) ?></p>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Subject Expertise</h5>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <span class="badge bg-primary"><?= htmlspecialchars($tutor['subject']) ?></span>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="mb-3">Contact Information</h5>
                        <p><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($tutor['email']) ?></p>
                        
                        <div class="mt-4">
                            <a href="<?php echo BASE_URL; ?>/main_pages/search.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Search
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm sticky-md-top mb-4" style="top: 20px; z-index: 1000;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Book a Session</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!isLoggedIn()): ?>
                            <div class="alert alert-info" role="alert">
                                <h6><i class="fas fa-info-circle me-2"></i>Login Required</h6>
                                <p class="mb-3">You need to login to book a session with this tutor.</p>
                                <div class="d-grid gap-2">
                                    <a href="<?php echo BASE_URL; ?>/main_pages/login.php" class="btn btn-primary">Login</a>
                                    <a href="<?php echo BASE_URL; ?>/main_pages/register.php" class="btn btn-outline-secondary">Create Account</a>
                                </div>
                            </div>
                        <?php elseif (!isStudent()): ?>
                            <div class="alert alert-warning" role="alert">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Student Account Required</h6>
                                <p class="mb-0">Only students can book tutoring sessions. If you'd like to book a session, please register a student account.</p>
                            </div>
                        <?php else: ?>
                            <?php if (empty($availableSchedules)): ?>
                                <div class="text-center py-4">
                                    <div class="mb-3">
                                        <i class="far fa-calendar-times fa-3x text-muted"></i>
                                    </div>
                                    <h6>No Available Sessions</h6>
                                    <p class="text-muted">This tutor doesn't have any available time slots at the moment. Please check back later.</p>
                                </div>
                            <?php else: ?>
                                <p class="mb-3">Select from available time slots:</p>
                                
                                <!-- Date Selection Tabs -->
                                <?php
                                // Group schedules by date
                                $schedulesByDate = [];
                                foreach ($availableSchedules as $schedule) {
                                    $date = $schedule['date'];
                                    if (!isset($schedulesByDate[$date])) {
                                        $schedulesByDate[$date] = [];
                                    }
                                    $schedulesByDate[$date][] = $schedule;
                                }
                                
                                // Sort dates
                                ksort($schedulesByDate);
                                
                                // Get first date as active
                                $firstDate = !empty($schedulesByDate) ? array_key_first($schedulesByDate) : null;
                                ?>
                                
                                <ul class="nav nav-tabs mb-3" id="dateTab" role="tablist">
                                    <?php $tabIndex = 0; ?>
                                    <?php foreach ($schedulesByDate as $date => $schedules): ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link <?= ($tabIndex === 0) ? 'active' : '' ?>" 
                                                    id="date-<?= $date ?>-tab" 
                                                    data-bs-toggle="tab" 
                                                    data-bs-target="#date-<?= $date ?>" 
                                                    type="button" 
                                                    role="tab" 
                                                    aria-controls="date-<?= $date ?>" 
                                                    aria-selected="<?= ($tabIndex === 0) ? 'true' : 'false' ?>">
                                                <?= date('M j', strtotime($date)) ?>
                                            </button>
                                        </li>
                                        <?php $tabIndex++; ?>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <div class="tab-content" id="dateTabContent">
                                    <?php $tabIndex = 0; ?>
                                    <?php foreach ($schedulesByDate as $date => $schedules): ?>
                                        <div class="tab-pane fade <?= ($tabIndex === 0) ? 'show active' : '' ?>" 
                                             id="date-<?= $date ?>" 
                                             role="tabpanel" 
                                             aria-labelledby="date-<?= $date ?>-tab">
                                            
                                            <h6 class="mb-3"><?= date('l, F j, Y', strtotime($date)) ?></h6>
                                            
                                            <div class="d-grid gap-2">
                                                <?php foreach ($schedules as $schedule): ?>
                                                    <?php
                                                    // Calculate duration
                                                    $startTime = new DateTime($schedule['start_time']);
                                                    $endTime = new DateTime($schedule['end_time']);
                                                    $duration = $startTime->diff($endTime);
                                                    $hours = $duration->h;
                                                    $minutes = $duration->i;
                                                    
                                                    // Calculate price
                                                    $durationHours = $hours + ($minutes / 60);
                                                    $price = $durationHours * $tutor['hourly_rate'];
                                                    
                                                    // Format times
                                                    $formattedStartTime = $startTime->format('g:i A');
                                                    $formattedEndTime = $endTime->format('g:i A');
                                                    ?>
                                                    
                                                    <div class="card mb-2 booking-card">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <h6 class="mb-1"><i class="far fa-clock me-2"></i><?= $formattedStartTime ?> - <?= $formattedEndTime ?></h6>
                                                                    <p class="text-muted mb-0 small">
                                                                        <span>Duration: 
                                                                            <?= $hours > 0 ? $hours . ' hr' . ($hours > 1 ? 's' : '') : '' ?>
                                                                            <?= ($hours > 0 && $minutes > 0) ? ' ' : '' ?>
                                                                            <?= $minutes > 0 ? $minutes . ' min' : '' ?>
                                                                        </span>
                                                                    </p>
                                                                </div>
                                                                <div class="text-end">
                                                                    <p class="text-primary fw-bold mb-1"><?= formatCurrency($price) ?></p>
                                                                    <a href="<?php echo BASE_URL; ?>/main_pages/booking.php?schedule=<?= $schedule['id'] ?>" class="btn btn-sm btn-primary">
                                                                        Book Now
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php $tabIndex++; ?>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-3">
                                    <i class="fas fa-info-circle me-2 text-muted"></i>
                                    <small class="text-muted">Click on a time slot to book your session. Payment will be handled during the session.</small>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Additional tutor info card -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Why Choose This Tutor?</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary p-2 me-3" style="width: 35px; height: 35px;">
                                    <i class="fas fa-user-graduate text-white d-flex justify-content-center"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Expert in <?= htmlspecialchars($tutor['subject']) ?></h6>
                                    <p class="mb-0 small text-muted">Specialized knowledge</p>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-success p-2 me-3" style="width: 35px; height: 35px;">
                                    <i class="fas fa-calendar-check text-white d-flex justify-content-center"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Flexible Schedule</h6>
                                    <p class="mb-0 small text-muted">Book at your convenience</p>
                                </div>
                            </li>
                            <li class="d-flex align-items-center">
                                <div class="rounded-circle bg-info p-2 me-3" style="width: 35px; height: 35px;">
                                    <i class="fas fa-chalkboard-teacher text-white d-flex justify-content-center"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Personalized Approach</h6>
                                    <p class="mb-0 small text-muted">Tailored to your needs</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Student Testimonials Section (if any) -->
        <?php
        // Here you would retrieve and display reviews/testimonials for this tutor
        // For now, we'll just show a placeholder
        ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">How Booking Works</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="p-3">
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-search text-white fs-4"></i>
                                    </div>
                                    <h5>1. Find a Tutor</h5>
                                    <p class="text-muted">Browse profiles and choose the right tutor for you</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-calendar-alt text-white fs-4"></i>
                                    </div>
                                    <h5>2. Select a Time</h5>
                                    <p class="text-muted">Choose from available time slots that work for you</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-check-circle text-white fs-4"></i>
                                    </div>
                                    <h5>3. Confirm Booking</h5>
                                    <p class="text-muted">Review details and confirm your tutoring session</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;">
                                        <i class="fas fa-graduation-cap text-white fs-4"></i>
                                    </div>
                                    <h5>4. Start Learning</h5>
                                    <p class="text-muted">Meet with your tutor and achieve your academic goals</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
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
                                                <?php if (!empty($tutor['photo'])): ?>
                                                    <img src="<?php echo BASE_URL . '/' . $tutor['photo']; ?>" alt="<?= htmlspecialchars($tutor['name']) ?>" class="rounded-circle" style="width: 64px; height: 64px; object-fit: cover;">
                                                <?php else: ?>
                                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($tutor['name']) ?>&background=random" 
                                                         alt="<?= htmlspecialchars($tutor['name']) ?>" 
                                                         class="tutor-avatar rounded-circle" style="width: 64px; height: 64px;">
                                                <?php endif; ?>
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
                
                <!-- Booking Information Section for Students -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">How to Book a Tutoring Session</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3">Simple Booking Process</h6>
                                <ol>
                                    <li><strong>Browse Tutor Profiles</strong> - Find a tutor that matches your subject needs and preferred teaching style.</li>
                                    <li><strong>View Available Time Slots</strong> - Each tutor sets their own availability. Choose a time slot that works for your schedule.</li>
                                    <li><strong>Book Your Session</strong> - With just a few clicks, confirm your booking and reserve your spot.</li>
                                    <li><strong>Receive Confirmation</strong> - Once the tutor approves your request, you'll receive a confirmation notification.</li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-3">Benefits of Online Booking</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Flexibility</strong> - Choose sessions that fit your schedule
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Convenience</strong> - Book anytime, anywhere from your device
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Transparent Pricing</strong> - See session costs upfront
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        <strong>Variety of Tutors</strong> - Find the perfect match for your learning style
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3 mb-0">
                            <div class="d-flex">
                                <div class="me-3">
                                    <i class="fas fa-info-circle fa-2x"></i>
                                </div>
                                <div>
                                    <h6>Ready to start learning?</h6>
                                    <p class="mb-2">Select a tutor from the list above, view their profile, and book a session that fits your schedule. If you haven't already, you'll need to <a href="<?php echo BASE_URL; ?>/main_pages/register.php">create a student account</a> to book sessions.</p>
                                    <p class="mb-0">Need help finding the right tutor? Use the filters on the left to narrow down your options by subject, price, rating, or availability.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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

/* Booking card hover effect */
.booking-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.booking-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Card border utilities */
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}
</style>

<?php
// Include footer
include_once ROOT_PATH . 'views/footer.php';
?>