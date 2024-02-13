<?php

namespace Drupal\os2web_nemlogin_autologout\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\UserData;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides settings for os2web_nemlogin_autologout module.
 */
class AutologoutSettingsForm extends ConfigFormBase {

  /**
   * The module manager service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The user.data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Constructs an AutologoutSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module manager service.
   * @param \Drupal\user\UserData $user_data
   *   The user.data service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, UserData $user_data) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('user.data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['os2web_nemlogin_autologout.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2web_nemlogin_autologout_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('os2web_nemlogin_autologout.settings');
    $default_dialog_title = $config->get('dialog_title');
    if (!$default_dialog_title) {
      $default_dialog_title = $this->config('system.site')->get('name') . ' Alert';
    }

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable autologout'),
      '#default_value' => $config->get('enabled'),
      '#weight' => -20,
      '#description' => $this->t("Enable autologout on this site."),
    ];

    $form['timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeout value in seconds'),
      '#default_value' => $config->get('timeout'),
      '#size' => 8,
      '#weight' => -10,
      '#description' => $this->t('The length of inactivity time, in seconds, before automated log out. Must be 60 seconds or greater.'),
    ];

    $form['padding'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeout padding'),
      '#default_value' => $config->get('padding'),
      '#size' => 8,
      '#weight' => -6,
      '#description' => $this->t('How many seconds to give a user to respond to the logout dialog before ending their session.'),
    ];

    $form['no_dialog'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not display the logout dialog'),
      '#default_value' => $config->get('no_dialog'),
      '#description' => $this->t('Enable this if you want users to logout right away and skip displaying the logout dialog.'),
    ];

    $form['dialog_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Dialog title'),
      '#default_value' => $default_dialog_title,
      '#size' => 40,
      '#description' => $this->t('This text will be dialog box title.'),
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to display in the logout dialog'),
      '#default_value' => $config->get('message'),
      '#size' => 40,
      '#description' => $this->t('This message must be plain text as it might appear in a JavaScript confirm dialog.'),
    ];

    $form['inactivity_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message to display to the user after they are logged out'),
      '#default_value' => $config->get('inactivity_message'),
      '#size' => 40,
      '#description' => $this->t('This message is displayed after the user was logged out due to inactivity. You can leave this blank to show no message to the user.'),
    ];

    $form['inactivity_message_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of the message to display'),
      '#default_value' => $config->get('inactivity_message_type'),
      '#description' => $this->t('Specifies whether to display the message as status or warning.'),
      '#options' => [
        MessengerInterface::TYPE_STATUS => $this->t('Status'),
        MessengerInterface::TYPE_WARNING => $this->t('Warning'),
      ],
    ];

    $form['modal_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Modal width'),
      '#default_value' => $config->get('modal_width'),
      '#size' => 40,
      '#description' => $this->t('This modal dialog width in pixels.'),
    ];

    $form['disable_buttons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable buttons'),
      '#default_value' => $config->get('disable_buttons'),
      '#description' => $this->t('Disable Yes/No buttons for automatic logout popout.'),
    ];

    $form['yes_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom confirm button text'),
      '#default_value' => $config->get('yes_button'),
      '#size' => 40,
      '#description' => $this->t('Add custom text to confirmation button.'),
    ];

    $form['no_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom decline button text'),
      '#default_value' => $config->get('no_button'),
      '#size' => 40,
      '#description' => $this->t('Add custom text to decline button.'),
    ];

    if ($this->moduleHandler->moduleExists('jstimer') && $this->moduleHandler->moduleExists('jst_timer')) {
      $form['jstimer_format'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Autologout block time format'),
        '#default_value' => $config->get('jstimer_format'),
        '#description' => $this->t('Change the display of the dynamic timer. Available replacement values are: %day%, %month%, %year%, %dow%, %moy%, %years%, %ydays%, %days%, %hours%, %mins%, and %secs%.'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $new_stack = [];
    if (!empty($values['table'])) {
      foreach ($values['table'] as $key => $pair) {
        if (is_array($pair)) {
          foreach ($pair as $pairkey => $pairvalue) {
            $new_stack[$key][$pairkey] = $pairvalue;
          }
        }
      }
    }

    $timeout = $values['timeout'];
    // Validate timeout.
    if ($timeout < 60) {
      $form_state->setErrorByName('timeout', $this->t('The timeout value must be an integer 60 seconds or greater.'));
    }
    elseif (!is_numeric($timeout) || ((int) $timeout != $timeout)) {
      $form_state->setErrorByName('timeout', $this->t('The timeout must be an integer greater than or equal to 60'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $autologout_settings = $this->config('os2web_nemlogin_autologout.settings');

    $autologout_settings
      ->set('enabled', $values['enabled'])
      ->set('timeout', $values['timeout'])
      ->set('padding', $values['padding'])
      ->set('no_dialog', $values['no_dialog'])
      ->set('dialog_title', $values['dialog_title'])
      ->set('message', $values['message'])
      ->set('inactivity_message', $values['inactivity_message'])
      ->set('inactivity_message_type', $values['inactivity_message_type'])
      ->set('modal_width', $values['modal_width'])
      ->set('disable_buttons', $values['disable_buttons'])
      ->set('yes_button', $values['yes_button'])
      ->set('no_button', $values['no_button'])
      ->save();

    if (isset($values['jstimer_format'])) {
      $autologout_settings->set('jstimer_format', $values['jstimer_format'])->save();
    }

    parent::submitForm($form, $form_state);
  }

}
