<?php

// else if ( strpos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) !== false ) {
//  $this->login_page = true;
//  // Run create_instance for use partial and model
//  add_action( 'login_init', [ $this, 'create_instance' ] );
//  // Kill original wp-activate.php execution
//  add_action( 'login_init', function() { die(); } );
// }



/**
 * Output the login page header.
 *
 * @param string   $title    Optional. WordPress login Page title to display in the `<title>` element.
 *                           Default 'Log In'.
 * @param string   $message  Optional. Message to display in header. Default empty.
 * @param WP_Error $wp_error Optional. The error to pass. Default empty.
 */

private $state;

public function login_header( $title = 'Log In', $message = '', $wp_error = '' ) {
    global $error, $interim_login, $action;

    $state->interim_login = $interim_login;

    // Don't index any of these forms
    add_action( 'login_head', 'wp_no_robots' );


    if ( empty($wp_error) )
        $wp_error = new WP_Error();

    // Shake it!
    $shake_error_codes = array( 'empty_password', 'empty_email', 'invalid_email', 'invalidcombo', 'empty_username', 'invalid_username', 'incorrect_password' );
    /**
     * Filters the error codes array for shaking the login form.
     *
     * @since 3.0.0
     *
     * @param array $shake_error_codes Error codes that shake the login form.
     */
    $shake_error_codes = apply_filters( 'shake_error_codes', $shake_error_codes );

    if ( $shake_error_codes && $wp_error->get_error_code() && in_array( $wp_error->get_error_code(), $shake_error_codes ) ) {
        $state->shakejs = 'shakeit!'
    }

    $separator = is_rtl() ? ' &rsaquo; ' : ' &lsaquo; ';


    wp_enqueue_style( 'login' );

    /*
     * Remove all stored post data on logging out.
     * This could be added by add_action('login_head'...) like wp_shake_js(),
     * but maybe better if it's not removable by plugins
     */
    
    $state->error = $wp_error->get_error_code();

    /**
     * Enqueue scripts and styles for the login page.
     */
    
    ob_start();

    do_action( 'login_enqueue_scripts' );

    $print->login_enqueue_scripts_output = ob_get_clean();
    
    /**
     * Fires in the login page header after scripts are enqueued.
     */
    
    ob_start();

    do_action( 'login_head' );

    $print->login_head_output = ob_get_clean();
    
    

    if (is_multisite() ) {
        $login_header_url   = network_home_url();
        $login_header_title = get_network()->site_name;
    } else {
        $login_header_url   = __( 'https://wordpress.org/' );
        $login_header_title = __( 'Powered by WordPress' );
    }

    /**
     * Filters link URL of the header logo above login form.
     *
     * @since 2.1.0
     *
     * @param string $login_header_url Login header logo URL.
     */
    $login_header_url = apply_filters( 'login_headerurl', $login_header_url );

    /**
     * Filters the title attribute of the header logo above login form.
     *
     * @since 2.1.0
     *
     * @param string $login_header_title Login header logo title attribute.
     */
    $login_header_title = apply_filters( 'login_headertitle', $login_header_title );

    $classes = array( 'login-action-' . $action, 'wp-core-ui' );
    if ( is_rtl() )
        $classes[] = 'rtl';
    if ( $interim_login ) {
        $classes[] = 'interim-login';
        if ( 'success' ===  $interim_login )
            $classes[] = 'interim-login-success';
    }
    $classes[] =' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_locale() ) ) );
    /**
     * Filters the login page body classes.
     *
     * @param array  $classes An array of body classes.
     * @param string $action  The action that brought the visitor to the login page.
     */
    $classes = apply_filters( 'login_body_class', $classes, $action );

    $print->classes = esc_attr( implode( ' ', $classes ) );
    /**
     * Fires in the login page header after the body tag is opened.
     */
    
    ob_start();

    do_action( 'login_header' );

    $print->login_header_output = ob_get_clean();

    $print->login_url = esc_url( $login_header_url );
    $print->login_title = esc_attr( $login_header_title );

    unset( $login_header_url, $login_header_title );

    /**
     * Filters the message to display above the login form.
     *
     * @param string $message Login message text.
     */
    $message = apply_filters( 'login_message', $message );

    if ( !empty( $message ) )
        $print->$message;
    // In case a plugin uses $error rather than the $wp_errors object
    if ( !empty( $error ) ) {
        $wp_error->add('error', $error);
        unset($error);
    }

    if ( $wp_error->get_error_code() ) {
        $errors = '';
        $errors_array = [];
        $messages = '';
        $messages_array = [];
        foreach ( $wp_error->get_error_codes() as $code ) {
            $severity = $wp_error->get_error_data( $code );
            foreach ( $wp_error->get_error_messages( $code ) as $error_message ) {
                if ( 'message' == $severity ) {
                    $messages .= '  ' . $error_message . "<br />\n";
                    $messages_array[] = $error_message;
                } 
                else {
                    $errors .= '    ' . $error_message . "<br />\n";
                    $errors_array[] = $error_message;
                }
            }
        }
        if ( ! empty( $errors ) ) {
            /**
             * Filters the error messages displayed above the login form.

             * @param string $errors Login error message.
             */
            $print->errors = apply_filters( 'login_errors', $errors );
            $print->errors_array = $errors_array;
        }
        if ( ! empty( $messages ) ) {
            /**
             * Filters instructional messages displayed above the login form.

             * @param string $messages Login messages.
             */
            $print->messages = apply_filters( 'login_messages', $messages );
            $print->messages_array = $messages_array;
        }
    }
    
    $this->state = $state;
    return $print;
}


