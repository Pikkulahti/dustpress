<?php

/**
 * Output the login page header.
 *
 * @param string   $title    Optional. WordPress login Page title to display in the `<title>` element.
 *                           Default 'Log In'.
 * @param string   $message  Optional. Message to display in header. Default empty.
 * @param WP_Error $wp_error Optional. The error to pass. Default empty.
 */

public function login_header( $title = 'Log In', $message = '', $wp_error = '' ) {
    global $error, $interim_login, $action;

    // Don't index any of these forms
    add_action( 'login_head', 'wp_no_robots' );

    add_action( 'login_head', 'wp_login_viewport_meta' );

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

    if ( $shake_error_codes && $wp_error->get_error_code() && in_array( $wp_error->get_error_code(), $shake_error_codes ) )
        add_action( 'login_head', 'wp_shake_js', 12 );

    $separator = is_rtl() ? ' &rsaquo; ' : ' &lsaquo; ';


    wp_enqueue_style( 'login' );

    /*
     * Remove all stored post data on logging out.
     * This could be added by add_action('login_head'...) like wp_shake_js(),
     * but maybe better if it's not removable by plugins
     */
    if ( 'loggedout' == $wp_error->get_error_code() ) {
        // custom
        $status = 'loggedout';
    }

    /**
     * Enqueue scripts and styles for the login page.
     */
    
    ob_start();

    do_action( 'login_enqueue_scripts' );

    $print->header->login_enqueue_scripts = ob_get_clean();
    
    /**
     * Fires in the login page header after scripts are enqueued.
     */
    
    ob_start();

    do_action( 'login_head' );

    $print->header->login_head = ob_get_clean();
    
    

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
        //custom
        $status = $interim_login;

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

    $print->header->classes = esc_attr( implode( ' ', $classes ) );
    /**
     * Fires in the login page header after the body tag is opened.
     */
    
    ob_start();

    do_action( 'login_header' );

    $print->header->login_header_output = ob_get_clean();

    $print->header->login_url = esc_url( $login_header_url );
    $print->header->login_title = esc_attr( $login_header_title );

    unset( $login_header_url, $login_header_title );

    /**
     * Filters the message to display above the login form.
     *
     * @param string $message Login message text.
     */
    $message = apply_filters( 'login_message', $message );

    if ( !empty( $message ) )
        $print->header->$message;
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
            $print->header->errors = apply_filters( 'login_errors', $errors );
            $print->header->errors_array = $errors_array;
        }
        if ( ! empty( $messages ) ) {
            /**
             * Filters instructional messages displayed above the login form.

             * @param string $messages Login messages.
             */
            $print->header->messages = apply_filters( 'login_messages', $messages );
            $print->header->messages_array = $messages_array;
        }
    }
    return $print->header;
}


/**
 * Outputs the footer for the login page.
 *
 * @param string $input_id Which input to auto-focus
 */
function login_footer($input_id = '') {
    global $interim_login;

    // Don't allow interim logins to navigate away from the page.
    if ( ! $interim_login ): ?>
    <p id="backtoblog"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php
        /* translators: %s: site title */
        printf( _x( '&larr; Back to %s', 'site' ), get_bloginfo( 'title', 'display' ) );
    ?></a></p>
    <?php endif; ?>

    </div>

    <?php if ( !empty($input_id) ) : ?>
    <script type="text/javascript">
    try{document.getElementById('<?php echo $input_id; ?>').focus();}catch(e){}
    if(typeof wpOnload=='function')wpOnload();
    </script>
    <?php endif; ?>

    <?php
    /**
     * Fires in the login page footer.
     *
     * @since 3.1.0
     */
    do_action( 'login_footer' ); ?>
    <div class="clear"></div>
    </body>
    </html>
    <?php
}
