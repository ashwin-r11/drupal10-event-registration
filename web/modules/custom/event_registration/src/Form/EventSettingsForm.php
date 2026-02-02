<?php

declare(strict_types=1);

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for event registration module settings.
 */
class EventSettingsForm extends ConfigFormBase
{

    /**
     * Config settings key.
     */
    const SETTINGS = 'event_registration.settings';

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'event_registration_settings';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames(): array
    {
        return [
            static::SETTINGS,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $config = $this->config(static::SETTINGS);

        $form['notification_settings'] = [
            '#type' => 'details',
            '#title' => $this->t('Email Notification Settings'),
            '#open' => TRUE,
        ];

        $form['notification_settings']['admin_notification_email'] = [
            '#type' => 'email',
            '#title' => $this->t('Admin Notification Email'),
            '#description' => $this->t('Email address to receive notifications when new registrations are submitted.'),
            '#default_value' => $config->get('admin_notification_email') ?? '',
            '#maxlength' => 255,
        ];

        $form['notification_settings']['enable_admin_notifications'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Enable Admin Notifications'),
            '#description' => $this->t('When enabled, an email notification will be sent to the admin email address for each new registration.'),
            '#default_value' => $config->get('enable_admin_notifications') ?? FALSE,
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        $enable_notifications = $form_state->getValue('enable_admin_notifications');
        $admin_email = trim($form_state->getValue('admin_notification_email'));

        // If notifications are enabled, require an admin email.
        if ($enable_notifications && empty($admin_email)) {
            $form_state->setErrorByName(
                'admin_notification_email',
                $this->t('Admin notification email is required when notifications are enabled.')
            );
        }

        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->config(static::SETTINGS)
            ->set('admin_notification_email', trim($form_state->getValue('admin_notification_email')))
            ->set('enable_admin_notifications', (bool) $form_state->getValue('enable_admin_notifications'))
            ->save();

        parent::submitForm($form, $form_state);
    }

}
