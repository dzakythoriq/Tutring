<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'models/tutor.model.php';
require_once 'models/schedule.model.php';

// Initialize models
$tutorModel = new Tutor($conn);
$scheduleModel = new Schedule($conn);

// Get search parameter
$searchSubject = isset($_GET['subject']) ? sanitize($_GET['subject']) : '';

// Check if viewing a specific tutor
$viewTutor = isset($_GET['tutor']) && is_numeric($_GET['tutor']) ? (int)$_GET['tutor'] : null;

if ($viewTutor) {
    // Get tutor details
    $tutor = $tutorModel->getById($viewTutor);
    
    if (!$tutor) {
        setMessage('Tutor not found', 'error');
        redirect('search.php');
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
    if (!empty($searchSubject)) {
        $tutors = $tutorModel->searchBySubject($searchSubject);
    } else {
        $tutors = $tutorModel->getAll();
    }
    
    // Set flag for list mode
    $viewMode = false;
}

// Include header
include_once 'views/header.php';
?>

<div class="container py-4">
    <?php if ($viewMode): ?>
        <!-- Tutor Detail View -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Tutor Profile</h6>
                        <a href="search.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Search
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($tutor['name']) ?>&background=random&size=200" alt="<?= htmlspecialchars($tutor['name']) ?>" class="img-fluid rounded-circle mb-3" style="max-width: 200px;">
                                
                                <h4><?= htmlspecialchars($tutor['name']) ?></h4>
                                <p class="text-muted mb-2"><?= htmlspecialchars($tutor['subject']) ?></p>
                                
                                <div class="tutor-rating mb-2">
                                    <?php if ($rating): ?>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $rating): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif ($i - 0.5 <= $rating): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        <span class="ms-1">(<?= $rating ?>)</span>
                                    <?php else: ?>
                                        <span class="text-muted">No ratings yet</span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="h5"><?= formatCurrency($tutor['hourly_rate']) ?> / hour</p>
                                
                                <p class="small text-muted">Member since <?= formatDate($tutor['created_at'], 'M Y') ?></p>
                            </div>
                            
                            <div class="col-md-8">
                                <h5>About the Tutor</h5>
                                <p><?= nl2br(htmlspecialchars($tutor['bio'])) ?></p>
                                
                                <hr>
                                
                                <h5>Subject Expertise</h5>
                                <p><?= htmlspecialchars($tutor['subject']) ?></p>
                                
                                <hr>
                                
                                <h5>Education & Experience</h5>
                                <p>The tutor hasn't added education details yet.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Available Schedules -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Available Time Slots</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($availableSchedules)): ?>
                            <div class="text-center py-4">
                                <p class="text-muted">No available schedules at the moment.</p>
                                <p>Please check back later or contact the tutor directly.</p>
                            </div>
                        <?php else: ?>
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
                            ?>
                            
                            <?php foreach ($schedulesByDate as $date => $daySchedules): ?>
                                <h6 class="font-weight-bold mb-3"><?= formatDate($date, 'l, d M Y') ?></h6>
                                
                                <div class="row row-cols-1 row-cols-md-2 g-3 mb-4">
                                    <?php foreach ($daySchedules as $schedule): ?>
                                        <div class="col">
                                            <div class="card h-100 border-primary">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <i class="far fa-clock me-1"></i>
                                                        <?= formatTime($schedule['start_time']) ?> - <?= formatTime($schedule['end_time']) ?>
                                                    </h6>
                                                    
                                                    <?php
                                                    // Calculate duration
                                                    $start = new DateTime($schedule['start_time']);
                                                    $end = new DateTime($schedule['end_time']);
                                                    $interval = $start->diff($end);
                                                    $hours = $interval->h;
                                                    $minutes = $interval->i;
                                                    
                                                    $durationText = '';
                                                    if ($hours > 0) {
                                                        $durationText .= $hours . ' hour' . ($hours > 1 ? 's' : '');
                                                    }
                                                    if ($minutes > 0) {
                                                        if ($hours > 0) {
                                                            $durationText .= ' and ';
                                                        }
                                                        $durationText .= $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                                                    }
                                                    
                                                    // Calculate total price
                                                    $totalHours = $hours + ($minutes / 60);
                                                    $totalPrice = $totalHours * $tutor['hourly_rate'];
                                                    ?>
                                                    
                                                    <p class="card-text text-muted mb-1">
                                                        <small>Duration: <?= $durationText ?></small>
                                                    </p>
                                                    <p class="card-text mb-3">
                                                        <strong>Price: <?= formatCurrency($totalPrice) ?></strong>
                                                    </p>
                                                    
                                                    <?php if (isLoggedIn() && isStudent()): ?>
                                                        <a href="booking.php?schedule=<?= $schedule['id'] ?>" class="btn btn-primary w-100">
                                                            Book Now
                                                        </a>
                                                    <?php elseif (!isLoggedIn()): ?>
                                                        <a href="login.php" class="btn btn-primary w-100">
                                                            Login to Book
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-secondary w-100" disabled>
                                                            Student Account Required
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Contact Information -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Contact Information</h6>
                    </div>
                    <div class="card-body">
                        <?php if (isLoggedIn()): ?>
                            <p>
                                <i class="fas fa-envelope me-2"></i>
                                <?= htmlspecialchars($tutor['email']) ?>
                            </p>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <p class="mb-0">Please <a href="login.php">login</a> to view contact information.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Similar Tutors -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Similar Tutors</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get similar tutors (same subject, excluding current tutor)
                        $similarTutors = $tutorModel->searchBySubject($tutor['subject']);
                        $similarTutors = array_filter($similarTutors, function($t) use ($viewTutor) {
                            return $t['id'] != $viewTutor;
                        });
                        
                        // Limit to 3
                        $similarTutors = array_slice($similarTutors, 0, 3);
                        ?>
                        
                        <?php if (empty($similarTutors)): ?>
                            <p class="text-muted">No similar tutors found.</p>
                        <?php else: ?>
                            <?php foreach ($similarTutors as $similarTutor): ?>
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($similarTutor['name']) ?>&background=random" alt="<?= htmlspecialchars($similarTutor['name']) ?>" class="tutor-avatar" style="width: 48px; height: 48px;">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-0"><?= htmlspecialchars($similarTutor['name']) ?></h6>
                                        <p class="text-muted mb-1"><small><?= htmlspecialchars($similarTutor['subject']) ?></small></p>
                                        <a href="search.php?tutor=<?= $similarTutor['id'] ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Tutor Search and Listing -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-body">
                        <form action="search.php" method="get" class="row g-3">
                            <div class="col-md-10">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="subject" name="subject" placeholder="Search by subject (e.g., Math, Physics, English)" value="<?= htmlspecialchars($searchSubject) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <?php if (empty($tutors)): ?>
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <p class="mb-0">No tutors found. Please try a different search term.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($tutors as $tutor): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card tutor-card h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($tutor['name']) ?>&background=random" alt="<?= htmlspecialchars($tutor['name']) ?>" class="tutor-avatar">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($tutor['name']) ?></h5>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($tutor['subject']) ?></p>
                                    </div>
                                </div>
                                
                                <p class="card-text">
                                    <?= strlen($tutor['bio']) > 100 ? substr(htmlspecialchars($tutor['bio']), 0, 100) . '...' : htmlspecialchars($tutor['bio']) ?>
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="tutor-rating">
                                        <?php
                                        $rating = $tutorModel->getAverageRating($tutor['id']);
                                        if ($rating) {
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } elseif ($i - 0.5 <= $rating) {
                                                    echo '<i class="fas fa-star-half-alt"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            echo ' <small>(' . $rating . ')</small>';
                                        } else {
                                            echo '<small class="text-muted">No ratings yet</small>';
                                        }
                                        ?>
                                    </div>
                                    <div class="tutor-price">
                                        <strong><?= formatCurrency($tutor['hourly_rate']) ?></strong> / hour
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0">
                                <a href="search.php?tutor=<?= $tutor['id'] ?>" class="btn btn-primary w-100">View Profile</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once 'views/footer.php';
?>