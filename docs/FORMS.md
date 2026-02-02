# Forms Documentation

This document details all forms in the Event Registration module.

## EventSettingsForm

**Location**: `src/Form/EventSettingsForm.php`  
**Route**: `/admin/config/event-registration/settings`  
**Extends**: `ConfigFormBase`

### Purpose

Global module settings configuration.

### Fields

| Field                        | Type     | Description                           |
| ---------------------------- | -------- | ------------------------------------- |
| `admin_notification_email`   | Email    | Email address for admin notifications |
| `enable_admin_notifications` | Checkbox | Toggle admin email notifications      |

### Configuration

Settings are stored in `event_registration.settings` config object.

---

## EventAddForm

**Location**: `src/Form/EventAddForm.php`  
**Route**: `/admin/config/event-registration/add-event`  
**Extends**: `FormBase`

### Purpose

Create new event configurations.

### Fields

| Field                     | Type      | Required | Description              |
| ------------------------- | --------- | -------- | ------------------------ |
| `event_name`              | Textfield | Yes      | Name of the event        |
| `category`                | Select    | Yes      | Event category           |
| `registration_start_date` | Date      | Yes      | When registration opens  |
| `registration_end_date`   | Date      | Yes      | When registration closes |
| `event_date`              | Date      | Yes      | Actual event date        |

### Categories

- `online_workshop`: Online Workshop
- `hackathon`: Hackathon
- `conference`: Conference
- `one_day_workshop`: One-day Workshop

### Validation

1. All fields required
2. `registration_end_date` > `registration_start_date`
3. `event_date` > `registration_end_date`

---

## RegistrationForm

**Location**: `src/Form/RegistrationForm.php`  
**Route**: `/register`  
**Extends**: `FormBase`

### Purpose

Public event registration with AJAX-dependent dropdowns.

### Fields

| Field        | Type      | Required | AJAX                |
| ------------ | --------- | -------- | ------------------- |
| `full_name`  | Textfield | Yes      | No                  |
| `email`      | Email     | Yes      | No                  |
| `college`    | Textfield | Yes      | No                  |
| `department` | Textfield | Yes      | No                  |
| `category`   | Select    | Yes      | Updates date field  |
| `event_date` | Select    | Yes      | Updates event field |
| `event_id`   | Select    | Yes      | No                  |

### AJAX Flow

```
Category Selected
    ↓
updateEventDateOptions() callback
    ↓
Event Date dropdown updated
    ↓
Event Date Selected
    ↓
updateEventOptions() callback
    ↓
Event Name dropdown updated
```

### Validation Rules

#### Email Validation
```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Invalid email format
}
if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
    // Invalid email pattern
}
```

#### Text Field Validation
```php
if (preg_match('/[^a-zA-Z0-9\s\-\'.]/', $value)) {
    // Contains special characters - rejected
}
```

#### Duplicate Check
```php
if ($this->repository->isEmailAlreadyRegisteredForEventDate($email, $eventDate)) {
    // Duplicate registration - rejected
}
```

### Access Control

Form is only accessible when:
- At least one event exists with active registration period
- Current date is between `registration_start_date` and `registration_end_date`

Controlled by `RegistrationAccessCheck` service.

### Email Notification

On successful submission:
1. Confirmation email sent to participant
2. If enabled, notification sent to admin

---

## Form Theming

All forms use Drupal's default form theming. Custom styling can be added via:

1. Theme template overrides
2. CSS in custom theme
3. `#attributes` on form elements

## Security

- CSRF protection via Drupal's form token
- Input sanitization via Form API
- XSS prevention via `htmlspecialchars()`
- SQL injection prevention via Database API
