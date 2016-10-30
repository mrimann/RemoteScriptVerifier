<?php

namespace Mrimann\RemoteScriptVerifier;

use GuzzleHttp\Psr7\Response;

/**
 * The central part of the whole remote script execution and verification.
 */
class Verifier {
	/**
	 * @var string the source IP address
	 */
	protected $sourceIpAddress;

	/**
	 * @var string the base URL
	 */
	protected $baseUrl = '';

	/**
	 * @var boolean whether logging and throttling is enabled
	 */
	protected $isThrottlingAndLoggingEnabled;

	/**
	 * @var string the database user
	 */
	protected $databaseUser;

	/**
	 * @var string the database password
	 */
	protected $databasePassword;

	/**
	 * @var string the host on which the database runs
	 */
	protected $databaseHost;

	/**
	 * @var string the database name
	 */
	protected $databaseName;

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
	 * @var \GuzzleHttp\Client
	 */
	protected $httpClient;

	/**
	 * The databse connection
	 *
	 * @var \mysqli
	 */
	protected $db;

	/**
	 * The constructor :-)
	 *
	 * @param string $url the base Url, optional
	 */
	public function __construct($url = '') {
		$this->checkResults = new \ArrayIterator();
		$this->isThrottlingAndLoggingEnabled = FALSE;

		$this->setLimitBySourceIp(100);
		$this->setLimitByRemoteUrl(100);

		$this->httpClient = new \GuzzleHttp\Client(array(
				'base_uri' => $url,
				'http_errors' => false
			)
		);

		if ($url != '') {
			$this->baseUrl = $url;
		}
	}

	/**
	 * Checks whether logging and throttling is enabled at all
	 *
	 * @return bool TRUE if logging and throttling is enabled, false otherwise
	 */
	public function isThrottlingAndLoggingEnabled() {
		return $this->isThrottlingAndLoggingEnabled;
	}

	/**
	 * Checks if the pre-requisites are given, then enables the throttling and
	 * logging functionality.
	 *
	 * @throws Exception\MissingCredentialsException
	 * @return void
	 */
	public function enableThrottlingAndLogging() {
		if ($this->verifyDatabaseConnection()) {
			$this->isThrottlingAndLoggingEnabled = TRUE;
		}
	}

	/**
	 * Checks if the given DB credentials are correct, by trying to connect
	 * to the database.
	 *
	 * @return boolean TRUE if the database connection was successful, FALSE otherwise
	 */
	public function verifyDatabaseConnection() {
		$result = FALSE;

		if (
			$this->databaseHost != ''
			&& $this->databaseName != ''
			&& $this->databaseUser != ''
			&& $this->databasePassword != ''
		) {
			// connect to the database
			@$db = new \mysqli(
				$this->databaseHost,
				$this->databaseUser,
				$this->databasePassword,
				$this->databaseName
			);
			if ($db->connect_errno) {
				$this->addNewFailedResult('There seems to be some troubles with the database. Please try again later...');
			} else {
				$this->db = $db;
				$result = TRUE;
			}
		} else {
			throw new \Mrimann\RemoteScriptVerifier\Exception\MissingCredentialsException(
				'Missing Credentials for the Database.'
			);
		}

		return $result;
	}

