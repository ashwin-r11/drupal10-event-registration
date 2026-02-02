# Module Architecture

This document describes the architecture and design patterns used in the Event Registration module.

## Overview

The module follows Drupal 10 best practices with a clean separation of concerns:

```
src/
├── Access/           # Custom access checkers
├── Controller/       # Page controllers
├── Form/            # Form classes
└── Service/         # Business logic services
```

## Design Patterns

### Dependency Injection

All classes use constructor-based dependency injection. No usage of `\Drupal::service()` in business logic.

```php
public function __construct(
    EventRegistrationRepository $repository,
    MailManagerInterface $mail_manager,
    ConfigFactoryInterface $config_factory
) {
    $this->repository = $repository;
    $this->mailManager = $mail_manager;
    $this->setConfigFactory($config_factory);
}
```

### Repository Pattern

Database operations are encapsulated in `EventRegistrationRepository`:
- Single point of access for all database queries
- Clean separation between business logic and data access
- Easy to mock for testing

### Form API

All forms extend Drupal's base form classes:
- `FormBase` for public forms
- `ConfigFormBase` for configuration forms

## Service Container

Services are defined in `event_registration.services.yml`:

```yaml
services:
  event_registration.repository:
    class: Drupal\event_registration\Service\EventRegistrationRepository
    arguments: ['@database']

  event_registration.access_checker:
    class: Drupal\event_registration\Access\RegistrationAccessCheck
    arguments: ['@event_registration.repository']
    tags:
      - { name: access_check, applies_to: _registration_access }
```

## Request Flow

### Public Registration

```
User Request
    ↓
RegistrationAccessCheck (date validation)
    ↓
RegistrationForm::buildForm()
    ↓
AJAX callbacks (category → date → event)
    ↓
RegistrationForm::validateForm()
    ↓
RegistrationForm::submitForm()
    ↓
EventRegistrationRepository::addRegistration()
    ↓
Email Notification (via MailManager)
    ↓
Redirect to front page
```

### Admin Report

```
User Request
    ↓
Permission Check (administer event registration)
    ↓
RegistrationReportController::listing()
    ↓
EventRegistrationRepository::getRegistrations()
    ↓
Render Table with Filters
```

## Configuration Management

Module configuration uses Drupal's Config API:

- **Schema**: `config/schema/event_registration.schema.yml`
- **Defaults**: `config/install/event_registration.settings.yml`

Configuration keys:
- `admin_notification_email`: Admin email for notifications
- `enable_admin_notifications`: Boolean toggle

## Access Control

### Custom Access Checker

`RegistrationAccessCheck` implements `AccessInterface`:
- Checks if any event has active registration period
- Returns `AccessResult::allowed()` or `AccessResult::forbidden()`

### Permission-Based Access

Admin routes use the `administer event registration` permission defined in `event_registration.permissions.yml`.

## AJAX Implementation

The registration form uses Drupal's AJAX Framework:

1. **Trigger**: `#ajax` property on form elements
2. **Callback**: Returns `AjaxResponse` with `ReplaceCommand`
3. **Wrapper**: Target element ID for replacement

```php
'#ajax' => [
    'callback' => '::updateEventDateOptions',
    'wrapper' => 'event-date-wrapper',
    'event' => 'change',
],
```

## Email System

Uses Drupal's Mail API via `hook_mail()`:

1. Define templates in `event_registration.module`
2. Call `MailManager::mail()` from form submission
3. Log results for debugging

## Error Handling

- Form validation errors shown via `$form_state->setErrorByName()`
- Exceptions logged via `\Drupal::logger()`
- User-friendly error messages displayed via Messenger service
