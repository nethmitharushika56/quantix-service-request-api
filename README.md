# Quantix – Service Request & Approval API

A RESTful backend API for managing internal business service requests, approvals, assignments and request tracking.

## 1. Project Overview

The system streamlines internal service request management within an organization.

It supports three roles:

- **Employee:** Creates, submits and manages personal service requests.
- **Manager:** Reviews requests submitted by direct reports and approves, returns or rejects them.
- **Operations:** Assigns approved requests and manages their progress until completion.

The API enforces role-based access control, workflow validation, audit history tracking and queued database notifications.

## 2. Technology Stack

| Technology | Purpose |
|---|---|
| PHP 8.3 | Backend programming language |
| Laravel 12 | Backend framework |
| Laravel Sanctum | API token authentication |
| SQLite | Relational database |
| PHPUnit | Automated testing |
| Laravel Notifications | Database notifications |
| Database Queue | Asynchronous job processing |
| Composer | Dependency management |
| Git & GitHub | Version control |

## 3. Core Features

- Token-based authentication using Laravel Sanctum.
- Role-based access control.
- Service request creation, viewing, updating and deletion.
- Manager approval, return and rejection workflow.
- Operations assignment and execution.
- Request comments.
- Status history and audit trail.
- Queued database notifications.
- Search, filtering, sorting and pagination.
- Database migrations and seeders.
- Automated feature tests.

## 4. Business Workflow

The service request lifecycle includes:

| Status | Description |
|---|---|
| Draft | Request created but not submitted |
| Submitted | Waiting for manager review |
| Returned | Returned to employee for changes |
| Approved | Approved by manager |
| Rejected | Rejected by manager |
| In Progress | Operations has started the work |
| Completed | Work has been completed |

### Workflow Rules

- Employees can edit requests only in Draft or Returned status.
- Employees can submit their own requests.
- Managers can review requests belonging only to their direct reports.
- Managers can approve, return or reject Submitted requests.
- Only Operations users can assign approved requests.
- Only the assigned Operations user can start or complete the work.
- Rejected and Completed requests are terminal states.
- Workflow changes are recorded in the status history.

## 5. Prerequisites

- PHP 8.3 or later
- Composer
- SQLite
- Git

## 6. Installation and Setup

### Step 1: Clone the repository

```bash
git clone https://github.com/nethmitharushika56/quantix-service-request-api.git
cd quantix-service-request-api
```

### Step 2: Install dependencies

```bash
composer install
```

### Step 3: Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

### Step 4: Configure SQLite

Set the following in `.env`:

```env
DB_CONNECTION=sqlite
QUEUE_CONNECTION=database
```

Create the SQLite database if it does not exist:

```bash
touch database/database.sqlite
```

### Step 5: Run migrations and seeders

```bash
php artisan migrate:fresh --seed
```

This creates the database tables and sample users, requests, comments and status history records.

### Step 6: Start the development server

```bash
php artisan serve
```

The API will be available at:

`http://127.0.0.1:8000`

## 7. Seeded Login Credentials

All seeded users use the following password:

`Password123!`

| Role | Email |
|---|---|
| Manager | sarah@quantix.test |
| Employee | john@quantix.test |
| Employee | emma@quantix.test |
| Operations | oliver@quantix.test |

These credentials are intended for local development and testing only.

## 8. API Endpoints

Base URL: `http://127.0.0.1:8000/api`

All endpoints except login require a valid Sanctum Bearer token.

### Authentication

| Method | Endpoint | Description |
|---|---|---|
| POST | `/login` | Authenticate user |
| POST | `/logout` | Revoke current token |
| GET | `/user` | Get authenticated user |

### Service Requests

| Method | Endpoint | Description |
|---|---|---|
| GET | `/service-requests` | List accessible requests |
| POST | `/service-requests` | Create a request |
| GET | `/service-requests/{id}` | View a request |
| PUT/PATCH | `/service-requests/{id}` | Update a request |
| DELETE | `/service-requests/{id}` | Delete a Draft request |

### Workflow Operations

| Method | Endpoint | Description |
|---|---|---|
| POST | `/service-requests/{id}/submit` | Submit a request |
| POST | `/service-requests/{id}/decision` | Approve, return or reject |
| POST | `/service-requests/{id}/assign` | Assign an approved request |
| POST | `/service-requests/{id}/start` | Start assigned work |
| POST | `/service-requests/{id}/complete` | Complete work |
| POST | `/service-requests/{id}/comments` | Add a comment |

