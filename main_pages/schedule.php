<?php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

require_once ROOT_PATH . 'configs/config.php';
require_once ROOT_PATH . 'configs/functions.php';
require_once ROOT_PATH . 'configs/auth.php';
require_once ROOT_PATH . 'configs/validation.php';
require_once ROOT_PATH . 'models/schedule.model.php';
require_once ROOT_PATH . 'models/tutor.model.php';

// Require login and tutor role
requireLogin('tutor');

// Initialize models
$scheduleModel = new Schedule($conn);
$tutorModel = new Tutor($conn);

// Get tutor ID from session
$tutorData = $tutorModel->getByUserId($_SESSION['user_id']);
if (!$tutorData) {
    setMessage('Tutor profile not found', 'error');
    redirect('main_pages/dashboard.php');
}
$tutorId = $tutorData['id'];

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new schedule
    if (isset($_POST['action']) && $_POST['action'] === 'add_schedule') {
        $date = sanitize($_POST['date']);
        $startTime = sanitize($_POST['start_time']);
        $endTime = sanitize($_POST['end_time']);
        
        // Validate schedule
        $validateResult = validateSchedule($date, $startTime, $endTime);
        
        if ($validateResult['status']) {
            // Create schedule
            $scheduleData = [
                'tutor_id' => $tutorId,
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime
            ];
            
            if ($scheduleModel->create($scheduleData)) {
                setMessage('Schedule added successfully', 'success');
            } else {
                setMessage('Failed to add schedule', 'error');
            }
        } else {
            setMessage($validateResult['message'], 'error');
        }
    }
    
    // Delete schedule
    if (isset($_POST['action']) && $_POST['action'] === 'delete_schedule') {
        $scheduleId = (int)$_POST['schedule_id'];
        
        if ($scheduleModel->delete($scheduleId)) {
            setMessage('Schedule deleted successfully', 'success');
        } else {
            setMessage('Failed to delete schedule. It might be already booked.', 'error');
        }
    }
    
    // Update schedule
    if (isset($_POST['action']) && $_POST['action'] === 'update_schedule') {
        $scheduleId = (int)$_POST['schedule_id'];
        $date = sanitize($_POST['date']);
        $startTime = sanitize($_POST['start_time']);
        $endTime = sanitize($_POST['end_time']);
        
        // Validate schedule
        $validateResult = validateSchedule($date, $startTime, $endTime);
        
        if ($validateResult['status']) {
            // Update schedule
            $scheduleData = [
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime
            ];
            
            if ($scheduleModel->update($scheduleId, $scheduleData)) {
                setMessage('Schedule updated successfully', 'success');
            } else {
                setMessage('Failed to update schedule. It might be already booked.', 'error');
            }
        } else {
            setMessage($validateResult['message'], 'error');
        }
    }
    
    // Add recurring schedules
    if (isset($_POST['action']) && $_POST['action'] === 'add_recurring') {
        $startDate = sanitize($_POST['start_date']);
        $endDate = sanitize($_POST['end_date']);
        $dayOfWeek = $_POST['day_of_week']; // Array of days (0-6, Sunday-Saturday)
        $startTime = sanitize($_POST['recurring_start_time']);
        $endTime = sanitize($_POST['recurring_end_time']);
        
        // Validate basic inputs
        $validateStartTime = validateTime($startTime);
        $validateEndTime = validateTime($endTime);
        
        if (!$validateStartTime || !$validateEndTime) {
            setMessage('Invalid time format. Use HH:MM', 'error');
        } elseif (strtotime($endTime) <= strtotime($startTime)) {
            setMessage('End time must be after start time', 'error');
        } elseif (!validateDate($startDate) || !validateDate($endDate)) {
            setMessage('Invalid date format. Use YYYY-MM-DD', 'error');
        } elseif (strtotime($endDate) < strtotime($startDate)) {
            setMessage('End date must be after start date', 'error');
        } elseif (empty($dayOfWeek) || !is_array($dayOfWeek)) {
            setMessage('Please select at least one day of the week', 'error');
        } else {
            // Calculate time slot duration
            $startTimeObj = new DateTime($startTime);
            $endTimeObj = new DateTime($endTime);
            $interval = $startTimeObj->diff($endTimeObj);
            $minutes = ($interval->h * 60) + $interval->i;
            
            if ($minutes < 30) {
                setMessage('Schedule must be at least 30 minutes', 'error');
            } else {
                // Loop through dates and create schedules
                $startDateObj = new DateTime($startDate);
                $endDateObj = new DateTime($endDate);
                $successCount = 0;
                $failCount = 0;
                
                while ($startDateObj <= $endDateObj) {
                    $currentDayOfWeek = (int)$startDateObj->format('w'); // 0 (Sunday) - 6 (Saturday)
                    
                    // Check if current day is selected
                    if (in_array($currentDayOfWeek, $dayOfWeek)) {
                        $currentDate = $startDateObj->format('Y-m-d');
                        
                        // Create schedule
                        $scheduleData = [
                            'tutor_id' => $tutorId,
                            'date' => $currentDate,
                            'start_time' => $startTime,
                            'end_time' => $endTime
                        ];
                        
                        if ($scheduleModel->create($scheduleData)) {
                            $successCount++;
                        } else {
                            $failCount++;
                        }
                    }
                    
                    // Move to next day
                    $startDateObj->modify('+1 day');
                }
                
                if ($successCount > 0) {
                    setMessage("Successfully added {$successCount} schedule(s)" . ($failCount > 0 ? ", {$failCount} failed" : ""), $failCount > 0 ? 'warning' : 'success');
                } else {
                    setMessage('Failed to add any schedules', 'error');
                }
            }
        }
    }
    
    // Redirect to refresh the page
    redirect('main_pages/schedule.php');
}

