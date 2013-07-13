<?php

namespace Mrimann\RemoteScriptVerifier;

/**
 * Some basic re-usable checks
 */
class Checks {
	/**
	 * Instance of the verifier
	 *
	 * @var Verifier
	 */
	protected $verifier;

	/**
	 * The constructor
	 *
	 * @param Verifier $verifier
	 */
	public function __construct(\Mrimann\RemoteScriptVerifier\Verifier $verifier) {
		$this->verifier = $verifier;
	}

	/**
	 * Basic Check: Checks for non-emptiness of the base URL
	 *
	 * Adds a corresponding check-result to the verifier
	 *
	 * @return void
	 */
	public function executeTestForNonEmptyUrl() {
		if ($this->verifier->getBaseUrl() != '') {
			$this->verifier->addNewSuccessfulResult('URL is not empty');
		} else {
			$this->verifier->addNewFailedResult('URL is empty, I cannot work this way dude...');
		}
	}

	/**
	 * Basic Check: Checks if the URL looks normal according to the PHP
	 * filter_var check.
	 *
	 * Adds a corresponding check-result to the verifier
	 *
	 * @return void
	 */
	public function executeTestForValidLookingUrl() {
		if (filter_var($this->verifier->getBaseUrl(), FILTER_VALIDATE_URL)) {
			$this->verifier->addNewSuccessfulResult('URL format looks sane');
		} else {
			$this->verifier->addNewFailedResult('URL seems to be invalid - at least PHP sees it that way.');
		}
	}

	/**
	 * Basic Check: Checks if the remote script responds with a successful status code (200)
	 * when being called with some (valid) values as GET parameters.
	 *
	 * Adds a corresponding check-result to the verifier
	 *
	 * @return void
	 */
	public function executeTestForAvailabilityOfTheRemoteScript($requiredGetParameters = array()){
		$result = $this->verifier->fetchRemoteScript($requiredGetParameters);

		if ($result->getStatusCode() == 200) {
			$this->verifier->addNewSuccessfulResult('Script seems to respond in a usable way.');
		} else {
			$this->verifier->addNewFailedResult('For whatever reason, it looks like your script could not be fetched at all.');
		}
	}
}
?>