### Request Listing Parameters

The listing endpoint supports:

- `search` – Search by request title or description.
- `status` – Filter by status.
- `employee_id` – Filter by employee.
- `assigned_to` – Filter by assigned Operations user.
- `sort_by` – Sort by `created_at`, `title` or `status`.
- `sort_direction` – `asc` or `desc`.
- `per_page` – Number of records per page (maximum 100).

### Example Login Request

```json
{
    "email": "john@quantix.test",
    "password": "Password123!"
}
```

### Example Manager Decision Request

```json
{
    "decision": "Approved",
    "comment": "Request approved."
}
```

### Example Assignment Request

```json
{
    "assigned_to": 4
}
```

### Example Comment Request

```json
{
    "comment": "Please provide an update on this request."
}
```

## 9. Database Design

The main database entities are:

- **Users:** User details, roles and manager relationships.
- **Service Requests:** Request information, ownership, assignment and status.
- **Comments:** Comments associated with requests and their authors.
- **Status Histories:** Workflow changes, actors, previous and new statuses, comments and timestamps.
- **Notifications:** Database notifications for users.
- **Jobs:** Queued jobs for asynchronous processing.

### Relationships

- A manager can have multiple employees.
- An employee can create multiple service requests.
- An Operations user can be assigned multiple requests.
- A service request can have multiple comments.
- A service request can have multiple status history records.
- A user can receive multiple notifications.

## 10. Notifications and Queue Processing

The application uses Laravel's queued database notifications.

Notifications are generated for:

- New request submissions to managers.
- Manager decisions to employees.
- Request assignments to Operations users.

Run the queue worker in a separate terminal:

```bash
php artisan queue:work
```

The worker processes pending notification jobs and stores the resulting notifications in the database.

## 11. Running Automated Tests

Run the complete test suite:

```bash
php artisan test
```

**Current test results: 42 passed, 109 assertions.**

The tests cover authentication, employee permissions, manager approvals, Operations workflow, comments, audit history and notifications.

## 12. Assumptions and Future Improvements

### Assumptions

- Each employee reports to one manager.
- Managers can review requests only from their direct reports.
- Operations users are responsible for assigning and executing approved requests.
- Rejected and Completed requests cannot transition to another status.
- Notifications are stored in the database and processed through the queue worker.
- Deletion is permitted only for Draft requests owned by the employee.

### Future Improvements

- Add email notifications alongside database notifications.
- Introduce a dedicated activity log for assignment events.
- Add notification retrieval and read-status endpoints.
- Improve concurrency handling using a production database with row-level locking.
- Add API rate limiting and more extensive integration testing.
- Provide OpenAPI documentation.

## 13. Architecture and Technical Decisions

The application follows Laravel MVC conventions.

- **Models:** Represent database entities and relationships.
- **API Controllers:** Handle HTTP requests, validation, authorization and responses.
- **Migrations:** Define the database schema.
- **Seeders:** Provide reproducible sample data.
- **Notifications:** Handle asynchronous user notifications.
- **Feature Tests:** Verify API behavior and business workflow rules.

Database transactions are used for workflow operations so status changes and their corresponding audit records are saved together.

### SQLite Concurrency Limitation

The workflow uses Laravel's `lockForUpdate()` method inside transactions. However, SQLite does not provide the same row-level `SELECT FOR UPDATE` locking behavior as MySQL or PostgreSQL.

For production deployment with concurrent users, a database supporting row-level locking, such as MySQL or PostgreSQL, is recommended, followed by concurrency testing.

### Audit History Trade-off

Workflow transitions are recorded in the `status_histories` table. Assignment events are also recorded there using identical previous and new status values, with assignment details stored in the comment. A dedicated activity or assignment history table would be a cleaner design for a larger production system.

## 14. References, Packages and AI Tools

### Main Packages

- Laravel Framework
- Laravel Sanctum
- Laravel Notifications
- PHPUnit

### References

- Laravel Documentation: https://laravel.com/docs
- Laravel Sanctum: https://laravel.com/docs/sanctum
- PHP Documentation: https://www.php.net/docs.php

### AI Tools

ChatGPT was used as a development assistance tool for understanding Laravel concepts, troubleshooting errors, reviewing implementation approaches, preparing test cases and improving documentation.

The application implementation, testing and verification were carried out in the project development environment (VS code).