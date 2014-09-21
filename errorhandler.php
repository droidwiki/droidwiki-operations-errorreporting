<?php
	// log errors into file
	ini_set( "log_errors", E_ALL );
	ini_set( "display_errors", "Off" );
	ini_set( "error_log", "/var/www/web20/html/specialsources/log/php.errors.log" );

	// set our handlers
	set_error_handler( 'handleError' );
	register_shutdown_function( 'shutdownHandler' );
	/**
	 * This function handles fatal errors for DroidWiki
	 */
	function handleError( $errtype, $errtext, $errfile, $errline ) {
		$include = array( 1, 4, 16, 256 );
		$excluded_script = array( '/profileinfo.php' );
		if (
			in_array( $errtype, $include ) &&
			!in_array( $_SERVER['SCRIPT_NAME'], $excluded_script )
		) {
			// erase last output
			ob_clean();
			// show our error page
			include 'php-fatal-error.template';
			die();
		}
	}

	function FriendlyErrorType( $type ) {
		switch( $type ) {
			case E_ERROR: // 1 //
				return 'Fatal Error';
			case E_WARNING: // 2 //
				return 'E_WARNING';
			case E_PARSE: // 4 //
				return 'Parse Error';
			case E_NOTICE: // 8 //
				return 'E_NOTICE';
			case E_CORE_ERROR: // 16 //
				return 'E_CORE_ERROR';
			case E_CORE_WARNING: // 32 //
				return 'E_CORE_WARNING';
			case E_CORE_ERROR: // 64 //
				return 'E_COMPILE_ERROR';
			case E_CORE_WARNING: // 128 //
				return 'E_COMPILE_WARNING';
			case E_USER_ERROR: // 256 //
				return 'E_USER_ERROR';
			case E_USER_WARNING: // 512 //
				return 'E_USER_WARNING';
			case E_USER_NOTICE: // 1024 //
				return 'E_USER_NOTICE';
			case E_STRICT: // 2048 //
				return 'E_STRICT';
			case E_RECOVERABLE_ERROR: // 4096 //
				return 'E_RECOVERABLE_ERROR';
			case E_DEPRECATED: // 8192 //
				return 'E_DEPRECATED';
			case E_USER_DEPRECATED: // 16384 //
				return 'E_USER_DEPRECATED';
		}
		return "fatal";
	}

	function shutdownHandler() {
		$last_error = error_get_last();
		if ( !empty( $last_error ) ) {
			handleError(
				$last_error['type'],
				$last_error['message'],
				$last_error['file'],
				$last_error['line']
			);
		}
	}
