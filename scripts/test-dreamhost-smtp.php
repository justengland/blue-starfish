#!/usr/bin/env php
<?php
/**
 * Local DreamHost SMTP test (no WordPress bootstrap required).
 *
 * Verifies rentals@ (or another mailbox) can authenticate and optionally send mail.
 *
 * Usage:
 *   php scripts/test-dreamhost-smtp.php
 *   php scripts/test-dreamhost-smtp.php --verbose
 *   php scripts/test-dreamhost-smtp.php --password-file=.smtp-credentials
 *   SMTP_PASS='your-password' php scripts/test-dreamhost-smtp.php --to=you@gmail.com
 *
 * Password sources (first match wins):
 *   1. SMTP_PASS environment variable
 *   2. --password-file=PATH (default: .smtp-credentials in repo root)
 *   3. local-smtp.php if it defines WPMS_SMTP_PASS
 *
 * @package BlueStarfish
 */

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "Run from the command line only.\n" );
	exit( 1 );
}

$repo_root = dirname( __DIR__ );

require $repo_root . '/wp-includes/PHPMailer/Exception.php';
require $repo_root . '/wp-includes/PHPMailer/PHPMailer.php';
require $repo_root . '/wp-includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * @param string[] $argv CLI args.
 * @return array<string, mixed>
 */
function ob_smtp_parse_args( array $argv ) {
	$repo_root = dirname( __DIR__ );

	$opts = array(
		'host'          => getenv( 'SMTP_HOST' ) ?: 'smtp.dreamhost.com',
		'port'          => (int) ( getenv( 'SMTP_PORT' ) ?: 587 ),
		'secure'        => getenv( 'SMTP_SECURE' ) ?: 'tls',
		'user'          => getenv( 'SMTP_USER' ) ?: 'notification@bluestarfishguesthouse.com',
		'from'          => getenv( 'SMTP_FROM' ) ?: 'notification@bluestarfishguesthouse.com',
		'to'            => getenv( 'SMTP_TO' ) ?: 'rentals@bluestarfishguesthouse.com',
		'password_file' => $repo_root . '/.smtp-credentials',
		'verbose'       => false,
		'auth_only'     => false,
		'help'          => false,
	);

	foreach ( array_slice( $argv, 1 ) as $arg ) {
		if ( '--help' === $arg || '-h' === $arg ) {
			$opts['help'] = true;
		} elseif ( '--verbose' === $arg || '-v' === $arg ) {
			$opts['verbose'] = true;
		} elseif ( '--auth-only' === $arg ) {
			$opts['auth_only'] = true;
		} elseif ( str_starts_with( $arg, '--host=' ) ) {
			$opts['host'] = substr( $arg, 7 );
		} elseif ( str_starts_with( $arg, '--port=' ) ) {
			$opts['port'] = (int) substr( $arg, 7 );
		} elseif ( str_starts_with( $arg, '--secure=' ) ) {
			$opts['secure'] = substr( $arg, 9 );
		} elseif ( str_starts_with( $arg, '--user=' ) ) {
			$opts['user'] = substr( $arg, 7 );
		} elseif ( str_starts_with( $arg, '--from=' ) ) {
			$opts['from'] = substr( $arg, 7 );
		} elseif ( str_starts_with( $arg, '--to=' ) ) {
			$opts['to'] = substr( $arg, 5 );
		} elseif ( str_starts_with( $arg, '--password-file=' ) ) {
			$opts['password_file'] = substr( $arg, 16 );
		}
	}

	return $opts;
}

/**
 * @param array<string, mixed> $opts Options.
 * @return string
 */
function ob_smtp_load_password( array $opts ) {
	$env = getenv( 'SMTP_PASS' );
	if ( is_string( $env ) && '' !== $env ) {
		return $env;
	}

	$path = $opts['password_file'];
	if ( is_string( $path ) && is_readable( $path ) ) {
		$raw = file_get_contents( $path );
		if ( false !== $raw ) {
			return trim( $raw );
		}
	}

	$local_smtp = dirname( __DIR__ ) . '/local-smtp.php';
	if ( is_readable( $local_smtp ) ) {
		require $local_smtp;
		if ( defined( 'WPMS_SMTP_PASS' ) && WPMS_SMTP_PASS && 'your_mailbox_password_here' !== WPMS_SMTP_PASS ) {
			return (string) WPMS_SMTP_PASS;
		}
	}

	return '';
}