/**
 * Outputs the footer for the login page.
 *
 * @param string $input_id Which input to auto-focus
 */
public function login_footer($input_id = '') {
    global $interim_login;

    // Don't allow interim logins to navigate away from the page.
    if (! $interim_login ) {
        /* translators: %s: site title */
        $print->backtoblog = sprintf(_x('&larr; Back to %s', 'site'), get_bloginfo('title', 'display'));
    }

    $print->js->input_id = $input_id;
    /**
     * Fires in the login page footer.
     *
     * @since 3.1.0
     */
    
    ob_start();

    do_action('login_footer');

    $print->login_footer_output = ob_get_clean();

    return $print;

}

/**
 * Handles sending password retrieval email to user.
 *
 * @return bool|WP_Error True: when finish. WP_Error on error
 */
private function retrieve_password() {
    $errors = new WP_Error();

    if ( empty( $_POST['user_login'] ) ) {
        $errors->add('empty_username', __('<strong>ERROR</strong>: Enter a username or email address.'));
    } elseif ( strpos( $_POST['user_login'], '@' ) ) {
        $user_data = get_user_by( 'email', trim( wp_unslash( $_POST['user_login'] ) ) );
        if ( empty( $user_data ) )
            $errors->add('invalid_email', __('<strong>ERROR</strong>: There is no user registered with that email address.'));
    } else {
        $login = trim($_POST['user_login']);
        $user_data = get_user_by('login', $login);
    }

    /**
     * Fires before errors are returned from a password reset request.
     *
     * @since 2.1.0
     * @since 4.4.0 Added the `$errors` parameter.
     *
     * @param WP_Error $errors A WP_Error object containing any errors generated
     *                         by using invalid credentials.
     */
    
    do_action( 'lostpassword_post', $errors );

    if ( $errors->get_error_code() )
        return $errors;

    if ( !$user_data ) {
        $errors->add('invalidcombo', __('<strong>ERROR</strong>: Invalid username or email.'));
        return $errors;
    }

    // Redefining user_login ensures we return the right case in the email.
    $user_login = $user_data->user_login;
    $user_email = $user_data->user_email;
    $key = get_password_reset_key( $user_data );

    if ( is_wp_error( $key ) ) {
        return $key;
    }

    $message = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";
    $message .= network_home_url( '/' ) . "\r\n\r\n";
    $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
    $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
    $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
    $message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";

    if ( is_multisite() ) {
        $blogname = get_network()->site_name;
    } else {
        /*
         * The blogname option is escaped with esc_html on the way into the database
         * in sanitize_option we want to reverse this for the plain text arena of emails.
         */
        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    }

    /* translators: Password reset email subject. 1: Site name */
    $title = sprintf( __('[%s] Password Reset'), $blogname );

    /**
     * Filters the subject of the password reset email.
     *
     * @since 2.8.0
     * @since 4.4.0 Added the `$user_login` and `$user_data` parameters.
     *
     * @param string  $title      Default email title.
     * @param string  $user_login The username for the user.
     * @param WP_User $user_data  WP_User object.
     */
    $title = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );

    /**
     * Filters the message body of the password reset mail.
     * 
     * If the filtered message is empty, the password reset email will not be sent.
     *
     * @since 2.8.0
     * @since 4.1.0 Added `$user_login` and `$user_data` parameters.
     *
     * @param string  $message    Default mail message.
     * @param string  $key        The activation key.
     * @param string  $user_login The username for the user.
     * @param WP_User $user_data  WP_User object.
     */
    $message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );

    if ( $message && !wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) )
        wp_die( __('The email could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.') );

    return true;
}

