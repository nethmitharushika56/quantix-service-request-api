# Quantix – Service Request & Approval API

A RESTful backend API for managing internal business service requests, approvals, assignments and request tracking.

Developed as a Software Engineering Internship take-home assignment for Quantix Global.

## Project Overview

The system is designed to streamline internal service request management within an organization.

Employees can create and submit service requests, managers can review and make approval decisions, and operations users can assign and manage approved requests through completion.

The API enforces role-based access control, valid workflow transitions and activity tracking.

## Technology Stack

| Technology | Purpose |
|---|---|
| PHP 8.3 | Backend programming language |
| Laravel 12 | Backend framework |
| Laravel Sanctum | API token authentication |
| SQLite | Relational database |
| PHPUnit | Automated testing |
| Composer | PHP dependency management |
| Git & GitHub | Version control |

## Core Features

Planned implementation:

- Token-based authentication.
- Employee, Manager and Operations roles.
- Service request creation and management.
- Manager approval and rejection workflow.
- Operations assignment and execution.
- Request comments.
- Activity audit trail.
- Queued notifications.
- Search, filtering and pagination.
- Automated API and workflow tests.

## Project Structure

```text
quantix-service-api/
├── app/
│   ├── Enums/
│   ├── Http/
│   ├── Models/
│   ├── Policies/
│   ├── Services/
│   └── Notifications/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docs/
├── routes/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env.example
├── .gitignore
├── artisan
├── composer.json
└── README.md
```

## Prerequisites

- PHP 8.3 or later
- Composer
- SQLite
- Git

## Installation and Setup

### 1. Clone the repository

```bash
git clone YOUR_REPOSITORY_URL
cd quantix-service-api
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Configure SQLite

Set the following in `.env`:

```env
DB_CONNECTION=sqlite
```

Create the database:

```bash
touch database/database.sqlite
```

### 5. Run database migrations

```bash
php artisan migrate
```

### 6. Start the development server

```bash
php artisan serve
```

The API will be available at:

`http://127.0.0.1:8000`

## Running Tests

Execute the automated test suite:

```bash
php artisan test
```

## API Documentation

API documentation and a Postman collection will be maintained in the `docs/` directory.

## Architecture

The application follows Laravel conventions and separates responsibilities across:

- Models and database relationships.
- Form Requests for input validation.
- Policies for authorization.
- Services for business logic and workflow management.
- API Resources for consistent JSON responses.
- Notifications for asynchronous communication.

## Business Workflow

The intended request lifecycle includes:

- Draft
- Submitted
- Returned
- Approved
- Rejected
- In Progress
- Completed

Invalid state transitions and unauthorized operations will be rejected by the API.

## Development Status

**Current stage:** Initial project setup completed.

Implemented:
- Laravel 12 project initialization.
- PHP 8.3 development environment.
- SQLite database configuration.
- Laravel Sanctum installation.

Remaining features are under development.

## Assignment Information

**Organization:** Quantix Global  
**Position:** Software Engineering Intern  
**Assignment:** Service Request & Approval API


## Technical Decisions and Limitations

### Database Transactions and Concurrency

The application uses Laravel database transactions to ensure that service request status changes and their corresponding audit history records are saved together.

The workflow also uses Laravel's `lockForUpdate()` query method when retrieving service requests inside transactions. This provides a locking mechanism on database systems that support row-level locking, such as MySQL and PostgreSQL.

**SQLite limitation:**

This project uses SQLite as its development database. SQLite does not provide the same row-level `SELECT FOR UPDATE` locking behavior as MySQL or PostgreSQL. Therefore, the use of `lockForUpdate()` in this project does not guarantee protection against concurrent modifications in the same way it would on those database systems.

Transactions still help maintain consistency between status updates and audit records. However, production deployment with concurrent users should use a database that supports the required row-level locking behavior, such as MySQL or PostgreSQL, followed by concurrency testing.

### Audit History

Status transitions are recorded in the `status_histories` table with the service request, actor, previous status, new status, comment and timestamp.

Assignment events are also recorded in this table using the same previous and new status values. A dedicated activity or assignment history table would be a cleaner design for a larger production system.


## Acknowledgements

Developed as part of the Quantix Global Software Engineering Internship selection process.