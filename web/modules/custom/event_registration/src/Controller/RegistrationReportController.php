<?php

declare(strict_types=1);

namespace Drupal\event_registration\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\event_registration\Service\EventRegistrationRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for the admin registration report page.
 */
class RegistrationReportController extends ControllerBase
{

    /**
     * The event registration repository.
     *
     * @var \Drupal\event_registration\Service\EventRegistrationRepository
     */
    protected EventRegistrationRepository $repository;

    /**
     * Category labels.
     */
    const CATEGORIES = [
        'online_workshop' => 'Online Workshop',
        'hackathon' => 'Hackathon',
        'conference' => 'Conference',
        'one_day_workshop' => 'One-day Workshop',
    ];

    /**
     * Constructs a RegistrationReportController object.
     *
     * @param \Drupal\event_registration\Service\EventRegistrationRepository $repository
     *   The event registration repository.
     */
    public function __construct(EventRegistrationRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('event_registration.repository')
        );
    }

    /**
     * Renders the registration report page.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The current request.
     *
     * @return array
     *   A render array for the page.
     */
    public function listing(Request $request): array
    {
        $event_date = $request->query->get('event_date', '');
        $event_id = $request->query->get('event_id', '');

        $filters = [];
        if (!empty($event_date)) {
            $filters['event_date'] = $event_date;
        }
        if (!empty($event_id)) {
            $filters['event_id'] = (int) $event_id;
        }

        // Get filter options.
        $dates = $this->repository->getUniqueEventDates();
        $date_options = ['' => $this->t('- All Dates -')];
        foreach ($dates as $date) {
            $date_options[$date] = date('F j, Y', strtotime($date));
        }

        // Get events (all events if no date selected, or filtered by date).
        $event_options = ['' => $this->t('- All Events -')];
        if (!empty($event_date)) {
            $events = $this->repository->getEventsByDate($event_date);
        } else {
            $events = $this->repository->getAllEvents();
        }
        foreach ($events as $event) {
            $event_options[$event->id] = $event->event_name;
        }

        // Get registrations and count.
        $registrations = $this->repository->getRegistrations($filters);
        $count = $this->repository->getRegistrationCount($filters);

        $build = [];

        // Build filter form HTML.
        $date_select_options = '';
        foreach ($date_options as $value => $label) {
            $selected = ($value === $event_date) ? ' selected' : '';
            $date_select_options .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . htmlspecialchars((string) $label) . '</option>';
        }

        $event_select_options = '';
        foreach ($event_options as $value => $label) {
            $selected = ((string) $value === (string) $event_id) ? ' selected' : '';
            $event_select_options .= '<option value="' . htmlspecialchars((string) $value) . '"' . $selected . '>' . htmlspecialchars((string) $label) . '</option>';
        }

        $build['filters'] = [
            '#markup' => Markup::create('
                <form method="get" action="" class="registration-filters" style="margin-bottom: 20px;">
                    <div style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                        <div>
                            <label for="filter-event-date"><strong>' . $this->t('Filter by Event Date') . '</strong></label><br>
                            <select name="event_date" id="filter-event-date" style="padding: 8px; min-width: 200px;">
                                ' . $date_select_options . '
                            </select>
                        </div>
                        <div>
                            <label for="filter-event-id"><strong>' . $this->t('Filter by Event') . '</strong></label><br>
                            <select name="event_id" id="filter-event-id" style="padding: 8px; min-width: 200px;">
                                ' . $event_select_options . '
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="button button--primary" style="padding: 8px 16px;">' . $this->t('Filter') . '</button>
                        </div>
                    </div>
                </form>
            '),
        ];

        // Export link.
        $export_url = '/admin/reports/event-registrations/export';
        if (!empty($event_date)) {
            $export_url .= '?event_date=' . urlencode($event_date);
            if (!empty($event_id)) {
                $export_url .= '&event_id=' . urlencode($event_id);
            }
        } elseif (!empty($event_id)) {
            $export_url .= '?event_id=' . urlencode($event_id);
        }

        $build['export'] = [
            '#type' => 'link',
            '#title' => $this->t('Export to CSV'),
            '#url' => \Drupal\Core\Url::fromUri('internal:' . $export_url),
            '#attributes' => [
                'class' => ['button', 'button--action', 'button--primary'],
                'style' => 'margin: 1em 0;',
            ],
        ];

        // Participant count.
        $build['count'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'participant-count',
                'class' => ['participant-count'],
                'style' => 'font-size: 1.2em; font-weight: bold; margin: 1em 0;',
            ],
            'content' => [
                '#markup' => $this->t('Total Participants: @count', ['@count' => $count]),
            ],
        ];

        // Registrations table.
        $build['table'] = [
            '#type' => 'table',
            '#header' => [
                $this->t('ID'),
                $this->t('Full Name'),
                $this->t('Email'),
                $this->t('College'),
                $this->t('Department'),
                $this->t('Event'),
                $this->t('Date'),
                $this->t('Category'),
                $this->t('Registered On'),
            ],
            '#empty' => $this->t('No registrations found.'),
            '#attributes' => ['id' => 'registrations-table'],
            '#prefix' => '<div id="registrations-table-wrapper">',
            '#suffix' => '</div>',
        ];

        foreach ($registrations as $reg) {
            $build['table'][] = [
                ['#markup' => $reg->id],
                ['#markup' => htmlspecialchars($reg->full_name)],
                ['#markup' => htmlspecialchars($reg->email)],
                ['#markup' => htmlspecialchars($reg->college)],
                ['#markup' => htmlspecialchars($reg->department)],
                ['#markup' => htmlspecialchars($reg->event_name)],
                ['#markup' => date('F j, Y', strtotime($reg->event_date))],
                ['#markup' => self::CATEGORIES[$reg->category] ?? $reg->category],
                ['#markup' => date('M j, Y g:i A', (int) $reg->created)],
            ];
        }

        return $build;
    }

    /**
     * Exports registrations as CSV.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The current request.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     *   A streamed CSV response.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $event_date = $request->query->get('event_date', '');
        $event_id = $request->query->get('event_id', '');

        $filters = [];
        if (!empty($event_date)) {
            $filters['event_date'] = $event_date;
        }
        if (!empty($event_id)) {
            $filters['event_id'] = (int) $event_id;
        }

        $registrations = $this->repository->getRegistrations($filters);

        $response = new StreamedResponse(function () use ($registrations) {
            $handle = fopen('php://output', 'w');

            // CSV header.
            fputcsv($handle, [
                'ID',
                'Full Name',
                'Email',
                'College',
                'Department',
                'Event Name',
                'Event Date',
                'Category',
                'Registered On',
            ]);

            // CSV rows.
            foreach ($registrations as $reg) {
                fputcsv($handle, [
                    $reg->id,
                    $reg->full_name,
                    $reg->email,
                    $reg->college,
                    $reg->department,
                    $reg->event_name,
                    date('Y-m-d', strtotime($reg->event_date)),
                    self::CATEGORIES[$reg->category] ?? $reg->category,
                    date('Y-m-d H:i:s', (int) $reg->created),
                ]);
            }

            fclose($handle);
        });

        $filename = 'event_registrations_' . date('Y-m-d_His') . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

}