// Get tutor's schedules
$schedules = $scheduleModel->getByTutor($tutorId);

// Get current date
$currentDate = date('Y-m-d');

// Group schedules by date
$schedulesByDate = [];
foreach ($schedules as $schedule) {
    $date = $schedule['date'];
    if (!isset($schedulesByDate[$date])) {
        $schedulesByDate[$date] = [];
    }
    $schedulesByDate[$date][] = $schedule;
}

// Sort dates
ksort($schedulesByDate);

// Include header
include_once ROOT_PATH . 'views/header.php';
?>

<div class="container py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Your Teaching Schedule</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
            <i class="fas fa-plus-circle me-1"></i> Add New Time Slot
        </button>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About Scheduling</h5>
                </div>
                <div class="card-body">
                    <p>Create your teaching schedule to let students book sessions with you. You can add individual time slots or recurring schedules.</p>
                    
                    <h6 class="mt-3 mb-2">Tips for Effective Scheduling:</h6>
                    <ul>
                        <li>Set consistent weekly hours to attract regular students</li>
                        <li>Allow at least 30-minute breaks between sessions</li>
                        <li>Create sessions of different lengths (30 min, 1 hour, 2 hours)</li>
                        <li>Mark your availability at least 2 weeks in advance</li>
                    </ul>
                    
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRecurringModal">
                            <i class="fas fa-calendar-alt me-1"></i> Add Recurring Schedule
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Schedule Statistics</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Count total schedules
                    $totalSchedules = count($schedules);
                    
                    // Count future schedules
                    $futureSchedules = array_filter($schedules, function($schedule) use ($currentDate) {
                        return $schedule['date'] >= $currentDate;
                    });
                    $futureScheduleCount = count($futureSchedules);
                    
                    // Count booked schedules
                    $bookedSchedules = array_filter($schedules, function($schedule) {
                        return $schedule['is_booked'] == 1;
                    });
                    $bookedScheduleCount = count($bookedSchedules);
                    
                    // Count available future schedules
                    $availableFutureSchedules = array_filter($futureSchedules, function($schedule) {
                        return $schedule['is_booked'] == 0;
                    });
                    $availableFutureScheduleCount = count($availableFutureSchedules);
                    ?>
                    
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <h2 class="h4 fw-bold text-primary"><?= $totalSchedules ?></h2>
                            <p class="text-muted mb-0">Total Time Slots</p>
                        </div>
                        <div class="col-6 mb-3">
                            <h2 class="h4 fw-bold text-success"><?= $futureScheduleCount ?></h2>
                            <p class="text-muted mb-0">Future Slots</p>
                        </div>
                        <div class="col-6">
                            <h2 class="h4 fw-bold text-info"><?= $availableFutureScheduleCount ?></h2>
                            <p class="text-muted mb-0">Available</p>
                        </div>
                        <div class="col-6">
                            <h2 class="h4 fw-bold text-warning"><?= $bookedScheduleCount ?></h2>
                            <p class="text-muted mb-0">Booked</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Your Schedule</h5>
                    <div>
                        <button id="view-calendar" class="btn btn-sm btn-outline-primary me-2">
                            <i class="fas fa-calendar-alt me-1"></i> Calendar View
                        </button>
                        <button id="view-list" class="btn btn-sm btn-primary">
                            <i class="fas fa-list me-1"></i> List View
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Calendar View (hidden by default) -->
                    <div id="calendar-container" class="mb-4" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <button id="prev-month" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-chevron-left"></i> Previous
                            </button>
                            <h5 id="current-month-display" class="mb-0">Month Year</h5>
                            <button id="next-month" class="btn btn-sm btn-outline-secondary">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <div id="calendar" class="mb-4"></div>
                    </div>
                    
                    <!-- List View -->
                    <div id="list-container">
                        <?php if (empty($schedulesByDate)): ?>
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-calendar-times fa-4x text-muted"></i>
                                </div>
                                <h4>No Schedules Found</h4>
                                <p class="text-muted">You haven't added any time slots yet.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                                    <i class="fas fa-plus-circle me-1"></i> Add Your First Time Slot
                                </button>
                            </div>
                        <?php else: ?>
                            <!-- Group by Month -->
                            <?php
                            $schedulesByMonth = [];
                            foreach ($schedulesByDate as $date => $dateSchedules) {
                                $month = date('F Y', strtotime($date));
                                if (!isset($schedulesByMonth[$month])) {
                                    $schedulesByMonth[$month] = [];
                                }
                                $schedulesByMonth[$month][$date] = $dateSchedules;
                            }
                            
                            // Display schedules by month
                            foreach ($schedulesByMonth as $month => $monthSchedules):
                            ?>
                                <div class="schedule-month mb-4">
                                    <h4 class="border-bottom pb-2"><?= $month ?></h4>
                                    
                                    <?php 
                                    // Sort dates within month
                                    ksort($monthSchedules);
                                    
                                    // Display schedules for each date
                                    foreach ($monthSchedules as $date => $dateSchedules): 
                                        // Check if date is in the past
                                        $isPastDate = $date < $currentDate;
                                    ?>
                                        <div class="schedule-date mb-3 <?= $isPastDate ? 'past-date' : '' ?>">
                                            <h5 class="<?= $isPastDate ? 'text-muted' : '' ?>">
                                                <?= formatDate($date, 'l, F j, Y') ?>
                                                <?= $isPastDate ? '<span class="badge bg-secondary ms-2">Past</span>' : '' ?>
                                            </h5>
                                            
                                            <div class="row">
                                                <?php foreach ($dateSchedules as $schedule): ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card h-100 <?= $schedule['is_booked'] ? 'border-success' : 'border-primary' ?>">
                                                            <div class="card-body p-3">
                                                                <div class="d-flex justify-content-between">
                                                                    <h6 class="card-title mb-1">
                                                                        <i class="far fa-clock me-1"></i>
                                                                        <?= formatTime($schedule['start_time']) ?> - <?= formatTime($schedule['end_time']) ?>
                                                                    </h6>
                                                                    <span class="badge <?= $schedule['is_booked'] ? 'bg-success' : 'bg-primary' ?>">
                                                                        <?= $schedule['is_booked'] ? 'Booked' : 'Available' ?>
                                                                    </span>
                                                                </div>
                                                                
                                                                <?php
                                                                // Calculate duration
                                                                $startTime = new DateTime($schedule['start_time']);
                                                                $endTime = new DateTime($schedule['end_time']);
                                                                $interval = $startTime->diff($endTime);
                                                                $hours = $interval->h;
                                                                $minutes = $interval->i;
                                                                
                                                                $durationText = '';
                                                                if ($hours > 0) {
                                                                    $durationText .= $hours . ' hour' . ($hours > 1 ? 's' : '');
                                                                }
                                                                if ($minutes > 0) {
                                                                    if ($hours > 0) {
                                                                        $durationText .= ' ';
                                                                    }
                                                                    $durationText .= $minutes . ' min';
                                                                }
                                                                ?>
                                                                
                                                                <p class="card-text text-muted mb-3 small">
                                                                    <i class="fas fa-hourglass-half me-1"></i> Duration: <?= $durationText ?>
                                                                </p>
                                                                
                                                                <?php if (!$schedule['is_booked'] && !$isPastDate): ?>
                                                                    <div class="d-flex">
                                                                        <button class="btn btn-sm btn-outline-primary me-2 edit-schedule-btn" 
                                                                                data-id="<?= $schedule['id'] ?>"
                                                                                data-date="<?= $schedule['date'] ?>"
                                                                                data-start="<?= $schedule['start_time'] ?>"
                                                                                data-end="<?= $schedule['end_time'] ?>">
                                                                            <i class="fas fa-edit"></i> Edit
                                                                        </button>
                                                                        <form action="<?= BASE_URL ?>/main_pages/schedule.php" method="post" class="d-inline delete-schedule-form">
                                                                            <input type="hidden" name="action" value="delete_schedule">
                                                                            <input type="hidden" name="schedule_id" value="<?= $schedule['id'] ?>">
                                                                            <button type="button" class="btn btn-sm btn-outline-danger delete-schedule-btn">
                                                                                <i class="fas fa-trash-alt"></i> Delete
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                <?php elseif ($schedule['is_booked']): ?>
                                                                    <a href="<?= BASE_URL ?>/main_pages/booking.php?id=<?= $schedule['booking_id'] ?>" class="btn btn-sm btn-success">
                                                                        <i class="fas fa-eye"></i> View Booking
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center mt-3">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                                    <i class="fas fa-plus-circle me-1"></i> Add More Time Slots
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addScheduleModalLabel">Add New Time Slot</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= BASE_URL ?>/main_pages/schedule.php" method="post" id="add-schedule-form">
                    <input type="hidden" name="action" value="add_schedule">
                    
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                    </div>
                    
                    <div class="form-text mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Time slots must be at least 30 minutes long. Choose a duration that works best for your teaching style.
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Add Time Slot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editScheduleModalLabel">Edit Time Slot</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= BASE_URL ?>/main_pages/schedule.php" method="post" id="edit-schedule-form">
                    <input type="hidden" name="action" value="update_schedule">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">
                    
                    <div class="mb-3">
                        <label for="edit_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="edit_date" name="date" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                        </div>
                    </div>
                    
                    <div class="form-text mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Time slots must be at least 30 minutes long.
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Update Time Slot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Recurring Schedule Modal -->
<div class="modal fade" id="addRecurringModal" tabindex="-1" aria-labelledby="addRecurringModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addRecurringModalLabel">Add Recurring Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= BASE_URL ?>/main_pages/schedule.php" method="post" id="add-recurring-form">
                    <input type="hidden" name="action" value="add_recurring">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Days of Week</label>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="day_of_week[]" id="day_0" value="0">
                                <label class="form-check-label" for="day_0">Sunday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="day_of_week[]" id="day_1" value="1">
                                <label class="form-check-label" for="day_1">Monday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="day_of_week[]" id="day_2" value="2">
                                <label class="form-check-label" for="day_2">Tuesday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="day_of_week[]" id="day_3" value="3">
                                <label class="form-check-label" for="day_3">Wednesday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="day_of_week[]" id="day_4" value="4">
                                <label class="form-check-label" for="day_4">Thursday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="day_of_week[]" id="day_5" value="5">
                                <label class="form-check-label" for="day_5">Friday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="day_of_week[]" id="day_6" value="6">
                                <label class="form-check-label" for="day_6">Saturday</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="recurring_start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="recurring_start_time" name="recurring_start_time" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="recurring_end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="recurring_end_time" name="recurring_end_time" required>
                        </div>
                    </div>
                    
                    <div class="form-text mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        This will create multiple time slots based on your selections. For example, selecting Monday and Wednesday from June 1 to June 15 will create slots for all Mondays and Wednesdays in that date range.
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">Create Recurring Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                <p>Are you sure you want to delete this time slot?</p>
                <p class="text-danger mb-0"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Calendar and Schedule Management JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables for the calendar view
    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();
    
    // Calendar toggle buttons
    const viewCalendarBtn = document.getElementById('view-calendar');
    const viewListBtn = document.getElementById('view-list');
    const calendarContainer = document.getElementById('calendar-container');
    const listContainer = document.getElementById('list-container');
    
    // Edit schedule modal elements
    const editButtons = document.querySelectorAll('.edit-schedule-btn');
    const editModal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
    const editScheduleId = document.getElementById('edit_schedule_id');
    const editDate = document.getElementById('edit_date');
    const editStartTime = document.getElementById('edit_start_time');
    const editEndTime = document.getElementById('edit_end_time');
    
    // Delete schedule button handling
    const deleteButtons = document.querySelectorAll('.delete-schedule-btn');
    const deleteConfirmModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    let currentDeleteForm = null;
    
    // Toggle between calendar and list views
    viewCalendarBtn.addEventListener('click', function() {
        calendarContainer.style.display = 'block';
        listContainer.style.display = 'none';
        viewCalendarBtn.classList.remove('btn-outline-primary');
        viewCalendarBtn.classList.add('btn-primary');
        viewListBtn.classList.remove('btn-primary');
        viewListBtn.classList.add('btn-outline-primary');
        
        // Initialize calendar if needed
        renderCalendar(currentMonth, currentYear);
    });
    
    viewListBtn.addEventListener('click', function() {
        calendarContainer.style.display = 'none';
        listContainer.style.display = 'block';
        viewListBtn.classList.remove('btn-outline-primary');
        viewListBtn.classList.add('btn-primary');
        viewCalendarBtn.classList.remove('btn-primary');
        viewCalendarBtn.classList.add('btn-outline-primary');
    });
    
    // Calendar navigation
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const currentMonthDisplay = document.getElementById('current-month-display');
    
    prevMonthBtn.addEventListener('click', function() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar(currentMonth, currentYear);
    });
    
    nextMonthBtn.addEventListener('click', function() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar(currentMonth, currentYear);
    });
    
    // Render the calendar for a specific month and year
    function renderCalendar(month, year) {
        const calendarEl = document.getElementById('calendar');
        
        // Update month display
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                           'July', 'August', 'September', 'October', 'November', 'December'];
        currentMonthDisplay.textContent = `${monthNames[month]} ${year}`;
        
        // Get first and last day of month
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        // Get day of week (0-6) for first day and total days in month
        const firstDayOfWeek = firstDay.getDay();
        const daysInMonth = lastDay.getDate();
        
        // Create calendar HTML
        let calendarHTML = `
            <table class="table table-bordered calendar-table">
                <thead>
                    <tr>
                        <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        // Create calendar rows
        let dayCount = 1;
        let calendarRow = '<tr>';
        
        // Add empty cells for days before first day of month
        for (let i = 0; i < firstDayOfWeek; i++) {
            calendarRow += '<td></td>';
        }
        
        // Get all schedules from the page data
        const schedules = <?= json_encode($schedules) ?>;
        
        // Add days of month
        while (dayCount <= daysInMonth) {
            // Start new row if week is complete
            if ((dayCount + firstDayOfWeek - 1) % 7 === 0 && dayCount !== 1) {
                calendarRow += '</tr><tr>';
            }
            
            // Format current date for comparison with schedules
            const currentDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(dayCount).padStart(2, '0')}`;
            
            // Check if current date has schedules
            const daySchedules = schedules.filter(schedule => schedule.date === currentDateStr);
            const hasSchedules = daySchedules.length > 0;
            
            // Check if date is past
            const isPast = new Date(currentDateStr) < new Date(new Date().setHours(0, 0, 0, 0));
            
            // Add day cell
            let cellClass = '';
            if (isPast) cellClass = 'text-muted bg-light';
            if (daySchedules.length > 0) cellClass += ' has-schedules';
            
            calendarRow += `
                <td class="${cellClass}" data-date="${currentDateStr}">
                    <div class="calendar-day">
                        <span class="day-number">${dayCount}</span>
                        ${hasSchedules ? getScheduleIndicators(daySchedules) : ''}
                    </div>
                </td>
            `;
            
            dayCount++;
        }
        
        // Add empty cells for days after last day of month
        const remainingCells = 7 - ((dayCount + firstDayOfWeek - 1) % 7);
        if (remainingCells < 7) {
            for (let i = 0; i < remainingCells; i++) {
                calendarRow += '<td></td>';
            }
        }
        
        calendarRow += '</tr>';
        calendarHTML += calendarRow;
        calendarHTML += '</tbody></table>';
        
        // Update calendar
        calendarEl.innerHTML = calendarHTML;
        
        // Add click event to calendar days with schedules
        document.querySelectorAll('.calendar-table td.has-schedules').forEach(cell => {
            cell.addEventListener('click', function() {
                const date = this.getAttribute('data-date');
                showDaySchedules(date);
            });
        });
    }
    
    // Generate schedule indicators for calendar cell
    function getScheduleIndicators(schedules) {
        const availableCount = schedules.filter(s => s.is_booked == 0).length;
        const bookedCount = schedules.filter(s => s.is_booked == 1).length;
        
        let indicators = '<div class="schedule-indicators">';
        
        if (availableCount > 0) {
            indicators += `<span class="indicator available" title="${availableCount} available slots"></span>`;
        }
        
        if (bookedCount > 0) {
            indicators += `<span class="indicator booked" title="${bookedCount} booked slots"></span>`;
        }
        
        indicators += '</div>';
        
        return indicators;
    }
    
    // Show schedules for a specific day
    function showDaySchedules(date) {
        // Convert API data to an array of all schedules
        const schedules = <?= json_encode($schedules) ?>;
        
        // Filter schedules for the selected date
        const daySchedules = schedules.filter(schedule => schedule.date === date);
        
        if (daySchedules.length === 0) {
            return;
        }
        
        // Format date for display
        const displayDate = new Date(date).toLocaleDateString('en-US', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        // Build HTML for day schedules
        let schedulesHTML = `<h5>Schedules for ${displayDate}</h5>`;
        schedulesHTML += '<div class="list-group">';
        
        // Sort schedules by start time
        daySchedules.sort((a, b) => a.start_time.localeCompare(b.start_time));
        
        daySchedules.forEach(schedule => {
            const startTime = new Date(`2000-01-01T${schedule.start_time}`);
            const endTime = new Date(`2000-01-01T${schedule.end_time}`);
            
            // Format times for display (e.g., "9:00 AM - 10:30 AM")
            const formattedStartTime = startTime.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
            const formattedEndTime = endTime.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
            
            schedulesHTML += `
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">${formattedStartTime} - ${formattedEndTime}</h6>
                        <span class="badge ${schedule.is_booked == 1 ? 'bg-success' : 'bg-primary'}">
                            ${schedule.is_booked == 1 ? 'Booked' : 'Available'}
                        </span>
                    </div>
                    <div>
                        ${schedule.is_booked == 0 ? `
                            <button class="btn btn-sm btn-outline-primary me-2 edit-schedule-btn" 
                                data-id="${schedule.id}"
                                data-date="${schedule.date}"
                                data-start="${schedule.start_time}"
                                data-end="${schedule.end_time}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-schedule-btn" 
                                data-id="${schedule.id}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        ` : `
                            <a href="<?= BASE_URL ?>/main_pages/booking.php?id=${schedule.booking_id}" class="btn btn-sm btn-success">
                                <i class="fas fa-eye"></i> View
                            </a>
                        `}
                    </div>
                </div>
            `;
        });
        
        schedulesHTML += '</div>';
        
        // Create a modal to display the schedules
        const dayScheduleModal = new bootstrap.Modal(document.createElement('div'));
        dayScheduleModal._element.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Day Schedules</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        ${schedulesHTML}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        // Add the modal to the body and show it
        document.body.appendChild(dayScheduleModal._element);
        dayScheduleModal.show();
        
        // Add event listeners to edit/delete buttons in the modal
        dayScheduleModal._element.querySelectorAll('.edit-schedule-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const date = this.getAttribute('data-date');
                const startTime = this.getAttribute('data-start');
                const endTime = this.getAttribute('data-end');
                
                editScheduleId.value = id;
                editDate.value = date;
                editStartTime.value = startTime;
                editEndTime.value = endTime;
                
                dayScheduleModal.hide();
                editModal.show();
            });
        });
        
        // Handle delete buttons in the day schedule modal
        dayScheduleModal._element.querySelectorAll('.delete-schedule-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                
                // Create a temporary form for deletion
                const tempForm = document.createElement('form');
                tempForm.method = 'post';
                tempForm.action = '<?= BASE_URL ?>/main_pages/schedule.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_schedule';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'schedule_id';
                idInput.value = id;
                
                tempForm.appendChild(actionInput);
                tempForm.appendChild(idInput);
                
                currentDeleteForm = tempForm;
                
                dayScheduleModal.hide();
                deleteConfirmModal.show();
            });
        });
    }
    
    // Edit schedule button handling
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const date = this.getAttribute('data-date');
            const startTime = this.getAttribute('data-start');
            const endTime = this.getAttribute('data-end');
            
            editScheduleId.value = id;
            editDate.value = date;
            editStartTime.value = startTime;
            editEndTime.value = endTime;
            
            editModal.show();
        });
    });
    
    // Delete schedule button handling
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            currentDeleteForm = this.closest('.delete-schedule-form');
            deleteConfirmModal.show();
        });
    });
    
    // Confirm delete button
    confirmDeleteBtn.addEventListener('click', function() {
        if (currentDeleteForm) {
            currentDeleteForm.submit();
        }
        deleteConfirmModal.hide();
    });
    
    // Date selection in the add schedule form
    const dateInput = document.getElementById('date');
    if (dateInput) {
        // Set default date to tomorrow if not already set
        if (!dateInput.value) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            dateInput.value = tomorrow.toISOString().split('T')[0];
        }
    }
    
    // Date selection in the recurring schedule form
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    if (startDateInput && endDateInput) {
        // Set default start date to tomorrow if not already set
        if (!startDateInput.value) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            startDateInput.value = tomorrow.toISOString().split('T')[0];
        }
        
        // Set default end date to 2 weeks from tomorrow if not already set
        if (!endDateInput.value) {
            const twoWeeksLater = new Date();
            twoWeeksLater.setDate(twoWeeksLater.getDate() + 15);
            endDateInput.value = twoWeeksLater.toISOString().split('T')[0];
        }
        
        // Ensure end date is not before start date
        startDateInput.addEventListener('change', function() {
            if (endDateInput.value < startDateInput.value) {
                endDateInput.value = startDateInput.value;
            }
        });
        
        endDateInput.addEventListener('change', function() {
            if (endDateInput.value < startDateInput.value) {
                endDateInput.value = startDateInput.value;
            }
        });
    }
});
</script>

<style>
/* Calendar styles */
.calendar-table {
    width: 100%;
    table-layout: fixed;
}

.calendar-table th {
    text-align: center;
    padding: 10px;
}

.calendar-table td {
    height: 80px;
    vertical-align: top;
    padding: 5px;
    position: relative;
}

.calendar-day {
    height: 100%;
    position: relative;
}

.day-number {
    position: absolute;
    top: 5px;
    left: 5px;
    font-weight: 500;
}

.has-schedules {
    background-color: #f8f9fa;
    cursor: pointer;
}

.has-schedules:hover {
    background-color: #e9ecef;
}

.schedule-indicators {
    position: absolute;
    bottom: 5px;
    right: 5px;
    display: flex;
    gap: 5px;
}

.indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.indicator.available {
    background-color: #4e73df;
}

.indicator.booked {
    background-color: #1cc88a;
}

/* Past date styling */
.past-date h5 {
    color: #adb5bd;
}
</style>

<?php
// Include footer
include_once ROOT_PATH . 'views/footer.php';
?>