	/**
	 * Checks if the request from a given IP address to a certain remote URL is
	 * allowed to be executed (or if it violates any given limitation).
	 *
	 * The check result is then being logged to the database.
	 *
	 * @param string the IP address of the requestor
	 * @param string the remote URL requested to be retrieved
	 * @param boolean whether to be verbose, defaults to FALSE
	 */
	public function checkRequestAgainstThrottlingLimits($sourceIpAddress, $remoteUrl, $beVerbose = FALSE) {
		$this->setSourceIpAddress($sourceIpAddress);
		$this->setBaseUrl($remoteUrl);

		$dateLimit = new \DateTime('-1 hour');

		$ipRes = $this->db->query('SELECT COUNT(*) as count FROM logging WHERE timestamp > "' . $dateLimit->format('Y-m-d H:i:s') . '" AND source_ip = "' . $this->db->escape_string($sourceIpAddress) . '"')->fetch_assoc();
		$ipCount = (int)$ipRes['count'];

		$remoteUrlRes = $this->db->query('SELECT COUNT(*) as count FROM logging WHERE timestamp > "' . $dateLimit->format('Y-m-d H:i:s') . '" AND remote_url ="' . $this->db->escape_string($remoteUrl) . '"')->fetch_assoc();
		$remoteUrlCount = (int)$remoteUrlRes['count'];

		// check them against the set limits
		if ($ipCount > $this->getLimitBySourceIp()
			|| $remoteUrlCount > $this->getLimitByRemoteUrl()) {
			$this->addNewFailedResult('Throttling in effect, please try later...');
			$status = 'fail';
		} else {
			if ($beVerbose == TRUE) {
				$this->addNewSuccessfulResult('You still operate within the regular limitations, go on.');
			}
			$status = 'pass';
		}

		$now = new \DateTime();
		$this->db->query('INSERT INTO logging SET source_ip="' . $this->db->escape_string($sourceIpAddress) .
			'", remote_url="' . $this->db->escape_string($remoteUrl) . '", timestamp="' . $now->format('Y-m-d H:i:s') . '", status="' . $status . '";');
	}

	/**
	 * Updates the existing log entry (from the throttling verification) with the
	 * final result.
	 *
	 * @param string the result to be logged to the database
	 * @param string the rawOutput of the tested script, optional, defaults to an empty string
	 */
	public function logVerification($result, $rawOutput = '') {
		$this->db->query(
			'UPDATE logging SET result="' . $result .
				'", rawOutput="' . $this->db->escape_string($rawOutput) .
				'" WHERE source_ip="' . $this->db->escape_string($this->getSourceIpAddress()) .
				'" AND remote_url="' . $this->db->escape_string($this->getBaseUrl()) .
				'" AND result="" LIMIT 1;'
		);
	}

	/**
	 * Stores the base URL
	 *
	 * @param string $url the URL to the remote script
	 */
	public function setBaseUrl($url) {
		$this->baseUrl = $url;
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
	 * @param string $sourceIpAddress
	 */
	public function setSourceIpAddress($sourceIpAddress) {
		$this->sourceIpAddress = $sourceIpAddress;
	}

	/**
	 * @return string
	 */
	public function getSourceIpAddress() {
		return $this->sourceIpAddress;
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
	 * @return \GuzzleHttp\Psr7\Response the result of the HTTP request
	 */
	public function fetchRemoteScript(array $getParameters) {
		try {
			//$response = $request->send();
			$response = $this->httpClient->request(
				'GET',
				$this->getBaseUrl() . '?' . http_build_query($getParameters),
				array('http_errors' => false)
			);
		} catch(\Exception $e) {
			$this->addNewFailedResult(
				'Fetching the remote URL failed.'
			);
			$response = new Response(
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

	/**
	 * @param string $databaseHost
	 */
	public function setDatabaseHost($databaseHost) {
		$this->databaseHost = $databaseHost;
	}

	/**
	 * @return string
	 */
	public function getDatabaseHost() {
		return $this->databaseHost;
	}

	/**
	 * @param string $databaseName
	 */
	public function setDatabaseName($databaseName) {
		$this->databaseName = $databaseName;
	}

	/**
	 * @return string
	 */
	public function getDatabaseName() {
		return $this->databaseName;
	}

	/**
	 * @param string $databasePassword
	 */
	public function setDatabasePassword($databasePassword) {
		$this->databasePassword = $databasePassword;
	}

	/**
	 * @return string
	 */
	public function getDatabasePassword() {
		return $this->databasePassword;
	}

	/**
	 * @param string $databaseUser
	 */
	public function setDatabaseUser($databaseUser) {
		$this->databaseUser = $databaseUser;
	}

	/**
	 * @return string
	 */
	public function getDatabaseUser() {
		return $this->databaseUser;
	}
}
?>