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

## Acknowledgements

Developed as part of the Quantix Global Software Engineering Internship selection process.