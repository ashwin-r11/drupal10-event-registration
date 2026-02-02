# Troubleshooting Guide

This document provides solutions for common issues.

## Installation Issues

### Module Won't Enable

**Symptom:** Error when running `drush en event_registration`

**Solutions:**

1. **Check PHP version**
   ```bash
   php -v  # Must be 8.1+
   ```

2. **Clear cache first**
   ```bash
   drush cr
   drush en event_registration -y
   ```

3. **Check module location**
   - Must be in `web/modules/custom/event_registration/`
   - Check `event_registration.info.yml` exists

4. **Check for syntax errors**
   ```bash
   php -l web/modules/custom/event_registration/src/Form/RegistrationForm.php
   ```

---

### Database Tables Not Created

**Symptom:** "Table doesn't exist" errors

**Solutions:**

1. **Reinstall module**
   ```bash
   drush pmu event_registration -y
   drush en event_registration -y
   ```

2. **Run install hooks manually**
   ```bash
   drush php:eval "event_registration_install();"
   ```

3. **Check database permissions**
   ```bash
   drush sqlq "SHOW GRANTS FOR CURRENT_USER();"
   ```

---

## Form Issues

### Registration Form Shows "Access Denied"

**Symptom:** 403 error on `/register`

**Cause:** No events with active registration period

**Solutions:**

1. **Check active events**
   ```bash
   drush sqlq "SELECT * FROM event_configurations WHERE registration_start_date <= CURDATE() AND registration_end_date >= CURDATE();"
   ```

2. **Create an event with current registration dates**
   - Go to `/admin/config/event-registration/add-event`
   - Set start date = today
   - Set end date = future date

---

### AJAX Dropdowns Not Updating

**Symptom:** Selecting category doesn't update date dropdown

**Solutions:**

1. **Clear cache**
   ```bash
   drush cr
   ```

2. **Check JavaScript errors**
   - Open browser console (F12)
   - Look for JavaScript errors

3. **Check Drupal AJAX is loaded**
   ```javascript
   // In browser console
   typeof Drupal.ajax !== 'undefined'
   ```

4. **Verify BigPipe is working**
   ```bash
   drush config:get system.performance css.preprocess
   ```

---

### Form Validation Not Working

**Symptom:** Invalid data being accepted

**Solutions:**

1. **Check form ID**
   ```bash
   drush devel:form:info event_registration_registration_form
   ```

2. **Verify validation methods are called**
   - Add `\Drupal::logger('test')->notice('Validation');` in `validateForm()`

---

## Email Issues

### Emails Not Being Sent

**Symptom:** No confirmation emails received

**Causes:**
1. No mail server configured (Docker)
2. Mail disabled
3. Email going to spam

**Solutions:**

1. **Check mail is being attempted**
   ```bash
   drush watchdog:show --type=mail_manager
   ```

2. **Check module logs**
   ```bash
   drush watchdog:show --type=event_registration
   ```

3. **For Docker development**, emails are logged only
   ```bash
   drush watchdog:show | grep "email"
   ```

4. **Install a mail handler**
   - Mailhog for development
   - SMTP module for production

---

### Admin Not Receiving Notifications

**Symptom:** Participant gets email, admin doesn't

**Solutions:**

1. **Check settings**
   - Go to `/admin/config/event-registration/settings`
   - Verify "Enable admin notifications" is checked
   - Verify email address is correct

2. **Check configuration**
   ```bash
   drush config:get event_registration.settings
   ```

---

## Admin Report Issues

### Report Shows Empty Table

**Symptom:** No registrations displayed

**Solutions:**

1. **Check registrations exist**
   ```bash
   drush sqlq "SELECT COUNT(*) FROM event_registrations;"
   ```

2. **Check filter values**
   - Reset filters by visiting URL without query params

---

### CSV Export Not Working

**Symptom:** Download doesn't start

**Solutions:**

1. **Check permissions**
   - User must have `administer event registration` permission

2. **Check server configuration**
   - `output_buffering` should be off for streaming
   - `zlib.output_compression` might interfere

---

## Performance Issues

### Slow Page Load

**Solutions:**

1. **Enable caching**
   ```bash
   drush config:set system.performance cache.page.max_age 3600
   ```

2. **Check database indexes**
   ```bash
   drush sqlq "SHOW INDEX FROM event_registrations;"
   ```

3. **Optimize queries**
   - Use `EXPLAIN` on slow queries

---

## Docker Issues

### Container Won't Start

**Symptom:** `docker compose up` fails

**Solutions:**

1. **Check port conflicts**
   ```bash
   lsof -i :8080
   lsof -i :3306
   ```

2. **Remove old containers**
   ```bash
   docker compose down -v
   docker compose up -d
   ```

3. **Check logs**
   ```bash
   docker compose logs drupal
   docker compose logs db
   ```

---

### Changes Not Reflected

**Symptom:** Code changes don't appear

**Solutions:**

1. **Clear Drupal cache**
   ```bash
   docker compose exec drupal drush cr
   ```

2. **Check volume mounts**
   ```bash
   docker compose exec drupal ls -la /opt/drupal/web/modules/custom/
   ```

3. **Restart container**
   ```bash
   docker compose restart drupal
   ```

---

## Debugging

### Enable Debug Mode

```php
// In settings.local.php
$config['system.logging']['error_level'] = 'verbose';
```

### View Recent Errors

```bash
drush watchdog:show --severity=error --count=20
```

### Enable Query Logging

```php
// In settings.local.php
$databases['default']['default']['log'] = TRUE;
```

### Print Variables

```php
\Drupal::logger('debug')->notice('<pre>' . print_r($variable, TRUE) . '</pre>');
```

---

## Getting Help

1. Check Drupal.org documentation
2. Search Drupal StackExchange
3. Review module source code comments
4. Check `watchdog:show` for detailed errors
