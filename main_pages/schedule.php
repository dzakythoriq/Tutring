<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/validation.php';
require_once 'models/schedule.model.php';
require_once 'models/tutor.model.php';

// Require login and tutor role
requireLogin('tutor');

// Initialize models
$scheduleModel = new Schedule($conn);
$tutorModel = new Tutor($conn);

// Get tutor details
$tutorData = $tutorModel->getByUserId($_SESSION['user_id']);
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
    
    // Redirect to refresh the page
    redirect('schedule.php');
}

// Get tutor's schedules
$schedules = $scheduleModel->getByTutor($tutorId);

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
include_once 'views/header.php';
?>

<div class="container py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage Schedule</h1>
        <button class="d-none d-sm-inline-block btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
            <i class="fas fa-plus fa-sm text-white-50"></i> Add New Schedule
        </button>
    </div>
    
    <!-- Schedule Calendar View -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Your Schedule</h6>
            <div>
                <button class="btn btn-sm btn-outline-primary" id="prev-month">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="mx-2" id="current-month"></span>
                <button class="btn btn-sm btn-outline-primary" id="next-month">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="calendar" class="mb-4"></div>
            
            <div id="date-schedules">
                <?php if (empty($schedulesByDate)): ?>
                    <div class="text-center py-5">
                        <p class="lead text-muted">No schedules found</p>
                        <p>Click the "Add New Schedule" button to create your first time slot.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addScheduleModalLabel">Add New Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="schedule.php" method="post">
                    <input type="hidden" name="action" value="add_schedule">
                    
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="end_time" name="end_time" required>
                        <small class="text-muted">Session must be at least 30 minutes</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Schedule</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editScheduleModalLabel">Edit Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="schedule.php" method="post" id="editScheduleForm">
                    <input type="hidden" name="action" value="update_schedule">
                    <input type="hidden" name="schedule_id" id="edit_schedule_id">
                    
                    <div class="mb-3">
                        <label for="edit_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="edit_date" name="date" required min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                        <small class="text-muted">Session must be at least 30 minutes</small>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Update Schedule</button>
                        <button type="button" class="btn btn-outline-danger" id="deleteScheduleBtn">Delete Schedule</button>
                    </div>
                </form>
                
                <form action="schedule.php" method="post" id="deleteScheduleForm" class="d-none">
                    <input type="hidden" name="action" value="delete_schedule">
                    <input type="hidden" name="schedule_id" id="delete_schedule_id">
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize variables
        let currentMonth = new Date();
        let schedules = <?= json_encode($schedules) ?>;
        
        // Format date to YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Parse date from YYYY-MM-DD
        function parseDate(dateString) {
            const [year, month, day] = dateString.split('-');
            return new Date(year, month - 1, day);
        }
        
        // Render calendar for a specific month
        function renderCalendar(date) {
            const year = date.getFullYear();
            const month = date.getMonth();
            
            // Update current month display
            document.getElementById('current-month').textContent = `${new Date(year, month).toLocaleString('default', { month: 'long' })} ${year}`;
            
            // Get first and last day of month
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            
            // Get day of week (0-6) for first day
            const firstDayOfWeek = firstDay.getDay();
            
            // Get total days in month
            const daysInMonth = lastDay.getDate();
            
            // Create calendar HTML
            let calendarHTML = `
                <table class="table table-bordered calendar-table">
                    <thead>
                        <tr>
                            <th>Sun</th>
                            <th>Mon</th>
                            <th>Tue</th>
                            <th>Wed</th>
                            <th>Thu</th>
                            <th>Fri</th>
                            <th>Sat</th>
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
            
            // Add days of month
            while (dayCount <= daysInMonth) {
                // Start new row if week is complete
                if ((dayCount + firstDayOfWeek - 1) % 7 === 0 && dayCount !== 1) {
                    calendarRow += '</tr><tr>';
                }
                
                // Format current date
                const currentDate = formatDate(new Date(year, month, dayCount));
                
                // Check if current date has schedules
                const hasSchedules = schedules.some(schedule => schedule.date === currentDate);
                
                // Add day cell
                const isPast = new Date(currentDate) < new Date(new Date().setHours(0, 0, 0, 0));
                const isToday = currentDate === formatDate(new Date());
                
                let cellClass = '';
                if (isPast) cellClass = 'text-muted';
                if (isToday) cellClass = 'bg-light font-weight-bold';
                if (hasSchedules) cellClass += ' has-schedules';
                
                calendarRow += `
                    <td class="${cellClass}" data-date="${currentDate}" style="cursor: pointer;">
                        ${dayCount}
                        ${hasSchedules ? '<div class="schedule-dot"></div>' : ''}
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
            document.getElementById('calendar').innerHTML = calendarHTML;
            
            // Add click event to date cells
            document.querySelectorAll('.calendar-table td[data-date]').forEach(cell => {
                cell.addEventListener('click', function() {
                    const date = this.getAttribute('data-date');
                    showSchedulesForDate(date);
                });
            });
        }
        
        // Show schedules for a specific date
        function showSchedulesForDate(date) {
            // Highlight selected date
            document.querySelectorAll('.calendar-table td').forEach(cell => cell.classList.remove('selected-date'));
            document.querySelector(`.calendar-table td[data-date="${date}"]`)?.classList.add('selected-date');
            
            // Filter schedules for selected date
            const dateSchedules = schedules.filter(schedule => schedule.date === date);
            
            // Sort schedules by start time
            dateSchedules.sort((a, b) => a.start_time.localeCompare(b.start_time));
            
            // Create schedules HTML
            let schedulesHTML = '';
            
            if (dateSchedules.length === 0) {
                schedulesHTML = `
                    <div class="text-center py-4">
                        <p class="text-muted">No schedules for ${formatDisplayDate(date)}</p>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal" onclick="setModalDate('${date}')">
                            <i class="fas fa-plus fa-sm"></i> Add Schedule for This Date
                        </button>
                    </div>
                `;
            } else {
                schedulesHTML = `
                    <h5 class="mb-3">Schedules for ${formatDisplayDate(date)}</h5>
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                `;
                
                dateSchedules.forEach(schedule => {
                    const isBooked = schedule.is_booked === 1;
                    
                    schedulesHTML += `
                        <div class="col">
                            <div class="card h-100 ${isBooked ? 'border-success' : 'border-primary'}">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="far fa-clock me-1"></i>
                                        ${formatTime(schedule.start_time)} - ${formatTime(schedule.end_time)}
                                    </h6>
                                    
                                    <p class="card-text mb-3">
                                        <span class="badge ${isBooked ? 'bg-success' : 'bg-info'}">
                                            ${isBooked ? 'Booked' : 'Available'}
                                        </span>
                                    </p>
                                    
                                    ${!isBooked ? `
                                        <button class="btn btn-sm btn-outline-primary edit-schedule" 
                                            data-id="${schedule.id}"
                                            data-date="${schedule.date}"
                                            data-start="${schedule.start_time}"
                                            data-end="${schedule.end_time}">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                schedulesHTML += `
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal" onclick="setModalDate('${date}')">
                            <i class="fas fa-plus fa-sm"></i> Add Another Schedule
                        </button>
                    </div>
                `;
            }
            
            // Update schedules display
            document.getElementById('date-schedules').innerHTML = schedulesHTML;
            
            // Add event listeners to edit buttons
            document.querySelectorAll('.edit-schedule').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const date = this.getAttribute('data-date');
                    const startTime = this.getAttribute('data-start');
                    const endTime = this.getAttribute('data-end');
                    
                    openEditModal(id, date, startTime, endTime);
                });
            });
        }
        
        // Format display date (e.g., "Monday, January 1, 2023")
        function formatDisplayDate(dateString) {
            const date = parseDate(dateString);
            return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
        
        // Format time (e.g., "9:00 AM")
        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const date = new Date();
            date.setHours(hours);
            date.setMinutes(minutes);
            
            return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        }
        
        // Open edit modal with schedule data
        function openEditModal(id, date, startTime, endTime) {
            document.getElementById('edit_schedule_id').value = id;
            document.getElementById('delete_schedule_id').value = id;
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_start_time').value = startTime;
            document.getElementById('edit_end_time').value = endTime;
            
            const editModal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
            editModal.show();
        }
        
        // Set date in add schedule modal
        window.setModalDate = function(date) {
            document.getElementById('date').value = date;
        };
        
        // Initialize calendar
        renderCalendar(currentMonth);
        
        // Show current date schedules
        const today = formatDate(new Date());
        showSchedulesForDate(today);
        
        // Previous month button
        document.getElementById('prev-month').addEventListener('click', function() {
            currentMonth.setMonth(currentMonth.getMonth() - 1);
            renderCalendar(currentMonth);
        });
        
        // Next month button
        document.getElementById('next-month').addEventListener('click', function() {
            currentMonth.setMonth(currentMonth.getMonth() + 1);
            renderCalendar(currentMonth);
        });
        
        // Delete schedule button
        document.getElementById('deleteScheduleBtn').addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this schedule?')) {
                document.getElementById('deleteScheduleForm').submit();
            }
        });
    });
</script>

<style>
    .calendar-table {
        table-layout: fixed;
    }
    
    .calendar-table th {
        text-align: center;
    }
    
    .calendar-table td {
        height: 60px;
        vertical-align: top;
        padding: 8px;
        position: relative;
    }
    
    .has-schedules {
        color: #4e73df;
        font-weight: 500;
    }
    
    .selected-date {
        background-color: #4e73df !important;
        color: white !important;
    }
    
    .schedule-dot {
        width: 8px;
        height: 8px;
        background-color: #4e73df;
        border-radius: 50%;
        position: absolute;
        bottom: 8px;
        right: 8px;
    }
</style>

<?php
// Include footer
include_once 'views/footer.php';
?>