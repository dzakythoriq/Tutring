# Tutring — Private Tutor Booking Platform

Tutring is a web platform that connects students with private tutors. Students can
search for tutors, view availability, book sessions, pay for completed bookings, and
leave time-limited reviews. Tutors can manage their profile, set individual or
recurring availability, and respond to booking requests.

## Features

- **Role-based accounts**: Register and log in as either a Student or a Tutor
- **Tutor discovery**: Search by name/subject, filter by subject, price range ($1–$10/hr),
  and minimum rating
- **Tutor profiles**: Bio, subject, hourly rate, average rating, and live availability
- **Scheduling (Tutor)**: Add individual time slots or bulk-create recurring weekly
  schedules; calendar and list views
- **Booking lifecycle**: Students book available slots (status: pending); tutors
  confirm or cancel; students can cancel pending/confirmed bookings
- **Payments (simulated)**: Pay for confirmed sessions via GoPay, DANA, or Bank
  Transfer; payment history view for both roles
- **Reviews**: Students rate (1–5) and comment on paid, confirmed sessions; reviews
  remain editable for 24 hours after submission
- **Dashboards**: Role-specific stats (pending bookings, confirmed sessions,
  available slots) and recent activity tables

## Technology Stack

- **Backend**: PHP (procedural with OOP data models)
- **Database**: MySQL (via `mysqli`, prepared statements)
- **Frontend**: Bootstrap 5, Font Awesome 6, vanilla JavaScript
- **Avatars**: ui-avatars.com (placeholder profile images when no photo is uploaded)

## System Architecture
configs/        → Bootstrap (config.php), helpers (functions.php), auth (auth.php), validation (validation.php)
models/         → User, Tutor, Schedule, Booking, Payment classes
main_pages/     → Page controllers + views (registration, login, dashboard, search, schedule, booking, payment, reviews)
views/          → Shared header/footer layout
css/, js/       → Styling and client-side interactivity
index.php       → Landing page

## Installation Guide

### Prerequisites
- PHP 7.4+ with `mysqli` extension enabled
- MySQL / MariaDB
- A local server stack (e.g., XAMPP, Laragon, MAMP)

### Steps
1. Clone or copy this repository into your web server's document root, e.g.:
   `htdocs/tutring`
2. Create a MySQL database named `tutring`.
3. Import the database schema (see **Database Setup** below).
4. Update `configs/config.php` if your DB credentials or base URL differ from
   the defaults:
```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'tutring');
   define('BASE_URL', 'http://localhost/tutring');
```
5. Ensure the `uploads/profiles/` directory exists and is writable (created
   automatically on first profile photo upload, but verify permissions).
6. Visit `http://localhost/tutring/index.php` in your browser.

## Configuration

All configuration lives in `configs/config.php`:

| Constant | Purpose |
|---|---|
| `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` | MySQL connection details |
| `SITE_NAME` | Displayed in page titles and footer |
| `BASE_URL` | Used to build all internal links and redirects |

## Database Setup

> **Note:** No `.sql` schema file is included in this repository. The tables below
> were reconstructed from the queries in `models/*.model.php` and `configs/auth.php`.
> You will need to create these tables manually (or generate a migration) before
> running the application.

Required tables (reconstructed):

- `users` (id, name, email, password, role, photo, created_at)
- `tutors` (id, user_id, bio, subject, hourly_rate)
- `tutor_education` (id, tutor_id, degree, institution, year, description)
- `schedules` (id, tutor_id, date, start_time, end_time, is_booked)
- `bookings` (id, student_id, schedule_id, status)
- `payments` (id, booking_id, amount, payment_method, status, paid_at, created_at)
- `reviews` (id, booking_id, rating, comment, created_at)

Recommended foreign keys:
- `tutors.user_id` → `users.id`
- `tutor_education.tutor_id` → `tutors.id`
- `schedules.tutor_id` → `tutors.id`
- `bookings.student_id` → `users.id`
- `bookings.schedule_id` → `schedules.id`
- `payments.booking_id` → `bookings.id`
- `reviews.booking_id` → `bookings.id`

## Usage Guide

### As a Student
1. Register via `main_pages/register.php` (Student tab)
2. Search/filter tutors on `main_pages/search.php`
3. View a tutor's profile and available time slots
4. Book a slot → wait for tutor confirmation
5. Pay via `main_pages/payment.php` once confirmed
6. Leave a review (editable for 24 hours)

### As a Tutor
1. Register via `main_pages/register.php` (Tutor tab)
2. Complete your profile (subject, hourly rate, bio) on `main_pages/profile.php`
3. Set availability on `main_pages/schedule.php` (individual or recurring slots)
4. Confirm or cancel incoming booking requests from `main_pages/dashboard.php`
   or `main_pages/booking.php`
5. Track earnings on `main_pages/payment_history.php`

## Screenshots

> _Add screenshots of the landing page, search/filter page, tutor profile,
> booking flow, schedule calendar, and review modal here._

## Known Limitations / Future Improvements

- The "Availability date" filter on the search page does not currently affect
  results — would require joining against the `schedules` table
- Payment gateways (GoPay, DANA, Bank Transfer) are simulated; real integration
  would replace `Payment::processPayment()`
- Add CSRF protection to all state-changing forms
- Replace client-supplied MIME-type checks for profile photo uploads with
  server-side verification (`finfo`/`getimagesize`)
- Review and fix the `sanitize()` helper to avoid double HTML-encoding
- Implement (or remove) the `tutor_education` feature — model methods exist
  with no corresponding UI
- Add an administrator role for moderation (users, reviews, disputes)
- Externalize configuration (DB credentials, base URL) via environment variables

## License

No license file is present in this repository. Add a `LICENSE` file
(e.g., MIT) if you intend to share this project publicly.
