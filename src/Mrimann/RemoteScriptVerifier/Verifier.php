<?php

namespace Mrimann\RemoteScriptVerifier;

/**
 * The central part of the whole remote script execution and verification.
 */
class Verifier {
	/**
	 * @var string the base URL
	 */
	var $baseUrl = '';

	/**
	 * The check results stack
	 *
	 * @var \ArrayIterator
	 */
	var $checkResults;

	/**
	 * The Number of errors that occurred
	 * @var int
	 */
	var $errorCount = 0;

	/**
	 * The Guzzle HTTP Client object
	 *
	 * @var \Guzzle\Http\Client
	 */
	var $httpClient;

	/**
	 * The constructor :-)
	 *
	 * @param string $url the base Url, optional
	 */
	public function __construct($url = '') {
		$this->checkResults = new \ArrayIterator();

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
	 * Returns the number of errors that occured
	 *
	 * @return int the number of errors
	 */
	public function getErrorCount() {
		return $this->errorCount;
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
		$response = $request->send();

		return $response;
	}

}
?>