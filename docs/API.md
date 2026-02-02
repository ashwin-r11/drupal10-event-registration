# API Reference

This document provides API reference for the Event Registration module services.

## EventRegistrationRepository

**Class**: `Drupal\event_registration\Service\EventRegistrationRepository`  
**Service ID**: `event_registration.repository`

The main service for database operations.

### Constructor

```php
public function __construct(Connection $database)
```

### Methods

---

#### addEvent()

Creates a new event configuration.

```php
public function addEvent(array $data): int
```

**Parameters:**
- `$data` (array): Event data with keys:
  - `registration_start_date` (string): YYYY-MM-DD
  - `registration_end_date` (string): YYYY-MM-DD
  - `event_date` (string): YYYY-MM-DD
  - `event_name` (string): Event name
  - `category` (string): Category key

**Returns:** `int` - The new event ID

**Example:**
```php
$event_id = $repository->addEvent([
    'registration_start_date' => '2024-01-01',
    'registration_end_date' => '2024-01-15',
    'event_date' => '2024-02-01',
    'event_name' => 'Tech Summit 2024',
    'category' => 'conference',
]);
```

---

#### addRegistration()

Creates a new registration.

```php
public function addRegistration(array $data): int
```

**Parameters:**
- `$data` (array): Registration data with keys:
  - `full_name` (string)
  - `email` (string)
  - `college` (string)
  - `department` (string)
  - `event_id` (int)

**Returns:** `int` - The new registration ID

**Throws:** `\Exception` on database error

---

#### getEventById()

Retrieves an event by ID.

```php
public function getEventById(int $id): ?object
```

**Parameters:**
- `$id` (int): Event ID

**Returns:** `object|null` - Event object or null if not found

---

#### getAllEvents()

Retrieves all events.

```php
public function getAllEvents(): array
```

**Returns:** `array` - Array of event objects

---

#### getActiveEvents()

Gets events with active registration periods.

```php
public function getActiveEvents(): array
```

**Returns:** `array` - Events where current date is between start and end dates

---

#### hasActiveRegistrationPeriod()

Checks if any event has active registration.

```php
public function hasActiveRegistrationPeriod(): bool
```

**Returns:** `bool` - True if at least one event has active registration

---

#### getEventsByCategory()

Gets events by category.

```php
public function getEventsByCategory(string $category): array
```

**Parameters:**
- `$category` (string): Category key

**Returns:** `array` - Matching events

---

#### getEventsByCategoryAndDate()

Gets events by category and date.

```php
public function getEventsByCategoryAndDate(string $category, string $eventDate): array
```

**Parameters:**
- `$category` (string): Category key
- `$eventDate` (string): Event date (YYYY-MM-DD)

**Returns:** `array` - Matching events

---

#### getEventDatesByCategory()

Gets unique event dates for a category.

```php
public function getEventDatesByCategory(string $category): array
```

**Parameters:**
- `$category` (string): Category key

**Returns:** `array` - Array of date strings

---

#### isEmailAlreadyRegisteredForEventDate()

Checks for duplicate registration.

```php
public function isEmailAlreadyRegisteredForEventDate(string $email, string $eventDate): bool
```

**Parameters:**
- `$email` (string): Participant email
- `$eventDate` (string): Event date

**Returns:** `bool` - True if already registered

---

#### getRegistrations()

Gets registrations with optional filters.

```php
public function getRegistrations(array $filters = []): array
```

**Parameters:**
- `$filters` (array): Optional filters:
  - `event_date` (string): Filter by date
  - `event_id` (int): Filter by event

**Returns:** `array` - Registration objects with event details

---

#### getRegistrationCount()

Gets count of registrations.

```php
public function getRegistrationCount(array $filters = []): int
```

**Parameters:**
- `$filters` (array): Same as `getRegistrations()`

**Returns:** `int` - Registration count

---

## RegistrationAccessCheck

**Class**: `Drupal\event_registration\Access\RegistrationAccessCheck`  
**Service ID**: `event_registration.access_checker`

Custom access checker for registration form.

### Methods

#### access()

Checks if registration form should be accessible.

```php
public function access(): AccessResultInterface
```

**Returns:** `AccessResult::allowed()` or `AccessResult::forbidden()`

**Usage in routing.yml:**
```yaml
event_registration.register:
  path: '/register'
  requirements:
    _registration_access: 'TRUE'
```

---

## Hooks

### hook_mail()

Defines email templates.

```php
function event_registration_mail(string $key, array &$message, array $params): void
```

**Keys:**
- `registration_confirmation`: Participant confirmation
- `admin_notification`: Admin notification

**Parameters in `$params`:**
- `full_name`
- `email`
- `event_name`
- `event_date`
- `category`
- `college`
- `department`

---

## Events (Future)

The module is designed to be extensible. Future versions may dispatch events:

- `RegistrationCreatedEvent`
- `EventCreatedEvent`

These would allow other modules to react to registrations.
