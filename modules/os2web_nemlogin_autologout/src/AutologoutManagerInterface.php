<?php

namespace Drupal\os2web_nemlogin_autologout;

use Drupal\user\UserInterface;

/**
 * Interface for AutologoutManager.
 */
interface AutologoutManagerInterface {

  /**
   * Get the timer HTML markup.
   *
   * @return string
   *   HTML to insert a countdown timer.
   */
  public function createTimer();

  /**
   * Get the time remaining before logout.
   *
   * @return int
   *   Number of seconds remaining.
   */
  public function getRemainingTime();

  /**
   * Resets time to the current time value.
   */
  public function resetTime();

  /**
   * Get a user's timeout in seconds.
   *
   * @return int
   *   The number of seconds the user can be idle for before being logged out.
   *   A value of 0 means no timeout.
   */
  public function getUserTimeout();

  /**
   * Perform Logout.
   *
   * Helper to perform the actual logout. Destroys the session of the logged
   * in user.
   */
  public function logout();

  /**
   * Display the inactivity message if required when the user is logged out.
   */
  public function inactivityMessage();

  /**
   * Determine if autologout should be prevented.
   *
   * @return bool
   *   TRUE if there is a reason not to autologout
   *   the current user on the current page.
   */
  public function preventJs();

}
