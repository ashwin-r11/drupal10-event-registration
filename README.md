# Event Registration Module for Drupal 10

A complete Drupal 10 custom module for event registration management, featuring admin configuration, public registration forms with AJAX-dependent dropdowns, email notifications, and CSV export.

## Features

- **Admin Event Configuration**: Create and manage events with dates, categories, and registration periods
- **Public Registration Form**: AJAX-powered dependent dropdowns (Category → Date → Event)
- **Access Control**: Registration form only accessible during active registration periods
- **Duplicate Prevention**: Prevents duplicate registrations by email + event date
- **Email Notifications**: Confirmation emails to participants and optional admin notifications
- **Admin Reports**: Filterable registration listing with participant count and CSV export
- **Custom Permissions**: Granular access control for admin functions

## Requirements

- Drupal 10.x
- PHP 8.1 or 8.2
- MySQL 8.0+ or MariaDB 10.4+
- Composer

## Installation

### Option 1: Docker (Recommended for Development)

1. Clone the repository:
   ```bash
   git clone https://github.com/your-username/drupal10-event-registration.git
   cd drupal10-event-registration
   ```

2. Start the Docker environment:
   ```bash
   docker compose up -d
   ```

3. Wait for containers to be ready, then install Drupal:
   ```bash
   docker compose exec drupal drush site:install standard \
     --db-url=mysql://drupal:drupal@db:3306/drupal \
     --account-name=admin \
     --account-pass=admin \
     --site-name="Event Registration" \
     -y
   ```

4. Enable the module:
   ```bash
   docker compose exec drupal drush en event_registration -y
   docker compose exec drupal drush cr
   ```

5. Access the site at: **http://localhost:8080**

### Option 2: Manual Installation

1. Copy the module to your Drupal installation:
   ```bash
   cp -r web/modules/custom/event_registration /path/to/drupal/web/modules/custom/
   ```

2. Enable the module:
   ```bash
   drush en event_registration -y
   drush cr
   ```

## URL Paths

| Path                                         | Description                   | Access                   |
| -------------------------------------------- | ----------------------------- | ------------------------ |
| `/admin/config/event-registration/settings`  | Module settings (admin email) | Admin                    |
| `/admin/config/event-registration/add-event` | Add new event                 | Admin                    |
| `/register`                                  | Public registration form      | Public (date-restricted) |
| `/admin/reports/event-registrations`         | Registration report           | Admin                    |
| `/admin/reports/event-registrations/export`  | CSV export                    | Admin                    |

## Database Schema

### event_configurations

Stores event definitions created by administrators.

| Column                    | Type         | Description                                                               |
| ------------------------- | ------------ | ------------------------------------------------------------------------- |
| `id`                      | INT (PK)     | Auto-increment ID                                                         |
| `registration_start_date` | DATE         | When registration opens                                                   |
| `registration_end_date`   | DATE         | When registration closes                                                  |
| `event_date`              | DATE         | Actual event date                                                         |
| `event_name`              | VARCHAR(255) | Event title                                                               |
| `category`                | VARCHAR(64)  | Event category (online_workshop, hackathon, conference, one_day_workshop) |

### event_registrations

Stores participant registrations.

| Column       | Type         | Description                          |
| ------------ | ------------ | ------------------------------------ |
| `id`         | INT (PK)     | Auto-increment ID                    |
| `full_name`  | VARCHAR(255) | Participant name                     |
| `email`      | VARCHAR(255) | Participant email                    |
| `college`    | VARCHAR(255) | College name                         |
| `department` | VARCHAR(255) | Department                           |
| `event_id`   | INT (FK)     | Reference to event_configurations.id |
| `created`    | INT          | Unix timestamp of registration       |

**Unique Constraint**: `email` + `event_id` (prevents duplicate registrations)

## Validation Logic

### Registration Form Validation

1. **Required Fields**: All fields are required
2. **Email Format**: Standard email validation (`filter_var` + regex)
3. **Special Characters**: Text fields (name, college, department) disallow special characters. Only letters, numbers, spaces, hyphens, and apostrophes allowed.
4. **Duplicate Prevention**: System checks if email is already registered for the selected event
5. **Date-Based Access**: Form only accessible when current date is between `registration_start_date` and `registration_end_date`

### Event Configuration Validation

1. **Date Order**: Registration end date must be after start date
2. **Event Date**: Must be after registration end date
3. **Required Fields**: All fields mandatory

## AJAX Cascading Dropdowns

The registration form uses AJAX for dependent field updates:

1. **Category Selection** → Updates Event Date dropdown (shows dates with events in that category)
2. **Event Date Selection** → Updates Event Name dropdown (shows events on that date in selected category)

## Email Notifications

### Participant Confirmation

Sent automatically after successful registration:
- Recipient: Participant email
- Content: Name, event details, category, date

### Admin Notification (Optional)

Configurable at `/admin/config/event-registration/settings`:
- Enable/disable admin notifications
- Set admin email address
- Content: Full registration details

**Note**: In Docker development, emails are logged but not delivered (no mail server). Check logs at `/admin/reports/dblog`.

## Permissions

| Permission                      | Description                        |
| ------------------------------- | ---------------------------------- |
| `administer event registration` | Full access to all admin functions |

Assign to roles at `/admin/people/permissions`.

## Configuration

Module settings are stored using Drupal's Config API:

```yaml
# config/install/event_registration.settings.yml
admin_notification_email: 'admin@example.com'
enable_admin_notifications: true
```

Export/import with:
```bash
drush config:export
drush config:import
```

## GitHub Actions CI

The repository includes a CI workflow that validates:

1. ✓ Drupal installs successfully
2. ✓ Module enables without errors
3. ✓ Database tables are created
4. ✓ Routes are registered

Runs automatically on push/PR to main branch.

## Development

### Clear Cache
```bash
docker compose exec drupal drush cr
```

### View Logs
```bash
docker compose exec drupal drush watchdog:show --count=20
```

### Database Access
```bash
docker compose exec drupal drush sqlq "SELECT * FROM event_configurations;"
```

### Export Registrations
```bash
docker compose exec drupal drush sqlq "SELECT * FROM event_registrations;"
```

## File Structure

```
web/modules/custom/event_registration/
├── config/
│   ├── install/
│   │   └── event_registration.settings.yml
│   └── schema/
│       └── event_registration.schema.yml
├── src/
│   ├── Access/
│   │   └── RegistrationAccessCheck.php
│   ├── Controller/
│   │   └── RegistrationReportController.php
│   ├── Form/
│   │   ├── EventAddForm.php
│   │   ├── EventSettingsForm.php
│   │   └── RegistrationForm.php
│   └── Service/
│       └── EventRegistrationRepository.php
├── event_registration.info.yml
├── event_registration.install
├── event_registration.links.menu.yml
├── event_registration.module
├── event_registration.permissions.yml
├── event_registration.routing.yml
└── event_registration.services.yml
```

## License

This project is provided as-is for educational and demonstration purposes.
