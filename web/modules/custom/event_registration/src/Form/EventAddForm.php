<?php

declare(strict_types=1);

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\event_registration\Service\EventRegistrationRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adding new events to the event configuration table.
 */
class EventAddForm extends FormBase
{

    /**
     * The event registration repository.
     *
     * @var \Drupal\event_registration\Service\EventRegistrationRepository
     */
    protected EventRegistrationRepository $repository;

    /**
     * Event category options.
     */
    const CATEGORIES = [
        'online_workshop' => 'Online Workshop',
        'hackathon' => 'Hackathon',
        'conference' => 'Conference',
        'one_day_workshop' => 'One-day Workshop',
    ];

    /**
     * Constructs an EventAddForm object.
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
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'event_registration_add_event';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['event_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Event Name'),
            '#description' => $this->t('Enter the name of the event.'),
            '#required' => TRUE,
            '#maxlength' => 255,
        ];

        $form['category'] = [
            '#type' => 'select',
            '#title' => $this->t('Event Category'),
            '#description' => $this->t('Select the category for this event.'),
            '#options' => ['' => $this->t('- Select Category -')] + self::CATEGORIES,
            '#required' => TRUE,
        ];

        $form['event_date'] = [
            '#type' => 'date',
            '#title' => $this->t('Event Date'),
            '#description' => $this->t('The date when the event will take place.'),
            '#required' => TRUE,
        ];

        $form['registration_start_date'] = [
            '#type' => 'date',
            '#title' => $this->t('Registration Start Date'),
            '#description' => $this->t('The date when registration opens.'),
            '#required' => TRUE,
        ];

        $form['registration_end_date'] = [
            '#type' => 'date',
            '#title' => $this->t('Registration End Date'),
            '#description' => $this->t('The date when registration closes.'),
            '#required' => TRUE,
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add Event'),
            '#button_type' => 'primary',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        $registration_start = $form_state->getValue('registration_start_date');
        $registration_end = $form_state->getValue('registration_end_date');
        $event_date = $form_state->getValue('event_date');

        // Validate registration start date is before end date.
        if (!empty($registration_start) && !empty($registration_end)) {
            if (strtotime($registration_start) > strtotime($registration_end)) {
                $form_state->setErrorByName(
                    'registration_start_date',
                    $this->t('Registration start date must be before or equal to the end date.')
                );
            }
        }

        // Validate event date is on or after registration start date.
        if (!empty($event_date) && !empty($registration_start)) {
            if (strtotime($event_date) < strtotime($registration_start)) {
                $form_state->setErrorByName(
                    'event_date',
                    $this->t('Event date must be on or after the registration start date.')
                );
            }
        }

        // Validate event name does not contain special characters.
        $event_name = $form_state->getValue('event_name');
        if (!empty($event_name) && preg_match('/[<>\"\'&]/', $event_name)) {
            $form_state->setErrorByName(
                'event_name',
                $this->t('Event name cannot contain special characters like <, >, ", \', or &.')
            );
        }

        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        try {
            $event_id = $this->repository->addEvent([
                'event_name' => trim($form_state->getValue('event_name')),
                'category' => $form_state->getValue('category'),
                'event_date' => $form_state->getValue('event_date'),
                'registration_start_date' => $form_state->getValue('registration_start_date'),
                'registration_end_date' => $form_state->getValue('registration_end_date'),
            ]);

            $this->messenger()->addStatus(
                $this->t('Event "@name" has been successfully created with ID @id.', [
                    '@name' => $form_state->getValue('event_name'),
                    '@id' => $event_id,
                ])
            );

            // Redirect back to the same form for adding more events.
            $form_state->setRedirect('event_registration.add_event');
        } catch (\Exception $e) {
            $this->messenger()->addError(
                $this->t('An error occurred while saving the event. Please try again.')
            );
            $this->getLogger('event_registration')->error('Event creation failed: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

}
