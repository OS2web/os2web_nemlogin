<?php

namespace Drupal\os2web_nemlogin_autologout\Controller;

use Drupal\os2web_nemlogin_autologout\AutologoutManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for autologout module routes.
 */
class AutologoutController extends ControllerBase {

  /**
   * The autologout manager service.
   *
   * @var \Drupal\os2web_nemlogin_autologout\AutologoutManagerInterface
   */
  protected $autoLogoutManager;


  /**
   * The Time Service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs an AutologoutSubscriber object.
   *
   * @param \Drupal\os2web_nemlogin_autologout\AutologoutManagerInterface $autologout
   *   The autologout manager service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    AutologoutManagerInterface $autologout,
    TimeInterface $time
  ) {
    $this->autoLogoutManager = $autologout;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('os2web_nemlogin_autologout.manager'),
      $container->get('datetime.time')
    );
  }

  /**
   * AJAX logout.
   */
  public function ajaxLogout() {
    $this->autoLogoutManager->logout();
    $response = new AjaxResponse();
    $response->setStatusCode(200);

    return $response;
  }

  /**
   * Ajax callback to reset the last access session variable.
   */
  public function ajaxSetLast() {
    $this->autoLogoutManager->resetTime();

    // Reset the timer.
    $response = new AjaxResponse();
    $markup = $this->autoLogoutManager->createTimer();
    $response->addCommand(new ReplaceCommand('#timer', $markup));

    return $response;
  }

  /**
   * AJAX callback that returns the time remaining for this user is logged out.
   */
  public function ajaxGetRemainingTime() {
    $response = new AjaxResponse();
    $time_remaining_ms = $this->autoLogoutManager->getRemainingTime() * 1000;

    // Reset the timer.
    $markup = $this->autoLogoutManager->createTimer();

    $response->addCommand(new ReplaceCommand('#timer', $markup));
    $response->addCommand(new SettingsCommand(['time' => $time_remaining_ms]));

    return $response;
  }

}
