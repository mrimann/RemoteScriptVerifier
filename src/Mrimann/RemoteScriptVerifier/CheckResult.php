<?php

namespace Mrimann\RemoteScriptVerifier;

/**
 * Instance of a check result
 */
class CheckResult {

	/**
	 * Some constants for the status
	 */
	const STATUS_PASS = 'pass';
	const STATUS_FAIL = 'fail';
	const STATUS_CRITICAL = 'critical';

	/**
	 * The status
	 *
	 * @var string
	 */
	protected $status;

	/**
	 * The message
	 *
	 * @var string
	 */
	protected $message = '';

	/**
	 * The constructor :-)
	 *
	 * @param string $url the base Url, optional
	 */
	public function __construct($status, $message) {
		$this->status = $status;
		$this->message = $message;
	}

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}
}
?>