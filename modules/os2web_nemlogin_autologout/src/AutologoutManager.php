<?php

namespace Drupal\os2web_nemlogin_autologout;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2web_nemlogin\Service\AuthProviderService;
use Drupal\Component\Utility\Xss;

/**
 * Defines an AutologoutManager service.
 */
class AutologoutManager implements AutologoutManagerInterface {

  use StringTranslationTrait;

  /**
   * The module manager service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config object for 'os2web_nemlogin_autologout.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $autoLogoutSettings;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The Time Service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The Nemlogin Authentication helper service.
   *
   * @var \Drupal\os2web_nemlogin\Service\AuthProviderService
   */
  protected $authProvider;

  /**
   * Constructs an AutologoutManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param AuthProviderService $authProvider
   *   Nemlogin auth provider.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger,
    TimeInterface $time,
    AuthProviderService $authProvider
  ) {
    $this->moduleHandler = $module_handler;
    $this->autoLogoutSettings = $config_factory->get('os2web_nemlogin_autologout.settings');
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->time = $time;
    $this->authProvider = $authProvider;
  }

  /**
   * {@inheritdoc}
   */
  public function preventJs() {
    if ($this->autoLogoutSettings->get('enabled') === FALSE) {
      // Autologout is disabled globally.
      return TRUE;
    }

    $plugin = $this->authProvider->getActivePlugin();
    if (!$plugin->isAuthenticated()) {
      return TRUE;
    }

    foreach ($this->moduleHandler->invokeAll('os2web_nemlogin_autologout_prevent') as $prevent) {
      if (!empty($prevent)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function inactivityMessage() {
    $message = Xss::filter($this->autoLogoutSettings->get('inactivity_message'));
    $type = $this->autoLogoutSettings->get('inactivity_message_type');
    if (!empty($message)) {
      $this->messenger->addMessage($this->t('@message', ['@message' => $message]), $type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function logout() {
    $plugin = $this->authProvider->getActivePlugin();
    if ($plugin->isAuthenticated()) {
      $plugin->clearValues();

      $this->inactivityMessage();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRemainingTime() {
    $plugin = $this->authProvider->getActivePlugin();
    $refreshed = $plugin->getSessionRefreshed();

    $time_passed = ($refreshed) ? $this->time->getRequestTime() - $refreshed : 0;

    $timeout = $this->getUserTimeout();
    return $timeout - $time_passed;
  }

  /**
   * {@inheritdoc}
   */
  public function canResetTime() {
    $plugin = $this->authProvider->getActivePlugin();
    $initialized = $plugin->getSessionInitialized();
    $refreshed = $plugin->getSessionRefreshed();
    $sessionTtl = $this->autoLogoutSettings->get('session_ttl');
    if (!$sessionTtl) {
      // Default value: 8 hours = 28800 seconds.
      $sessionTtl = 28800;
    }

    if ($refreshed - $initialized < $sessionTtl) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function resetTime() {
    $plugin = $this->authProvider->getActivePlugin();
    $plugin->refreshSessionTime();
  }

  /**
   * {@inheritdoc}
   */
  public function createTimer() {
    return $this->getRemainingTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getUserTimeout() {
    // Return the default timeout.
    return $this->autoLogoutSettings->get('timeout');
  }

}
