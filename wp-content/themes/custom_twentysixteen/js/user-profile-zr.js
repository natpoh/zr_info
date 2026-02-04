/**
 * @output wp-admin/js/user-profile.js
 */

/* global ajaxurl, pwsL10n, userProfileL10n */
(function ($) {
    var updateLock = false,
            __ = wp.i18n.__,
            $pass1Row,
            $pass1,
            $pass2,
            $weakRow,
            $weakCheckbox,
            $toggleButton,
            $submitButtons,
            $submitButton,
            currentPass,
            $passwordWrapper,
            $clearButton;

    function generatePasswordZr() {
        if (typeof zxcvbn !== 'function') {
            setTimeout(generatePasswordZr, 50);
            return;
        } else if (!$pass1.val() || $passwordWrapper.hasClass('is-open')) {
            // zxcvbn loaded before user entered password, or generating new password.
            $pass1.val($pass1.data('pw'));
            $pass1.trigger('pwupdate');
            showOrHideWeakPasswordCheckbox();
        } else {
            // zxcvbn loaded after the user entered password, check strength.
            check_pass_strength();
            showOrHideWeakPasswordCheckbox();
        }

        /*
         * This works around a race condition when zxcvbn loads quickly and
         * causes `generatePasswordZr()` to run prior to the toggle button being
         * bound.
         */
        bindToggleButton();
        bindClearButton();

        // Install screen.
        if (1 !== parseInt($toggleButton.data('start-masked'), 10)) {
            // Show the password not masked if admin_password hasn't been posted yet.
            $pass1.attr('type', 'text');
        } else {
            // Otherwise, mask the password.
            $toggleButton.trigger('click');
        }

        // Once zxcvbn loads, passwords strength is known.
        $('#pw-weak-text-label').text(__('Confirm use of weak password'));

        // Focus the password field.
        if ('mailserver_pass' !== $pass1.prop('id')) {
            $($pass1).trigger('focus');
        }
    }

    function bindPass1() {
        currentPass = $pass1.val();

        if (1 === parseInt($pass1.data('reveal'), 10)) {
            generatePasswordZr();
        }

        $pass1.on('input' + ' pwupdate', function () {
            if ($pass1.val() === currentPass) {
                return;
            }

            currentPass = $pass1.val();

            // Refresh password strength area.
            $pass1.removeClass('short bad good strong');
            showOrHideWeakPasswordCheckbox();
        });
    }

    function resetToggle(show) {
        $toggleButton
                .attr({
                    'aria-label': show ? __('Show password') : __('Hide password')
                })
                .find('.text')
                .text(show ? __('Show') : __('Hide'))
                .end()
                .find('.dashicons')
                .removeClass(show ? 'dashicons-hidden' : 'dashicons-visibility')
                .addClass(show ? 'dashicons-visibility' : 'dashicons-hidden');
    }

    function bindToggleButton() {
        if (!!$toggleButton) {
            // Do not rebind.
            return;
        }
        $toggleButton = $pass1Row.find('.wp-hide-pw-zr');
        $toggleButton.show().on('click', function () {
            if ('password' === $pass1.attr('type')) {
                $pass1.attr('type', 'text');
                resetToggle(false);
            } else {
                $pass1.attr('type', 'password');
                resetToggle(true);
            }
        });
    }

    function bindClearButton() {
        if (!!$clearButton) {
            // Do not rebind.
            return;
        }
        $clearButton = $pass1Row.find('.wp-clear-pw-zr');
        $clearButton.show().on('click', function () {
            console.log('click');
            $pass1 = $('#pass1');
            $pass1.val('');

            check_pass_strength();
            // Refresh password strength area.
            $pass1.removeClass('short bad good strong');
            showOrHideWeakPasswordCheckbox();
        });
    }

    /**
     * Handle the password reset button. Sets up an ajax callback to trigger sending
     * a password reset email.
     */
    function bindPasswordResetLink() {
        $('#generate-reset-link').on('click', function () {
            var $this = $(this),
                    data = {
                        'user_id': userProfileL10n.user_id, // The user to send a reset to.
                        'nonce': userProfileL10n.nonce    // Nonce to validate the action.
                    };

            // Remove any previous error messages.
            $this.parent().find('.notice-error').remove();

            // Send the reset request.
            var resetAction = wp.ajax.post('send-password-reset', data);

            // Handle reset success.
            resetAction.done(function (response) {
                addInlineNotice($this, true, response);
            });

            // Handle reset failure.
            resetAction.fail(function (response) {
                addInlineNotice($this, false, response);
            });

        });

    }

    /**
     * Helper function to insert an inline notice of success or failure.
     *
     * @param {jQuery Object} $this   The button element: the message will be inserted
     *                                above this button
     * @param {bool}          success Whether the message is a success message.
     * @param {string}        message The message to insert.
     */
    function addInlineNotice($this, success, message) {
        var resultDiv = $('<div />');

        // Set up the notice div.
        resultDiv.addClass('notice inline');

        // Add a class indicating success or failure.
        resultDiv.addClass('notice-' + (success ? 'success' : 'error'));

        // Add the message, wrapping in a p tag, with a fadein to highlight each message.
        resultDiv.text($($.parseHTML(message)).text()).wrapInner('<p />');

        // Disable the button when the callback has succeeded.
        $this.prop('disabled', success);

        // Remove any previous notices.
        $this.siblings('.notice').remove();

        // Insert the notice.
        $this.before(resultDiv);
    }

    function bindPasswordForm() {
        var $generateButton,
                $cancelButton;

        $pass1Row = $('.user-pass1-wrap, .user-pass-wrap, .mailserver-pass-wrap, .reset-pass-submit');

        // Hide the confirm password field when JavaScript support is enabled.
        $('.user-pass2-wrap').hide();

        $submitButton = $('#submit, #wp-submit').on('click', function () {
            updateLock = false;
        });

        $submitButtons = $submitButton.add(' #createusersub');

        $weakRow = $('.pw-weak');
        $weakCheckbox = $weakRow.find('.pw-checkbox');
        $weakCheckbox.on('change', function () {
            $submitButtons.prop('disabled', !$weakCheckbox.prop('checked'));
        });

        $pass1 = $('#pass1, #mailserver_pass');
        if ($pass1.length) {
            bindPass1();
        } else {
            // Password field for the login form.
            $pass1 = $('#user_pass');
        }

        /*
         * Fix a LastPass mismatch issue, LastPass only changes pass2.
         *
         * This fixes the issue by copying any changes from the hidden
         * pass2 field to the pass1 field, then running check_pass_strength.
         */
        $pass2 = $('#pass2').on('input', function () {
            if ($pass2.val().length > 0) {
                $pass1.val($pass2.val());
                $pass2.val('');
                currentPass = '';
                $pass1.trigger('pwupdate');
            }
        });

        // Disable hidden inputs to prevent autofill and submission.
        if ($pass1.is(':hidden')) {
            $pass1.prop('disabled', true);
            $pass2.prop('disabled', true);
        }

        $passwordWrapper = $pass1Row.find('.wp-pwd-zr');
        $generateButton = $pass1Row.find('button.wp-generate-pw-zr');

        bindToggleButton();
        bindClearButton();

        $generateButton.show();

        $generateButton.on('click', function () {

            updateLock = true;

            // Make sure the password fields are shown.
            $generateButton.not('.skip-aria-expanded').attr('aria-expanded', 'true');
            $passwordWrapper
                    .show()
                    .addClass('is-open');

            // Enable the inputs when showing.
            $pass1.attr('disabled', false);
            $pass2.attr('disabled', false);

            // Set the password to the generated value.
            generatePasswordZr();

            // Show generated password in plaintext by default.
            resetToggle(false);

            // Generate the next password and cache.
            wp.ajax.post('generate-password')
                    .done(function (data) {
                        $pass1.data('pw', data);
                    });
        });

        $pass1Row.closest('form').on('submit', function () {
            updateLock = false;
            $pass1.prop('disabled', false);
            $pass2.prop('disabled', false);
            $pass2.val($pass1.val());
        });
    }

    function check_pass_strength() {
        var pass1 = $('#pass1').val(), strength;

        $('#pass-strength-result').removeClass('short bad good strong empty');
        if (!pass1 || '' === pass1.trim()) {
            $('#pass-strength-result').addClass('empty').html('&nbsp;');
            return;
        }

        strength = wp.passwordStrength.meter(pass1, wp.passwordStrength.userInputDisallowedList(), pass1);

        switch (strength) {
            case - 1:
                $('#pass-strength-result').addClass('bad').html(pwsL10n.unknown);
                break;
            case 2:
                $('#pass-strength-result').addClass('bad').html(pwsL10n.bad);
                break;
            case 3:
                $('#pass-strength-result').addClass('good').html(pwsL10n.good);
                break;
            case 4:
                $('#pass-strength-result').addClass('strong').html(pwsL10n.strong);
                break;
            case 5:
                $('#pass-strength-result').addClass('short').html(pwsL10n.mismatch);
                break;
            default:
                $('#pass-strength-result').addClass('short').html(pwsL10n['short']);
        }
    }

    function showOrHideWeakPasswordCheckbox() {
        var passStrengthResult = $('#pass-strength-result');

        if (passStrengthResult.length) {
            var passStrength = passStrengthResult[0];

            if (passStrength.className) {
                $pass1.addClass(passStrength.className);
                if ($(passStrength).is('.short, .bad')) {
                    if (!$weakCheckbox.prop('checked')) {
                        $submitButtons.prop('disabled', true);
                    }
                    $weakRow.show();
                } else {
                    if ($(passStrength).is('.empty')) {
                        $submitButtons.prop('disabled', true);
                        $weakCheckbox.prop('checked', false);
                    } else {
                        $submitButtons.prop('disabled', false);
                    }
                    $weakRow.hide();
                }
            }
        }
        if ($('#registerform').find('input.invalid').length != 0) {
            $('#wp-submit').prop('disabled', true);
        }
    }

    $(function () {

        $('#pass1').val('').on('input' + ' pwupdate', check_pass_strength);
        //$('#pass-strength-result').show();

        bindPasswordForm();
        bindPasswordResetLink();
    });



    window.generatePasswordZr = generatePasswordZr;

    // Warn the user if password was generated but not saved.
    $(window).on('beforeunload', function () {
        if (true === updateLock) {
            return __('Your new password has not been saved.');
        }
    });

    /*
     * We need to generate a password as soon as the Reset Password page is loaded,
     * to avoid double clicking the button to retrieve the first generated password.
     * See ticket #39638.
     */
    /*$( function() {
     if ( $( '.reset-pass-submit' ).length ) {
     $( '.reset-pass-submit button.wp-generate-pw-zr' ).trigger( 'click' );
     }
     });*/

    $(document).ready(function ($) {
        $('#user_login, #user_email').keyup(function () {
            var $this = $(this);
            $this.removeClass('valid invalid');
            var name = $this.attr('name');
            $('#login_ajax_error_' + name).remove();
            $('#login_error').remove();
            if ($('#registerform').find('input.invalid').length == 0) {
                $('#wp-submit').prop('disabled', false);
            }
        });

        $('#user_login, #user_email').on('blur', function () {
            var $this = $(this);
            var user_field = $this.val();
            var name = $this.attr('name');

            $('#login_ajax_error_' + name).remove();
            $('#login_error').remove();
            if ($('#registerform').find('input.invalid').length == 0) {
                $('#wp-submit').prop('disabled', false);
            }

            $.ajax({
                type: 'POST',
                url: "/wp-admin/admin-ajax.php",
                data: {
                    action: 'zr_check_username',
                    user_field: user_field,
                    name: name
                },
                success: function (response) {
                    var json_resp = JSON.parse(response);
                    if (json_resp['valid'] === 1) {
                        $this.removeClass('invalid');
                        $this.addClass('valid');
                        if ($('#registerform').find('input.invalid').length == 0) {
                            $('#wp-submit').prop('disabled', false);
                        }
                    } else {
                        $this.removeClass('valid');
                        $this.addClass('invalid');
                        $('#wp-submit').prop('disabled', true);
                        $('#registerform').before('<div id="login_ajax_error_' + name + '" class="notice notice-error">' + json_resp.err + '</div>');
                    }
                }
            });
        });
    });

})(jQuery);