function ob_smtp_print_help() {
	$help = <<<'HELP'
DreamHost SMTP test for Blue Starfish

  php scripts/test-dreamhost-smtp.php [options]

Options:
  --verbose, -v       Show SMTP conversation (level 2)
  --auth-only         Connect + AUTH only; do not send a message
  --host=HOST         Default: smtp.dreamhost.com
  --port=PORT         Default: 587
  --secure=tls|ssl|''  Default: tls (use ssl with port 465)
  --user=EMAIL        Default: notification@bluestarfishguesthouse.com
  --from=EMAIL        Default: same as --user
  --to=EMAIL          Default: rentals@bluestarfishguesthouse.com
  --password-file=PATH  Default: .smtp-credentials in repo root

Environment:
  SMTP_PASS, SMTP_HOST, SMTP_PORT, SMTP_SECURE, SMTP_USER, SMTP_FROM, SMTP_TO

Examples:
  printf '%s' 'YOUR_PASS' > .smtp-credentials
  php scripts/test-dreamhost-smtp.php --verbose

  SMTP_PASS='YOUR_PASS' php scripts/test-dreamhost-smtp.php --verbose --to=justengland@gmail.com

  # Try SSL on 465 if TLS/587 fails:
  php scripts/test-dreamhost-smtp.php --port=465 --secure=ssl --verbose

HELP;
	fwrite( STDOUT, $help );
}

/**
 * @param string $label Step label.
 * @param callable $fn Test function.
 */
function ob_smtp_step( $label, callable $fn ) {
	fwrite( STDOUT, "\n=== {$label} ===\n" );
	try {
		$fn();
		fwrite( STDOUT, "OK: {$label}\n" );
	} catch ( Exception $e ) {
		fwrite( STDERR, "FAIL: {$label}\n" );
		fwrite( STDERR, $e->getMessage() . "\n" );
		if ( $e->errorInfo ) {
			fwrite( STDERR, "PHPMailer errorInfo: {$e->errorInfo}\n" );
		}
		exit( 1 );
	}
}

$opts     = ob_smtp_parse_args( $argv ?? array() );
$repo_root = dirname( __DIR__ );
$opts['password_file'] = str_starts_with( $opts['password_file'], '/' )
	? $opts['password_file']
	: $repo_root . '/' . ltrim( $opts['password_file'], '/' );

if ( ! empty( $opts['help'] ) ) {
	ob_smtp_print_help();
	exit( 0 );
}

$password = ob_smtp_load_password( $opts );

fwrite( STDOUT, "DreamHost SMTP test\n" );
fwrite( STDOUT, "  host:   {$opts['host']}:{$opts['port']} ({$opts['secure']})\n" );
fwrite( STDOUT, "  user:   {$opts['user']}\n" );
fwrite( STDOUT, "  from:   {$opts['from']}\n" );
fwrite( STDOUT, "  to:     {$opts['to']}\n" );
fwrite( STDOUT, '  pass:   ' . ( '' !== $password ? 'loaded (' . strlen( $password ) . " chars)\n" : "MISSING — set SMTP_PASS or .smtp-credentials\n" ) );

if ( '' === $password ) {
	fwrite( STDERR, "\nNo password found. Create .smtp-credentials in the repo root:\n" );
	fwrite( STDERR, "  printf '%s' 'YOUR_PASSWORD' > .smtp-credentials\n" );
	exit( 1 );
}

// Flag common copy/paste issues without printing the password.
if ( preg_match( '/[\r\n]/', $password ) ) {
	fwrite( STDERR, "WARN: password contains a newline — use printf, not echo.\n" );
}
if ( $password !== trim( $password ) ) {
	fwrite( STDERR, "WARN: password has leading/trailing whitespace (will use trimmed value).\n" );
	$password = trim( $password );
}

$mail = new PHPMailer( true );
$mail->isSMTP();
$mail->Host       = $opts['host'];
$mail->Port       = $opts['port'];
$mail->SMTPAuth   = true;
$mail->Username   = $opts['user'];
$mail->Password   = $password;
$mail->SMTPSecure = $opts['secure'] ?: PHPMailer::ENCRYPTION_STARTTLS;
$mail->SMTPAutoTLS = true;
$mail->Timeout    = 30;

if ( $opts['verbose'] ) {
	$mail->SMTPDebug = 2;
	$mail->Debugoutput = static function ( $str, $level ) {
		fwrite( STDOUT, "[smtp {$level}] {$str}" );
	};
}

ob_smtp_step(
	'SMTP connect + authenticate',
	static function () use ( $mail, $opts ) {
		if ( ! $mail->smtpConnect() ) {
			throw new Exception( 'smtpConnect returned false' );
		}
		if ( $opts['auth_only'] ) {
			$mail->smtpClose();
		}
	}
);

if ( $opts['auth_only'] ) {
	fwrite( STDOUT, "\nAuth-only mode: credentials accepted. No message sent.\n" );
	exit( 0 );
}

ob_smtp_step(
	'Send test message',
	static function () use ( $mail, $opts ) {
		$mail->setFrom( $opts['from'], 'Blue Starfish SMTP Test' );
		$mail->addAddress( $opts['to'] );
		$mail->Subject = 'SMTP test ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC';
		$mail->Body    = "If you received this, DreamHost SMTP works.\n\nSent from scripts/test-dreamhost-smtp.php\n";
		$mail->send();
		$mail->smtpClose();
	}
);

fwrite( STDOUT, "\nDone. Check the inbox for {$opts['to']} (and spam).\n" );
