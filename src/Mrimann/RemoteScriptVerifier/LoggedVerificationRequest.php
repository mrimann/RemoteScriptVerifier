<?php

namespace Mrimann\RemoteScriptVerifier;

/**
 * The central part of the whole remote script execution and verification.
 */
class LoggedVerificationRequest {
	/**
	 * The source IP of the request
	 * @var string
	 */
	protected $sourceIp;

	/**
	 * The target URL the user entered
	 * @var string
	 */
	protected $targetUrl;

	/**
	 * The target IP of the remote host
	 * @var string
	 */
	protected $targetIp;

	/**
	 * The status of the request
	 * @var string
	 */
	protected $status;

	/**
	 * The timestamp of the request
	 * @var \DateTime
	 */
	protected $timestamp;

	/**
	 * @param string $sourceIp
	 */
	public function setSourceIp($sourceIp) {
		$this->sourceIp = $sourceIp;
	}

	/**
	 * @return string
	 */
	public function getSourceIp() {
		return $this->sourceIp;
	}

	/**
	 * @param string $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param string $targetIp
	 */
	public function setTargetIp($targetIp) {
		$this->targetIp = $targetIp;
	}

	/**
	 * @return string
	 */
	public function getTargetIp() {
		return $this->targetIp;
	}

	/**
	 * @param string $targetUrl
	 */
	public function setTargetUrl($targetUrl) {
		$this->targetUrl = $targetUrl;
	}

	/**
	 * @return string
	 */
	public function getTargetUrl() {
		return $this->targetUrl;
	}

	/**
	 * @param \DateTime $timestamp
	 */
	public function setTimestamp($timestamp) {
		$this->timestamp = $timestamp;
	}

	/**
	 * @return \DateTime
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}
}
?>