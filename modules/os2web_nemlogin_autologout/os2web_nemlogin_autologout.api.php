<?php

/**
 * @file
 * Describe hooks provided by the os2web_nemlogin_autologout module.
 */

/**
 * Prevent autologout logging a user out.
 *
 * This allows other modules to indicate that a page should not be included
 * in the autologout checks.
 *
 * @return bool
 *   Return TRUE if you do not want the user to be logged out.
 *   Return FALSE (or nothing) if you want to leave the autologout
 *   process alone.
 */
function hook_os2web_nemlogin_autologout_prevent() {
  // Don't include autologout JS checks on ajax callbacks.
  $path_args = explode('/', current_path());
  $blacklist = [
    'ajax',
    'os2web_nemlogin_autologout_ajax_logout',
    'os2web_nemlogin_autologout_ajax_set_last',
  ];

  if (in_array($path_args[0], $blacklist)) {
    return TRUE;
  }
}
