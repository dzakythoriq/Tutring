<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'models/tutor.model.php';

// Get featured tutors (limited to 6)
$tutorModel = new Tutor($conn);
$featuredTutors = $tutorModel->getAll();

// Limit to 6 tutors
$featuredTutors = array_slice($featuredTutors, 0, 6);

// Include header
include_once 'views/header.php';
?>

<!-- Hero Section -->
<div class="hero text-center py-5 mb-4">
    <div class="container">
        <h1>Find the Perfect Tutor for Your Needs</h1>
        <p class="lead mb-4">Connect with qualified tutors, book sessions at your convenience, and excel in your studies</p>
        <div class="d-flex justify-content-center">
            <a href="search.php" class="btn btn-light btn-lg me-2">Find a Tutor</a>
            <?php if (!isLoggedIn()): ?>
                <a href="register.php?role=tutor" class="btn btn-outline-light btn-lg">Become a Tutor</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- How It Works Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">How Tutring Works</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-search"></i>
                        </div>
                        <h5 class="card-title">Find a Tutor</h5>
                        <p class="card-text">Browse through our database of qualified tutors and find the perfect match for your subject and learning goals.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h5 class="card-title">Book a Session</h5>
                        <p class="card-text">Select a convenient time slot from your tutor's schedule and book your session with just a few clicks.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h5 class="card-title">Learn & Succeed</h5>
                        <p class="card-text">Attend your tutoring sessions, learn from expert tutors, and achieve your academic goals.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Tutors Section -->
<?php if (!empty($featuredTutors)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Meet Our Featured Tutors</h2>
        <div class="row g-4">
            <?php foreach ($featuredTutors as $tutor): ?>
                <div class="col-md-4">
                    <div class="card h-100 tutor-card">
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