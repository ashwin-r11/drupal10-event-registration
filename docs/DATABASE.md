# Database Schema

This document details the database schema for the Event Registration module.

## Overview

The module creates two custom tables during installation:
- `event_configurations`: Stores event definitions
- `event_registrations`: Stores participant registrations

## Tables

### event_configurations

Stores event definitions created by administrators.

```sql
CREATE TABLE event_configurations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    registration_start_date DATE NOT NULL,
    registration_end_date DATE NOT NULL,
    event_date DATE NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    category VARCHAR(64) NOT NULL,
    PRIMARY KEY (id),
    INDEX idx_category (category),
    INDEX idx_event_date (event_date),
    INDEX idx_registration_dates (registration_start_date, registration_end_date)
);
```

#### Columns

| Column                    | Type         | Nullable | Description                   |
| ------------------------- | ------------ | -------- | ----------------------------- |
| `id`                      | INT UNSIGNED | No       | Auto-increment primary key    |
| `registration_start_date` | DATE         | No       | Date when registration opens  |
| `registration_end_date`   | DATE         | No       | Date when registration closes |
| `event_date`              | DATE         | No       | Actual date of the event      |
| `event_name`              | VARCHAR(255) | No       | Display name of the event     |
| `category`                | VARCHAR(64)  | No       | Event category identifier     |

#### Indexes

- **Primary Key**: `id`
- **idx_category**: Optimizes category-based queries
- **idx_event_date**: Optimizes date-based filtering
- **idx_registration_dates**: Optimizes active registration queries

---

### event_registrations

Stores participant registration records.

```sql
CREATE TABLE event_registrations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    college VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    event_id INT UNSIGNED NOT NULL,
    created INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY unique_registration (email, event_id),
    INDEX idx_event_id (event_id),
    INDEX idx_email (email),
    INDEX idx_created (created),
    FOREIGN KEY (event_id) REFERENCES event_configurations(id) ON DELETE CASCADE
);
```

#### Columns

| Column       | Type         | Nullable | Description                         |
| ------------ | ------------ | -------- | ----------------------------------- |
| `id`         | INT UNSIGNED | No       | Auto-increment primary key          |
| `full_name`  | VARCHAR(255) | No       | Participant's full name             |
| `email`      | VARCHAR(255) | No       | Participant's email address         |
| `college`    | VARCHAR(255) | No       | Participant's college/institution   |
| `department` | VARCHAR(255) | No       | Participant's department            |
| `event_id`   | INT UNSIGNED | No       | Foreign key to event_configurations |
| `created`    | INT          | No       | Unix timestamp of registration      |

#### Indexes

- **Primary Key**: `id`
- **unique_registration**: Prevents duplicate registrations (email + event)
- **idx_event_id**: Optimizes event-based queries
- **idx_email**: Optimizes email lookups
- **idx_created**: Optimizes date-sorted queries

#### Constraints

- **Foreign Key**: `event_id` references `event_configurations(id)` with CASCADE delete

---

## Entity Relationship Diagram

```
┌─────────────────────────────┐
│    event_configurations     │
├─────────────────────────────┤
│ id (PK)                     │
│ registration_start_date     │
│ registration_end_date       │
│ event_date                  │
│ event_name                  │
│ category                    │
└──────────────┬──────────────┘
               │
               │ 1:N
               │
┌──────────────▼──────────────┐
│     event_registrations     │
├─────────────────────────────┤
│ id (PK)                     │
│ full_name                   │
│ email                       │
│ college                     │
│ department                  │
│ event_id (FK)               │
│ created                     │
└─────────────────────────────┘
```

## Schema Definition

The schema is defined in `event_registration.install`:

```php
function event_registration_schema(): array {
    $schema['event_configurations'] = [
        'description' => 'Stores event configurations.',
        'fields' => [
            'id' => [
                'type' => 'serial',
                'unsigned' => TRUE,
                'not null' => TRUE,
            ],
            // ... other fields
        ],
        'primary key' => ['id'],
        'indexes' => [
            'idx_category' => ['category'],
            'idx_event_date' => ['event_date'],
        ],
    ];
    // ...
}
```

## Common Queries

### Get Active Events (For Registration)

```sql
SELECT * FROM event_configurations 
WHERE registration_start_date <= CURDATE() 
  AND registration_end_date >= CURDATE()
ORDER BY event_date ASC;
```

### Get Registrations with Event Details

```sql
SELECT r.*, e.event_name, e.event_date, e.category
FROM event_registrations r
JOIN event_configurations e ON r.event_id = e.id
ORDER BY r.created DESC;
```

### Check Duplicate Registration

```sql
SELECT COUNT(*) FROM event_registrations r
JOIN event_configurations e ON r.event_id = e.id
WHERE r.email = :email AND e.event_date = :date;
```

### Registration Count by Event

```sql
SELECT e.event_name, COUNT(r.id) as registrations
FROM event_configurations e
LEFT JOIN event_registrations r ON e.id = r.event_id
GROUP BY e.id
ORDER BY registrations DESC;
```

## Maintenance

### Backup Tables

```bash
drush sqlq "SELECT * FROM event_configurations" > events_backup.csv
drush sqlq "SELECT * FROM event_registrations" > registrations_backup.csv
```

### Clear Test Data

```bash
drush sqlq "DELETE FROM event_registrations WHERE email LIKE '%test%'"
```

### Check Table Status

```bash
drush sqlq "SHOW TABLE STATUS LIKE 'event_%'"
```
