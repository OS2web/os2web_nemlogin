/**
 * @file
 * JavaScript for os2web_nemlogin_autologout.
 */

(function ($, Drupal, cookies) {

  'use strict';

  /**
   * Attaches the batch behavior for os2web_nemlogin_autologout.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.os2web_nemlogin_autologout = {
    attach: function (context, settings) {
      if (context !== document) {
        return;
      }

      var paddingTimer;
      var theDialog;
      var t;
      var localSettings;

      // Prevent settings being overridden by ajax callbacks by cloning it.
      localSettings = jQuery.extend(true, {}, settings.os2web_nemlogin_autologout);

      // Add timer element to prevent detach of all behaviours.
      var timerMarkup = $('<div id="timer"></div>').hide();
      $('body').append(timerMarkup);

      t = setTimeout(init, localSettings.timeout);

      function init() {
        var noDialog = settings.os2web_nemlogin_autologout.no_dialog;

        // The user has not been active, ask them if they want to stay logged
        // in and start the logout timer.
        paddingTimer = setTimeout(confirmLogout, localSettings.timeout_padding);
        // While the countdown timer is going, lookup the remaining time. If
        // there is more time remaining (i.e. a user is navigating in another
        // tab), then reset the timer for opening the dialog.
        Drupal.Ajax['os2web_nemlogin_autologout.getTimeLeft'].autologoutGetTimeLeft(function (time, can_reset) {
          if (time > 0 && can_reset) {
            clearTimeout(paddingTimer);
            t = setTimeout(init, time);
          }
          else {
            // Logout user right away without displaying a confirmation dialog.
            if (noDialog || !can_reset) {
              logout();
              return;
            }
            theDialog = dialog();
          }
        });
      }

      function dialog() {
        var disableButtons = settings.os2web_nemlogin_autologout.disable_buttons;

        var buttons = {};
        if (!disableButtons) {
          var yesButton = settings.os2web_nemlogin_autologout.yes_button;
          buttons[Drupal.t(yesButton)] = function () {
            cookies.set("Drupal.visitor.os2web_nemlogin_autologout_login", Math.round((new Date()).getTime() / 1000));
            $(this).dialog("destroy");
            clearTimeout(paddingTimer);
            refresh();
          };

          var noButton = settings.os2web_nemlogin_autologout.no_button;
          buttons[Drupal.t(noButton)] = function () {
            $(this).dialog("destroy");
            logout();
          };
        }

        return $('<div id="os2web-nemlogin-autologout-confirm">' + localSettings.message + '</div>').dialog({
          modal: true,
          closeOnEscape: false,
          width: localSettings.modal_width,
          dialogClass: 'os2web-nemlogin-autologout-dialog',
          title: localSettings.title,
          buttons: buttons,
          close: function (event, ui) {
            logout();
          }
        });
      }

      // A user could have used the reset button on the tab/window they're
      // actively using, so we need to double check before actually logging out.
      function confirmLogout() {
        try {
          $(theDialog).dialog('destroy');
        } catch (exception){
        }

        Drupal.Ajax['os2web_nemlogin_autologout.getTimeLeft'].autologoutGetTimeLeft(function (time, can_reset) {
          if (time > 0 && can_reset) {
            t = setTimeout(init, time);
          }
          else {
            logout();
          }
        });
      }

      function triggerLogoutEvent(logoutMethod, logoutUrl) {
        const logoutEvent = new CustomEvent('os2web_nemlogin_autologout', {
          detail: {
            logoutMethod: logoutMethod,
            logoutUrl: logoutUrl,
          },
        });
        document.dispatchEvent(logoutEvent);
      }

      function logout() {
        $.ajax({
          url: drupalSettings.path.baseUrl + "os2web_nemlogin_autologout_ajax_logout",
          type: "POST",
          beforeSend: function (xhr) {
            xhr.setRequestHeader('X-Requested-With', {
              toString: function () {
                return '';
              }
            });
          },
          success: function () {
            var logoutUrl = window.location.href;
            triggerLogoutEvent('normal', logoutUrl);

            window.location.reload();
          },
          error: function (XMLHttpRequest, textStatus) {
            if (XMLHttpRequest.status === 403 || XMLHttpRequest.status === 404) {
              window.location.reload();
            }
          }
        });
      }

      /**
       * Get the remaining time.
       *
       * Use the Drupal ajax library to handle get time remaining events
       * because if using the JS Timer, the return will update it.
       *
       * @param function callback(time, can_reset)
       *   The function to run when ajax is successful. The time parameter
       *   is the time remaining for the current user in ms.
       */
      Drupal.Ajax.prototype.autologoutGetTimeLeft = function (callback) {
        var ajax = this;

        ajax.options.success = function (response, status) {
          if (typeof response == 'string') {
            response = $.parseJSON(response);
          }
          if (typeof response[0].command === 'string' && response[0].command === 'alert') {
            // In the event of an error, we can assume user has been logged out.
            window.location.reload();
          }

          callback(response[1].settings.time, response[1].settings.can_reset);

          response[0].data = '<div id="timer" style="display: none;">' + response[0].data + '</div>';

          // Let Drupal.ajax handle the JSON response.
          return ajax.success(response, status);
        };

        try {
          $.ajax(ajax.options);
        }
        catch (e) {
          ajax.ajaxing = false;
        }
      };

      Drupal.Ajax['os2web_nemlogin_autologout.getTimeLeft'] = Drupal.ajax({
        base: null,
        element: document.body,
        url: drupalSettings.path.baseUrl + 'os2web_nemlogin_autologout_ajax_get_time_left',
        // submit: {
        //   uactive : false
        // },
        event: 'os2web_nemlogin_autologout.getTimeLeft',
        error: function (XMLHttpRequest, textStatus) {
          // Disable error reporting to the screen.
        },
      });

      /**
       * Handle refresh event.
       *
       * Use the Drupal ajax library to handle refresh events because if using
       * the JS Timer, the return will update it.
       *
       * @param function timerFunction
       *   The function to tell the timer to run after its been restarted.
       */
      Drupal.Ajax.prototype.autologoutRefresh = function (timerfunction) {
        var ajax = this;

        if (ajax.ajaxing) {
          return false;
        }

        ajax.options.success = function (response, status) {
          if (typeof response === 'string') {
            response = $.parseJSON(response);
          }
          if (typeof response[0].command === 'string' && response[0].command === 'alert') {
            // In the event of an error, we can assume the user has been logged out.
            window.location.reload();
          }

          t = setTimeout(timerfunction, localSettings.timeout);

          // Wrap response data in timer markup to prevent detach of all behaviors.
          response[0].data = '<div id="timer" style="display: none;">' + response[0].data + '</div>';

          // Let Drupal.ajax handle the JSON response.
          return ajax.success(response, status);
        };

        try {
          $.ajax(ajax.options);
        }
        catch (e) {
          ajax.ajaxing = false;
        }
      };

      Drupal.Ajax['os2web_nemlogin_autologout.refresh'] = Drupal.ajax({
        base: null,
        element: document.body,
        url: drupalSettings.path.baseUrl + 'os2web_nemlogin_autologout_ajax_set_last',
        event: 'os2web_nemlogin_autologout.refresh',
        error: function (XMLHttpRequest, textStatus) {
          // Disable error reporting to the screen.
        }
      });

      function refresh() {
        Drupal.Ajax['os2web_nemlogin_autologout.refresh'].autologoutRefresh(init);
      }

      // Check if the page was loaded via a back button click.
      var $dirty_bit = $('#os2web-nemlogin-autologout-cache-check-bit');
      if ($dirty_bit.length !== 0) {
        if ($dirty_bit.val() === '1') {
          // Page was loaded via back button click, we should refresh the timer.
          refresh();
        }

        $dirty_bit.val('1');
      }
    }
  };

})(jQuery, Drupal, window.Cookies);
