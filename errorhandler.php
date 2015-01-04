<?php
/**
 * Errorhandler class for droidwiki.de
 */
class DroidWikiErrorHandler {
	/** @var string $logFile Errors are logged to this file */
	private $logFile = null;
	/** @var string $detailedLog Location of detailed error logs */
	private $detailedLog = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wgPHPLogFilePref;

		// enable logging for all errors, but do not display them
		ini_set( "log_errors", E_ALL );
		ini_set( "display_errors", "Off" );

		// get filename
		if ( $wgPHPLogFilePref ) {
			$name_pre = $wgPHPLogFilePref;
		} elseif ( $_SERVER['HTTP_HOST'] ) {
			$name_pre = $_SERVER['HTTP_HOST'];
		} else {
			$name_pre = 'undefined_';
		}

		$this->logFile = "/var/www/web20/html/phplog/{$name_pre}php.errors.log";
		// set error log
		ini_set( "error_log", $this->logFile );

		// set error handlers
		set_error_handler( array( $this, 'handleError' ) );
		register_shutdown_function( array( $this, 'handleShutdown' ) );
	}

	/**
	 * This function handles fatal errors for DroidWiki
	 */
	public function handleError( $errtype, $errtext, $errfile, $errline ) {
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

	/**
	 * Handles php shutdown and catches errors, if there are any
	 */
	public function handleShutdown() {
		$last_error = error_get_last();
		if ( !empty( $last_error ) ) {
			$this->handleError(
				$last_error['type'],
				$last_error['message'],
				$last_error['file'],
				$last_error['line']
			);
		}
	}

	/**
	 * Catch an error and logs it to the logfile
	 */
	public function catchError( $errtype, $errtext, $errfile, $errline, $additionalInfo = false ) {
		$date = date( 'd-M-Y H:i:s' );
		$errorLine = "[" .
			$date .
			"] " .
			$this->FriendlyErrorType( $errtype ) .
			" " .
			$errtext .
			" in " .
			$errfile .
			" on line " .
			$errline .
			"\n";

		if ( !$handle = fopen( $this->logFile, "a" ) ) {
			return false;
		}

		if ( !fwrite( $handle, $errorLine ) ) {
			return false;
		}
		fclose( $handle );

		if ( $additionalInfo ) {
			$this->detailedLog = "/var/www/web20/html/phplog/details/{$errline}_{$date}.txt";
			// Collecting additional info and save it separately

			// text to write
			$text = "";

			// repeat the errorline from logFile
			$text .= $errorLine;
			// include stacktrace
			$text .= $this->getStackTrace() . "\n\n";
			// get requested URL
			$text .= "URL: " . $_SERVER["REQUEST_URI"] . "\n";
			// client ip is helpful, sometimes
			$text .= "Request IP: " . $_SERVER["REMOTE_ADDR"] . "\n";

			// write it down
			$file = fopen( $this->detailedLog, "a" );
			fwrite( $file, $text );
			fclose( $file );
		}
		return true;
	}

	private function getStackTrace() {
		$trace = '';
		foreach (debug_backtrace() as $k => $v) {
			if ( $k < $ignore ) {
				continue;
			}

			array_walk( $v['args'], function ( &$item, $key ) {
				$item = var_export( $item, true );
			});

			$trace .= '#' .
				($k - $ignore) .
				' ' .
				$v['file'] .
				'(' . $v['line'] . '): ' .
				( isset($v['class']) ? $v['class'] . '->' : '' ) .
				$v['function'] .
				'(' . implode(', ', $v['args']) . ')' .
				"\n";
		}

		return $trace;
	}

	public function FriendlyErrorType( $type ) {
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
}

$handler = new DroidWikiErrorHandler;
