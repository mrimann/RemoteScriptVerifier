<?php

namespace Mrimann\RemoteScriptVerifier;

/**
 * The central part of the whole remote script execution and verification.
 */
class Verifier {
	/**
	 * @var string the base URL
	 */
	protected  $baseUrl = '';

	/**
	 * @var integer the limit of requests from a given IP
	 */
	protected $limitBySourceIp;

	/**
	 * @var integer the limit of requests for a given URL
	 */
	protected $limitByRemoteUrl;

	/**
	 * The check results stack
	 *
	 * @var \ArrayIterator
	 */
	protected $checkResults;

	/**
	 * The Number of errors that occurred
	 * @var int
	 */
	protected $errorCount = 0;

	/**
	 * The Guzzle HTTP Client object
	 *
	 * @var \Guzzle\Http\Client
	 */
	protected $httpClient;

	/**
	 * The constructor :-)
	 *
	 * @param string $url the base Url, optional
	 */
	public function __construct($url = '') {
		$this->checkResults = new \ArrayIterator();

		$this->setLimitBySourceIp(100);
		$this->setLimitByRemoteUrl(100);

		$this->httpClient = new \Guzzle\Http\Client($url);
		if ($url != '') {
			$this->baseUrl = $url;
		}
	}

	/**
	 * Stores the base URL
	 *
	 * @param string $url the URL to the remote script
	 */
	public function setBaseUrl($url) {
		$this->baseUrl = $url;
		$this->httpClient->setBaseUrl($url);
	}

	/**
	 * Returns the base URL
	 *
	 * @return string the base url
	 */
	public function getBaseUrl() {
		return $this->baseUrl;
	}

	/**
	 * @param int $limitBySourceIp
	 */
	public function setLimitBySourceIp($limitBySourceIp) {
		$this->limitBySourceIp = $limitBySourceIp;
	}

	/**
	 * @return int
	 */
	public function getLimitBySourceIp() {
		return $this->limitBySourceIp;
	}

	/**
	 * @param int $limitByRemoteUrl
	 */
	public function setLimitByRemoteUrl($limitByRemoteUrl) {
		$this->limitByRemoteUrl = $limitByRemoteUrl;
	}

	/**
	 * @return int
	 */
	public function getLimitByRemoteUrl() {
		return $this->limitByRemoteUrl;
	}


	/**
	 * Returns the number of errors that occured
	 *
	 * @return int the number of errors
	 */
	public function getErrorCount() {
		return $this->errorCount;
	}

	/**
	 * Checks whether all checks passed, by checking the number of errors to be zero
	 *
	 * @return boolean true if no single error happened
	 */
	public function passedAllTests() {
		if ($this->errorCount == 0) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Executes an HTTP request (GET) to the remote script.
	 *
	 * Note: By default, the Guzzle HTTP Client would throw an exception, in case
	 * the remote server responds e.g. with a status-code 500. But that would make
	 * testing/evaluating the output much more difficult. For that reason, the
	 * exceptions are disabled for the call!
	 *
	 * @param array $getParameters some GET parameters to be sent to the remote script
	 * @return \Guzzle\Http\Message\Response the result of the HTTP request
	 */
	public function fetchRemoteScript(array $getParameters) {
		$request = $this->httpClient->get(
			'?' . http_build_query($getParameters),
			array(),
			array('exceptions' => FALSE)
		);
		try {
			$response = $request->send();
		} catch(\Exception $e) {
			$this->addNewFailedResult(
				'Fetching the remote URL failed.'
			);
			$response = new \Guzzle\Http\Message\Response(
				500
			);
		}

		return $response;
	}

	/**
	 * Returns the check results
	 *
	 * @return \ArrayIterator
	 */
	public function getCheckResults() {
		return $this->checkResults;
	}

	/**
	 * Adds a new check result message with a positive status
	 *
	 * @param string $message
	 */
	public function addNewSuccessfulResult($message) {
		$checkResult = new CheckResult(
			CheckResult::STATUS_PASS,
			$message
		);
		$this->checkResults->append($checkResult);
	}

	/**
	 * Adds a new check result message with a failure status
	 * @param string $message
	 */
	public function addNewFailedResult($message) {
		$checkResult = new CheckResult(
			CheckResult::STATUS_FAIL,
			$message
		);

		$this->errorCount++;
		$this->checkResults->append($checkResult);
	}
}
?>