<?php

namespace Drupal\os2web_nemlogin_autologout\EventSubscriber;

use Drupal\os2web_nemlogin\Service\AuthProviderService;
use Drupal\os2web_nemlogin_autologout\AutologoutManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines os2web_nemlogin_autologout Subscriber.
 */
class AutologoutSubscriber implements EventSubscriberInterface {

  /**
   * The autologout manager service.
   *
   * @var \Drupal\os2web_nemlogin_autologout\AutologoutManagerInterface
   */
  protected $autoLogoutManager;

  /**
   * The user account service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The Config service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The theme manager service.
   *
   * @var \Drupal\Core\Theme\ThemeManager
   */
  protected $theme;

  /**
   * The Time Service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The request stacks service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Nemlogin Authentication helper service.
   *
   * @var \Drupal\os2web_nemlogin\Service\AuthProviderService
   */
  protected $authProvider;

  /**
   * Constructs an AutologoutSubscriber object.
   *
   * @param \Drupal\os2web_nemlogin_autologout\AutologoutManagerInterface $autologout
   *   The autologout manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account service.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The Config service.
   * @param \Drupal\Core\Theme\ThemeManager $theme
   *   The theme manager service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param AuthProviderService $authProvider
   *    Nemlogin auth provider.
   */
  public function __construct(AutologoutManagerInterface $autologout, AccountInterface $account, ConfigFactory $config, ThemeManager $theme, TimeInterface $time, RequestStack $requestStack, LanguageManagerInterface $language_manager, AuthProviderService $authProvider) {
    $this->autoLogoutManager = $autologout;
    $this->currentUser = $account;
    $this->config = $config;
    $this->theme = $theme;
    $this->time = $time;
    $this->requestStack = $requestStack;
    $this->languageManager = $language_manager;
    $this->authProvider = $authProvider;
  }

  /**
   * Check for os2web_nemlogin_autologout JS.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event) {
    $autologout_manager = $this->autoLogoutManager;

    if ($this->autoLogoutManager->preventJs()) {
      return;
    }

    $now = $this->time->getRequestTime();
    // Check if anything wants to be refresh only. This URL would include the
    // javascript but will keep the login alive whilst that page is opened.
    $timeout = $autologout_manager->getUserTimeout();
    $timeout_padding = $this->config->get('os2web_nemlogin_autologout.settings')->get('padding');

    // We need a backup plan if JS is disabled.
    $plugin = $this->authProvider->getActivePlugin();
    $last_login = $plugin->getSessionRefreshed();
    if ($last_login) {
      // If time since last access is > timeout + padding, log them out.
      $diff = $now - $last_login;
      if ($diff >= ($timeout + (int) $timeout_padding)) {
        $autologout_manager->logout();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onRequest', 100];

    return $events;
  }

}
