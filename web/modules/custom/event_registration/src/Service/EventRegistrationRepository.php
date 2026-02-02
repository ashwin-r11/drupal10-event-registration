<?php

declare(strict_types=1);

namespace Drupal\event_registration\Service;

use Drupal\Core\Database\Connection;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Repository service for event registration database operations.
 */
class EventRegistrationRepository
{

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * Constructs an EventRegistrationRepository object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(Connection $database, TimeInterface $time)
  {
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * Adds a new event configuration.
   *
   * @param array $data
   *   An associative array containing:
   *   - registration_start_date: string (YYYY-MM-DD)
   *   - registration_end_date: string (YYYY-MM-DD)
   *   - event_date: string (YYYY-MM-DD)
   *   - event_name: string
   *   - category: string
   *
   * @return int
   *   The ID of the newly inserted event.
   */
  public function addEvent(array $data): int
  {
    return (int) $this->database->insert('event_configurations')
      ->fields([
        'registration_start_date' => $data['registration_start_date'],
        'registration_end_date' => $data['registration_end_date'],
        'event_date' => $data['event_date'],
        'event_name' => $data['event_name'],
        'category' => $data['category'],
      ])
      ->execute();
  }

  /**
   * Gets events filtered by category.
   *
   * @param string $category
   *   The event category to filter by.
   *
   * @return array
   *   An array of event objects.
   */
  public function getEventsByCategory(string $category): array
  {
    return $this->database->select('event_configurations', 'ec')
      ->fields('ec')
      ->condition('category', $category)
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchAll();
  }

  /**
   * Checks if a registration already exists for the given email and event.
   *
   * @param string $email
   *   The participant's email address.
   * @param int $eventId
   *   The event ID.
   *
   * @return bool
   *   TRUE if a duplicate registration exists, FALSE otherwise.
   */
  public function checkDuplicateRegistration(string $email, int $eventId): bool
  {
    $count = $this->database->select('event_registrations', 'er')
      ->condition('email', $email)
      ->condition('event_id', $eventId)
      ->countQuery()
      ->execute()
      ->fetchField();

    return (int) $count > 0;
  }

  /**
   * Adds a new event registration.
   *
   * @param array $data
   *   An associative array containing:
   *   - full_name: string
   *   - email: string
   *   - college: string
   *   - department: string
   *   - event_id: int
   *
   * @return int
   *   The ID of the newly inserted registration.
   */
  public function addRegistration(array $data): int
  {
    return (int) $this->database->insert('event_registrations')
      ->fields([
        'full_name' => $data['full_name'],
        'email' => $data['email'],
        'college' => $data['college'],
        'department' => $data['department'],
        'event_id' => $data['event_id'],
        'created' => $this->time->getRequestTime(),
      ])
      ->execute();
  }

  /**
   * Gets all events.
   *
   * @return array
   *   An array of all event objects.
   */
  public function getAllEvents(): array
  {
    return $this->database->select('event_configurations', 'ec')
      ->fields('ec')
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets an event by ID.
   *
   * @param int $eventId
   *   The event ID.
   *
   * @return object|null
   *   The event object or NULL if not found.
   */
  public function getEventById(int $eventId): ?object
  {
    $result = $this->database->select('event_configurations', 'ec')
      ->fields('ec')
      ->condition('id', $eventId)
      ->execute()
      ->fetchObject();

    return $result ?: NULL;
  }

  /**
   * Gets events filtered by category and date.
   *
   * @param string $category
   *   The event category.
   * @param string $eventDate
   *   The event date (YYYY-MM-DD).
   *
   * @return array
   *   An array of event objects.
   */
  public function getEventsByCategoryAndDate(string $category, string $eventDate): array
  {
    return $this->database->select('event_configurations', 'ec')
      ->fields('ec')
      ->condition('category', $category)
      ->condition('event_date', $eventDate)
      ->orderBy('event_name', 'ASC')
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets unique event dates for a category.
   *
   * @param string $category
   *   The event category.
   *
   * @return array
   *   An array of unique event dates.
   */
  public function getEventDatesByCategory(string $category): array
  {
    return $this->database->select('event_configurations', 'ec')
      ->fields('ec', ['event_date'])
      ->condition('category', $category)
      ->groupBy('event_date')
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchCol();
  }

  /**
   * Gets all unique event dates from registrations.
   *
   * @return array
   *   An array of unique event dates.
   */
  public function getUniqueEventDates(): array
  {
    return $this->database->select('event_configurations', 'ec')
      ->fields('ec', ['event_date'])
      ->groupBy('event_date')
      ->orderBy('event_date', 'ASC')
      ->execute()
      ->fetchCol();
  }

  /**
   * Gets event names for a specific date.
   *
   * @param string $eventDate
   *   The event date (YYYY-MM-DD).
   *
   * @return array
   *   An array of event objects.
   */
  public function getEventsByDate(string $eventDate): array
  {
    return $this->database->select('event_configurations', 'ec')
      ->fields('ec')
      ->condition('event_date', $eventDate)
      ->orderBy('event_name', 'ASC')
      ->execute()
      ->fetchAll();
  }

  /**
   * Gets registrations with optional filters.
   *
   * @param array $filters
   *   Optional filters:
   *   - event_date: Filter by event date
   *   - event_id: Filter by specific event ID
   *
   * @return array
   *   An array of registration objects with event details.
   */
  public function getRegistrations(array $filters = []): array
  {
    $query = $this->database->select('event_registrations', 'er');
    $query->join('event_configurations', 'ec', 'er.event_id = ec.id');
    $query->fields('er', ['id', 'full_name', 'email', 'college', 'department', 'created']);
    $query->fields('ec', ['event_name', 'event_date', 'category']);
    $query->addField('er', 'event_id', 'event_id');

    if (!empty($filters['event_date'])) {
      $query->condition('ec.event_date', $filters['event_date']);
    }

    if (!empty($filters['event_id'])) {
      $query->condition('er.event_id', $filters['event_id']);
    }

    $query->orderBy('er.created', 'DESC');

    return $query->execute()->fetchAll();
  }

  /**
   * Gets the count of registrations with optional filters.
   *
   * @param array $filters
   *   Optional filters (same as getRegistrations).
   *
   * @return int
   *   The count of registrations.
   */
  public function getRegistrationCount(array $filters = []): int
  {
    $query = $this->database->select('event_registrations', 'er');
    $query->join('event_configurations', 'ec', 'er.event_id = ec.id');

    if (!empty($filters['event_date'])) {
      $query->condition('ec.event_date', $filters['event_date']);
    }

    if (!empty($filters['event_id'])) {
      $query->condition('er.event_id', $filters['event_id']);
    }

    return (int) $query->countQuery()->execute()->fetchField();
  }

}
