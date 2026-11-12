# System Architecture & Design Decisions

## 1. System Architecture

The application follows Laravel MVC architecture. API controllers handle requests and business rules, Eloquent models manage database relationships, and Sanctum provides authentication.

```mermaid
flowchart TD
    A[API Client / Postman] --> B[Laravel API Routes]
    B --> C[Sanctum Authentication]
    C --> D[API Controllers]
    D --> E[Validation & Role Authorization]
    E --> F[Service Request Workflow]
    F --> G[(SQLite Database)]
    F --> H[Laravel Notifications]
    H --> I[Database Queue]
    I --> J[Queue Worker]
```

## 2. Request Workflow

The workflow separates responsibilities across three roles.

```mermaid
flowchart TD
    A[Employee Creates Request] --> B[Draft]
    B --> C[Submit]
    C --> D[Manager Review]
    D -->|Return| E[Returned]
    E --> B
    D -->|Approve| F[Approved]
    D -->|Reject| G[Rejected]
    F --> H[Operations Assignment]
    H --> I[In Progress]
    I --> J[Completed]
```

## 3. Key Design Decisions

| Decision | Rationale |
|---|---|
| Laravel Sanctum | Secure token-based API authentication |
| SQLite | Simple relational database for development |
| Database transactions | Keep workflow updates and audit records consistent |
| Database queue | Process notifications asynchronously |
| Role-based authorization | Restrict actions according to user responsibilities |

**Trade-offs:** SQLite does not provide the same row-level locking as MySQL or PostgreSQL. Assignment events are recorded in the status history table rather than a separate activity table.

**Future improvement:** Introduce a production database with row-level locking, OpenAPI documentation and dedicated activity tracking.
