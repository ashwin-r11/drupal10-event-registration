<?php

declare(strict_types=1);

namespace Drupal\event_registration\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\event_registration\Service\EventRegistrationRepository;

/**
 * Access check for the public registration form.
 *
 * Verifies that at least one event has an active registration period.
 */
class RegistrationAccessCheck implements AccessInterface
{

    /**
     * The event registration repository.
     *
     * @var \Drupal\event_registration\Service\EventRegistrationRepository
     */
    protected EventRegistrationRepository $repository;

    /**
     * Constructs a RegistrationAccessCheck object.
     *
     * @param \Drupal\event_registration\Service\EventRegistrationRepository $repository
     *   The event registration repository.
     */
    public function __construct(EventRegistrationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Checks access for the registration form.
     *
     * @return \Drupal\Core\Access\AccessResultInterface
     *   The access result.
     */
    public function access(): AccessResultInterface
    {
        $today = date('Y-m-d');
        $events = $this->repository->getAllEvents();

        foreach ($events as $event) {
            // Check if current date is within registration period.
            if ($today >= $event->registration_start_date && $today <= $event->registration_end_date) {
                return AccessResult::allowed()->setCacheMaxAge(0);
            }
        }

        return AccessResult::forbidden('No events are currently accepting registrations.')
            ->setCacheMaxAge(0);
    }

}
