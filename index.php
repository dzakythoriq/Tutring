<?php
// Define root path for includes
define('ROOT_PATH', __DIR__ . '/');

require_once ROOT_PATH . 'configs/config.php';
require_once ROOT_PATH . 'configs/functions.php';
require_once ROOT_PATH . 'models/tutor.model.php';

// Get featured tutors (limited to 6)
$tutorModel = new Tutor($conn);
$featuredTutors = $tutorModel->getAll();

// Limit to 6 tutors
$featuredTutors = array_slice($featuredTutors, 0, 6);

// Include header
include_once ROOT_PATH . 'views/header.php';
?>

<!-- Hero Section -->
<div class="hero text-center py-5 mb-4">
    <div class="container">
        <h1>Find the Perfect Tutor for Your Needs</h1>
        <p class="lead mb-4">Connect with qualified tutors, book sessions at your convenience, and excel in your studies</p>
        <div class="d-flex justify-content-center">
            <a href="<?php echo BASE_URL; ?>/main_pages/search.php" class="btn btn-light btn-lg me-2">Find a Tutor</a>
            <?php if (!isLoggedIn()): ?>
                <a href="<?php echo BASE_URL; ?>/main_pages/register.php?role=tutor" class="btn btn-outline-light btn-lg">Become a Tutor</a>
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
                            <a href="<?php echo BASE_URL; ?>/main_pages/search.php?tutor=<?= $tutor['id'] ?>" class="btn btn-primary w-100">View Profile</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Testimonials Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">What Our Students Say</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-quote-left fa-2x text-primary opacity-50"></i>
                        </div>
                        <p class="card-text">Tutring helped me find the perfect math tutor. I went from struggling with calculus to acing my exams. The platform is so easy to use!</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="flex-shrink-0">
                                <img src="https://ui-avatars.com/api/?name=Sarah+Johnson&background=random" alt="Sarah Johnson" class="rounded-circle" width="50" height="50">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Sarah Johnson</h6>
                                <p class="text-muted mb-0"><small>College Student</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-quote-left fa-2x text-primary opacity-50"></i>
                        </div>
                        <p class="card-text">As a parent, I appreciate how easy it is to book sessions for my son. His grades in science have improved significantly since we started using Tutring.</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="flex-shrink-0">
                                <img src="https://ui-avatars.com/api/?name=Michael+Rodriguez&background=random" alt="Michael Rodriguez" class="rounded-circle" width="50" height="50">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Michael Rodriguez</h6>
                                <p class="text-muted mb-0"><small>Parent</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-quote-left fa-2x text-primary opacity-50"></i>
                        </div>
                        <p class="card-text">I've been tutoring on Tutring for six months now. The platform makes it easy to manage my schedule and connect with students who need help with English literature.</p>
                        <div class="d-flex align-items-center mt-3">
                            <div class="flex-shrink-0">
                                <img src="https://ui-avatars.com/api/?name=Emily+Chen&background=random" alt="Emily Chen" class="rounded-circle" width="50" height="50">
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">Emily Chen</h6>
                                <p class="text-muted mb-0"><small>English Tutor</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="mb-4">Ready to Excel in Your Studies?</h2>
        <p class="lead mb-4">Join Tutring today and connect with expert tutors in any subject.</p>
        <div class="d-flex justify-content-center">
            <?php if (!isLoggedIn()): ?>
                <a href="<?php echo BASE_URL; ?>/main_pages/register.php" class="btn btn-light btn-lg me-2">Sign Up Now</a>
                <a href="<?php echo BASE_URL; ?>/main_pages/search.php" class="btn btn-outline-light btn-lg">Browse Tutors</a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/main_pages/search.php" class="btn btn-light btn-lg">Find a Tutor</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// Include footer
include_once ROOT_PATH . 'views/footer.php';
?>