<?php

declare(strict_types=1);

namespace Drupal\event_registration\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\event_registration\Service\EventRegistrationRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public event registration form with AJAX-dependent dropdowns.
 */
class RegistrationForm extends FormBase
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
     * Constructs a RegistrationForm object.
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
        return 'event_registration_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        // Personal information section.
        $form['personal_info'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Personal Information'),
        ];

        $form['personal_info']['full_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Full Name'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#description' => $this->t('Enter your full name.'),
        ];

        $form['personal_info']['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email Address'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#description' => $this->t('Enter a valid email address.'),
        ];

        $form['personal_info']['college'] = [
            '#type' => 'textfield',
            '#title' => $this->t('College Name'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#description' => $this->t('Enter your college or institution name.'),
        ];

        $form['personal_info']['department'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Department'),
            '#required' => TRUE,
            '#maxlength' => 255,
            '#description' => $this->t('Enter your department.'),
        ];

        // Event selection section.
        $form['event_selection'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Event Selection'),
        ];

        // Get selected values from form state.
        $selected_category = $form_state->getValue('category') ?? '';
        $selected_date = $form_state->getValue('event_date') ?? '';

        $form['event_selection']['category'] = [
            '#type' => 'select',
            '#title' => $this->t('Event Category'),
            '#options' => ['' => $this->t('- Select Category -')] + self::CATEGORIES,
            '#required' => TRUE,
            '#default_value' => $selected_category,
            '#ajax' => [
                'callback' => '::updateEventDates',
                'wrapper' => 'event-date-wrapper',
                'event' => 'change',
            ],
        ];

        // Build event date options based on selected category.
        $date_options = ['' => $this->t('- Select Event Date -')];
        if (!empty($selected_category)) {
            $dates = $this->repository->getEventDatesByCategory($selected_category);
            foreach ($dates as $date) {
                $date_options[$date] = date('F j, Y', strtotime($date));
            }
        }

        $form['event_selection']['event_date'] = [
            '#type' => 'select',
            '#title' => $this->t('Event Date'),
            '#options' => $date_options,
            '#required' => TRUE,
            '#default_value' => $selected_date,
            '#prefix' => '<div id="event-date-wrapper">',
            '#suffix' => '</div>',
            '#validated' => TRUE,
            '#ajax' => [
                'callback' => '::updateEventNames',
                'wrapper' => 'event-name-wrapper',
                'event' => 'change',
            ],
        ];

        // Build event name options based on selected category and date.
        $event_options = ['' => $this->t('- Select Event -')];
        if (!empty($selected_category) && !empty($selected_date)) {
            $events = $this->repository->getEventsByCategoryAndDate($selected_category, $selected_date);
            foreach ($events as $event) {
                $event_options[$event->id] = $event->event_name;
            }
        }

        $form['event_selection']['event_id'] = [
            '#type' => 'select',
            '#title' => $this->t('Event Name'),
            '#options' => $event_options,
            '#required' => TRUE,
            '#prefix' => '<div id="event-name-wrapper">',
            '#suffix' => '</div>',
            '#validated' => TRUE,
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Register'),
            '#button_type' => 'primary',
        ];

        return $form;
    }

    /**
     * AJAX callback to update event dates based on selected category.
     *
     * @param array $form
     *   The form array.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The form state.
     *
     * @return \Drupal\Core\Ajax\AjaxResponse
     *   The AJAX response.
     */
    public function updateEventDates(array &$form, FormStateInterface $form_state): AjaxResponse
    {
        $response = new AjaxResponse();

        // Replace event date dropdown.
        $response->addCommand(new ReplaceCommand(
            '#event-date-wrapper',
            $form['event_selection']['event_date']
        ));

        // Also reset event name dropdown.
        $response->addCommand(new ReplaceCommand(
            '#event-name-wrapper',
            $form['event_selection']['event_id']
        ));

        return $response;
    }

    /**
     * AJAX callback to update event names based on selected category and date.
     *
     * @param array $form
     *   The form array.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The form state.
     *
     * @return array
     *   The event name form element.
     */
    public function updateEventNames(array &$form, FormStateInterface $form_state): array
    {
        return $form['event_selection']['event_id'];
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        parent::validateForm($form, $form_state);

        // Get form values.
        $full_name = trim($form_state->getValue('full_name') ?? '');
        $email = trim($form_state->getValue('email') ?? '');
        $college = trim($form_state->getValue('college') ?? '');
        $department = trim($form_state->getValue('department') ?? '');
        $event_id = $form_state->getValue('event_id');

        // Regex pattern to disallow special characters.
        // Allows letters, numbers, spaces, hyphens, apostrophes, periods, and commas.
        $special_char_pattern = '/[<>\"&\\\\\/\[\]{}|^~`]/';

        // Validate full name for special characters.
        if (!empty($full_name) && preg_match($special_char_pattern, $full_name)) {
            $form_state->setErrorByName(
                'full_name',
                $this->t('Full name contains invalid characters. Please avoid using special characters like <, >, ", &, etc.')
            );
        }

        // Validate college name for special characters.
        if (!empty($college) && preg_match($special_char_pattern, $college)) {
            $form_state->setErrorByName(
                'college',
                $this->t('College name contains invalid characters. Please avoid using special characters like <, >, ", &, etc.')
            );
        }

        // Validate department for special characters.
        if (!empty($department) && preg_match($special_char_pattern, $department)) {
            $form_state->setErrorByName(
                'department',
                $this->t('Department contains invalid characters. Please avoid using special characters like <, >, ", &, etc.')
            );
        }

        // Validate email format (additional check beyond HTML5 validation).
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $form_state->setErrorByName(
                'email',
                $this->t('Please enter a valid email address.')
            );
        }

        // Check for duplicate registration (Email + Event ID).
        if (!empty($email) && !empty($event_id) && $event_id !== '') {
            $is_duplicate = $this->repository->checkDuplicateRegistration($email, (int) $event_id);
            if ($is_duplicate) {
                $form_state->setErrorByName(
                    'email',
                    $this->t('You have already registered for this event with this email address.')
                );
            }
        }

        // Validate that an event is selected.
        if (empty($event_id) || $event_id === '') {
            $form_state->setErrorByName(
                'event_id',
                $this->t('Please select an event to register for.')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        try {
            // Gather form values.
            $data = [
                'full_name' => trim($form_state->getValue('full_name')),
                'email' => trim($form_state->getValue('email')),
                'college' => trim($form_state->getValue('college')),
                'department' => trim($form_state->getValue('department')),
                'event_id' => (int) $form_state->getValue('event_id'),
            ];

            // Save registration using repository.
            $registration_id = $this->repository->addRegistration($data);

            // Get event details for success message.
            $event = $this->repository->getEventById($data['event_id']);
            $event_name = $event ? $event->event_name : $this->t('Unknown Event');

            // Display success message.
            $this->messenger()->addStatus(
                $this->t('Thank you, @name! You have successfully registered for "@event".', [
                    '@name' => $data['full_name'],
                    '@event' => $event_name,
                ])
            );

            // Log the registration.
            $this->getLogger('event_registration')->info('New registration #@id: @email for event @event_id', [
                '@id' => $registration_id,
                '@email' => $data['email'],
                '@event_id' => $data['event_id'],
            ]);

            // Redirect to front page after successful registration.
            $form_state->setRedirect('<front>');
        } catch (\Exception $e) {
            $this->messenger()->addError(
                $this->t('An error occurred while processing your registration. Please try again later.')
            );
            $this->getLogger('event_registration')->error('Registration failed: @message', [
                '@message' => $e->getMessage(),
            ]);
        }
    }

}