public function main_login() {
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'login';
    $errors = new WP_Error();

    if ( isset($_GET['key']) )
        $action = 'resetpass';

    // validate action so as to default to the login screen
    if (! in_array( $action, array( 'postpass', 'logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'register', 'login' ), true ) && false === has_filter( 'login_form_' . $action ) )
        $action = 'login';

    nocache_headers();

    header('Content-Type: '.get_bloginfo('html_type').'; charset='.get_bloginfo('charset'));

    if ( defined( 'RELOCATE' ) && RELOCATE ) { // Move flag is set
        if ( isset( $_SERVER['PATH_INFO'] ) && ($_SERVER['PATH_INFO'] != $_SERVER['PHP_SELF']) )
            $_SERVER['PHP_SELF'] = str_replace( $_SERVER['PATH_INFO'], '', $_SERVER['PHP_SELF'] );

        $url = dirname( set_url_scheme( 'http://' .  $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] ) );
        if ( $url != get_option( 'siteurl' ) )
            update_option( 'siteurl', $url );
    }

    //Set a cookie now to see if they are supported by the browser.
    $secure = ( 'https' === parse_url( wp_login_url(), PHP_URL_SCHEME ) );
    setcookie( TEST_COOKIE, 'WP Cookie check', 0, COOKIEPATH, COOKIE_DOMAIN, $secure );
    if ( SITECOOKIEPATH != COOKIEPATH )
        setcookie( TEST_COOKIE, 'WP Cookie check', 0, SITECOOKIEPATH, COOKIE_DOMAIN, $secure );

    /**
     * Fires when the login form is initialized.
     *
     * @since 3.2.0
     */
    
    ob_start();

    do_action( 'login_init' );


    $print->login_init_output = ob_get_clean();

    /**
     * Fires before a specified login form action.
     *
     * The dynamic portion of the hook name, `$action`, refers to the action
     * that brought the visitor to the login form. Actions include 'postpass',
     * 'logout', 'lostpassword', etc.
     *
     * @since 2.8.0
     */
    do_action( "login_form_{$action}" );

    $this->login_state_$action();

    $http_post = ('POST' == $_SERVER['REQUEST_METHOD']);
    $interim_login = isset($_REQUEST['interim-login']);



    return $print;
}

protected function login_state_postpass() {
    if ( ! array_key_exists( 'post_password', $_POST ) ) {
        wp_safe_redirect( wp_get_referer() );
        exit();
    }

    $hasher = new PasswordHash( 8, true );

    /**
     * Filters the life span of the post password cookie.
     *
     * By default, the cookie expires 10 days from creation. To turn this
     * into a session cookie, return 0.
     *
     * @since 3.7.0
     *
     * @param int $expires The expiry time, as passed to setcookie().
     */
    $expire = apply_filters( 'post_password_expires', time() + 10 * DAY_IN_SECONDS );
    $referer = wp_get_referer();
    if ( $referer ) {
        $secure = ( 'https' === parse_url( $referer, PHP_URL_SCHEME ) );
    } else {
        $secure = false;
    }
    setcookie( 'wp-postpass_' . COOKIEHASH, $hasher->HashPassword( wp_unslash( $_POST['post_password'] ) ), $expire, COOKIEPATH, COOKIE_DOMAIN, $secure );

    wp_safe_redirect( wp_get_referer() );
    exit();
}


protected function login_state_logout() {
    check_admin_referer('log-out');

    $user = wp_get_current_user();

    wp_logout();

    if ( ! empty( $_REQUEST['redirect_to'] ) ) {
        $redirect_to = $requested_redirect_to = $_REQUEST['redirect_to'];
    } else {
        $redirect_to = 'wp-login.php?loggedout=true';
        $requested_redirect_to = '';
    }

    /**
     * Filters the log out redirect URL.
     *
     * @since 4.2.0
     *
     * @param string  $redirect_to           The redirect destination URL.
     * @param string  $requested_redirect_to The requested redirect destination URL passed as a parameter.
     * @param WP_User $user                  The WP_User object for the user that's logging out.
     */
    $redirect_to = apply_filters( 'logout_redirect', $redirect_to, $requested_redirect_to, $user );
    wp_safe_redirect( $redirect_to );
    exit();
}

protected function login_state_password() {
    if ( $http_post ) {
		$errors = retrieve_password();
		if ( !is_wp_error($errors) ) {
			$redirect_to = !empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : 'wp-login.php?checkemail=confirm';
			wp_safe_redirect( $redirect_to );
			exit();
		}
	}

	if ( isset( $_GET['error'] ) ) {
		if ( 'invalidkey' == $_GET['error'] ) {
			$errors->add( 'invalidkey', __( 'Your password reset link appears to be invalid. Please request a new link below.' ) );
		} elseif ( 'expiredkey' == $_GET['error'] ) {
			$errors->add( 'expiredkey', __( 'Your password reset link has expired. Please request a new link below.' ) );
		}
	}

	$lostpassword_redirect = ! empty( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : '';
	/**
	 * Filters the URL redirected to after submitting the lostpassword/retrievepassword form.
	 *
	 * @since 3.0.0
	 *
	 * @param string $lostpassword_redirect The redirect destination URL.
	 */
	$redirect_to = apply_filters( 'lostpassword_redirect', $lostpassword_redirect );

	/**
	 * Fires before the lost password form.
	 *
	 * @since 1.5.1
	 */

    ob_start();

    do_action( 'lost_password' );

    $print->lost_password_output = ob_get_clean();
	
	// init login header

    $login_header->title = __('Lost Password');
    $login_header->message = '<p class="message">' . __('Please enter your username or email address. You will receive a link to create a new password via email.') . '</p>';
    $login_hader->wp_error = $errors;
    

	$user_login = isset($_POST['user_login']) ? wp_unslash($_POST['user_login']) : '';


    $lost_password_action = esc_url( network_site_url( 'wp-login.php?action=lostpassword', 'login_post' ) );
    $esc_user_login = esc_attr($user_login);

        ob_start();

    do_action( 'lostpassword_form' );

    $print->lostpassword_form_output = ob_get_clean();

	
	<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Get New Password'); ?>" /></p>
</form>

<p id="nav">
<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php _e('Log in') ?></a>
<?php
if ( get_option( 'users_can_register' ) ) :
	$registration_url = sprintf( '<a href="%s">%s</a>', esc_url( wp_registration_url() ), __( 'Register' ) );

	/** This filter is documented in wp-includes/general-template.php */
	echo ' | ' . apply_filters( 'register', $registration_url );
endif;
?>
</p>



    $print->js->input_id = 'user_login';

}