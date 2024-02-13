<?php

namespace Drupal\os2web_nemlogin_autologout\Form;

use Drupal\os2web_nemlogin_autologout\AutologoutManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a settings for os2web_nemlogin_autologout module.
 */
class AutologoutBlockForm extends FormBase {

  /**
   * The autologout manager service.
   *
   * @var \Drupal\os2web_nemlogin_autologout\AutologoutManagerInterface
   */
  protected $autoLogoutManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2web_nemlogin_autologout_block_settings';
  }

  /**
   * Constructs an AutologoutBlockForm object.
   *
   * @param \Drupal\os2web_nemlogin_autologout\AutologoutManagerInterface $autologout
   *   The autologout manager service.
   */
  public function __construct(AutologoutManagerInterface $autologout) {
    $this->autoLogoutManager = $autologout;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os2web_nemlogin_autologout.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['reset'] = [
      '#type' => 'button',
      '#value' => $this->t('Reset Timeout'),
      '#weight' => 1,
      '#limit_validation_errors' => FALSE,
      '#executes_submit_callback' => FALSE,
      '#ajax' => [
        'callback' => 'os2web_nemlogin_autologout_ajax_set_last',
      ],
    ];

    $form['timer'] = [
      '#markup' => $this->autoLogoutManager->createTimer(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Submits on block form.
  }

}
