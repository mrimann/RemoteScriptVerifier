<?php

namespace Mrimann\RemoteScriptVerifier\Tests;

class ClientTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var \Mrimann\RemoteScriptVerifier\Verifier
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \Mrimann\RemoteScriptVerifier\Verifier('http://www.example.org/test.php');
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function constructorSetsBaseUrlProperly() {
		$this->assertEquals(
			'http://www.example.org/test.php',
			$this->fixture->getBaseUrl()
		);
	}

	/**
	 * @test
	 */
	public function constructorSetsLimitPerSourceIpByDefault() {
		$this->assertEquals(
			100,
			$this->fixture->getLimitBySourceIp()
		);
	}

	/**
	 * @test
	 */
	public function constructorSetsLimitPerRemoteUrlByDefault() {
		$this->assertEquals(
			100,
			$this->fixture->getLimitByRemoteUrl()
		);
	}


	/**
	 * @test
	 */
	public function checkResultsIsEmptyOnFreshVerifier() {
		$this->assertEquals(
			0,
			$this->fixture->getCheckResults()->count()
		);
	}

	/**
	 * @test
	 */
	public function errorCountIsZeroOnFreshVerifier() {
		$this->assertEquals(
			0,
			$this->fixture->getErrorCount()
		);
	}

	/**
	 * @test
	 */
	public function passedAllTestsIsTrueOnFreshVerifier() {
		$this->assertTrue(
			$this->fixture->passedAllTests()
		);
	}

	/**
	 * @test
	 */
	public function setBaseUrlSetsBaseUrlProperly() {
		$this->fixture->setBaseUrl('http://www.example.org/test2.php');

		$this->assertEquals(
			'http://www.example.org/test2.php',
			$this->fixture->getBaseUrl()
		);
	}

	/**
	 * @test
	 */
	public function getCheckResultsReturnsInstanceOfArrayIterator() {
		$this->assertInstanceOf(
			'ArrayIterator',
			$this->fixture->getCheckResults()
		);
	}

	/**
	 * @test
	 */
	public function addingSuccessfulResultKeepsAllChecksPassedTrue() {
		$this->addValidPositiveMessage();

		$this->assertTrue(
			$this->fixture->passedAllTests()
		);
	}

	/**
	 * @test
	 */
	public function addingFailureResultChangesAllTestsPassedToFalse() {
		$this->addValidFailedMessage();

		$this->assertFalse(
			$this->fixture->passedAllTests()
		);
	}

	/**
	 * @test
	 */
	public function addingSuccessfulResultDoesNotRaiseErrorCount() {
		$this->addValidPositiveMessage();

		$this->assertEquals(
			0,
			$this->fixture->getErrorCount()
		);
	}

	/**
	 * @test
	 */
	public function addingFailureResultRaisesErrorCount() {
		$this->addValidFailedMessage();

		$this->assertEquals(
			1,
			$this->fixture->getErrorCount()
		);
	}

	/**
	 * @test
	 */
	public function addingSuccessfulResultIsContainedInCheckResults() {
		$this->addValidPositiveMessage();

		$this->assertEquals(
			1,
			$this->fixture->getCheckResults()->count()
		);

		$this->assertEquals(
			'successful',
			$this->fixture->getCheckResults()->current()->getMessage()
		);
	}

	/**
	 * @test
	 */
	public function addingFailureResultIsContainedInCheckResults() {
		$this->addValidFailedMessage();

		$this->assertEquals(
			1,
			$this->fixture->getCheckResults()->count()
		);

		$this->assertEquals(
			'failed',
			$this->fixture->getCheckResults()->current()->getMessage()
		);
	}

	/**
	 * @test
	 */
	public function setLimitBySourceIPSetsTheLimitProperly() {
		$this->fixture->setLimitBySourceIp(4242);

		$this->assertEquals(
			4242,
			$this->fixture->getLimitBySourceIp()
		);
	}

	/**
	 * @test
	 */
	public function setLimitByRemoteUrlSetsTheLimitProperly() {
		$this->fixture->setLimitByRemoteUrl(2121);

		$this->assertEquals(
			2121,
			$this->fixture->getLimitByRemoteUrl()
		);
	}

	protected function addValidPositiveMessage() {
		$this->fixture->addNewSuccessfulResult(
			'successful'
		);
	}

	protected function addValidFailedMessage() {
		$this->fixture->addNewFailedResult(
			'failed'
		);
	}

}
