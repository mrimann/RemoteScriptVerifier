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
	public function isThrottlingAndLoggingEnabledIsFalseByDefault() {
		$this->assertFalse(
			$this->fixture->isThrottlingAndLoggingEnabled()
		);
	}

	/**
	 * @test
	 */
	public function verifyDatabaseConnectionThrowsExceptionIfNoCredentialsAreSetYet() {
		$this->setExpectedException('\Mrimann\RemoteScriptVerifier\Exception\MissingCredentialsException');
		$this->fixture->verifyDatabaseConnection();
	}

	/**
	 * @test
	 */
	public function verifyDatabaseConnectionDoesNotThrowExceptionIfCredentialsAreSetYet() {
		$this->fixture->setDatabaseUser('foo');
		$this->fixture->setDatabasePassword('bar');
		$this->fixture->setDatabaseHost('localhost');
		$this->fixture->setDatabaseName('quiz');

		$this->fixture->verifyDatabaseConnection();
	}

	/**
	 * @test
	 */
	public function isThrottlingAndLoggingEnabledReturnsTrueAfterEnablingItWidthCorrectCredentials() {
		$this->fixture->setDatabaseUser('travis');
		$this->fixture->setDatabasePassword('travis');
		$this->fixture->setDatabaseHost('localhost');
		$this->fixture->setDatabaseName('scriptverifier');

		$this->fixture->enableThrottlingAndLogging();

		$this->assertTrue(
			$this->fixture->isThrottlingAndLoggingEnabled()
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
	public function checkRequestAgainstThrottlingLimitsDoesNotAddSuccessfulCheckResultIfEverythingIsOkByDefault() {
		$this->enableTestDatabase();

		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org'
		);

		$this->assertEquals(
			0,
			$this->fixture->getCheckResults()->count()
		);
	}

	/**
	 * @test
	 */
	public function checkRequestAgainstThrottlingLimitsAddsSuccessfulCheckResultIfEverythingIsOkAndVerbosityEnabled() {
		$this->enableTestDatabase();

		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org',
			TRUE
		);

		$this->assertEquals(
			1,
			$this->fixture->getCheckResults()->count()
		);

		$this->assertEquals(
			'pass',
			$this->fixture->getCheckResults()->current()->getStatus()
		);
	}

	/**
	 * @test
	 */
	public function checkRequestAgainstThrottlingLimitsAddsFailedCheckResultIfIpLimitIsReached() {
		$this->enableTestDatabase();

		$this->fixture->setLimitBySourceIp(0);

		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org',
			TRUE
		);
		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org'
		);

		$this->assertEquals(
			2,
			$this->fixture->getCheckResults()->count()
		);

		$this->assertEquals(
			'pass',
			$this->fixture->getCheckResults()->current()->getStatus()
		);
		$this->fixture->getCheckResults()->next();
		$this->assertEquals(
			'fail',
			$this->fixture->getCheckResults()->current()->getStatus()
		);
	}

	/**
	 * @test
	 */
	public function checkRequestAgainstThrottlingLimitsAddsFailedCheckResultIfRemoteUrlLimitIsReached() {
		$this->enableTestDatabase();

		$this->fixture->setLimitByRemoteUrl(0);

		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org',
			TRUE
		);
		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org'
		);

		$this->assertEquals(
			2,
			$this->fixture->getCheckResults()->count()
		);

		$this->assertEquals(
			'pass',
			$this->fixture->getCheckResults()->current()->getStatus()
		);
		$this->fixture->getCheckResults()->next();
		$this->assertEquals(
			'fail',
			$this->fixture->getCheckResults()->current()->getStatus()
		);
	}

	/**
	 * @test
	 */
	public function checkRequestAgainstThrottlingLimitsStoresTheSourceIpAddress() {
		$this->enableTestDatabase();

		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org'
		);

		$this->assertEquals(
			'127.0.0.1',
			$this->fixture->getSourceIpAddress()
		);
	}

	/**
	 * @test
	 */
	public function checkRequestAgainstThrottlingLimitsStoresTheRemoteScriptAddress() {
		$this->enableTestDatabase();

		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org'
		);

		$this->assertEquals(
			'http://www.example.org',
			$this->fixture->getBaseUrl()
		);
	}


	/**
	 * @test
	 */
	public function checkRequestAgainstThrottlingLimitsAddsCorrectEntryInTheLogTableOnPass() {
		$db = $this->enableTestDatabase();

		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org/'
		);

		$dbResult = $db->query('SELECT status FROM logging where source_ip="127.0.0.1" LIMIT 1;')->fetch_assoc();

		$this->assertEquals(
			'pass',
			$dbResult['status']
		);
	}

	/**
	 * @test
	 */
	public function checkRequestAgainstThrottlingLimitsAddsCorrectEntryInTheLogTableOnFail() {
		$db = $this->enableTestDatabase();

		$this->fixture->setLimitBySourceIp(0);

		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org/'
		);
		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org/'
		);

		$dbResult = $db->query('SELECT status FROM logging where source_ip="127.0.0.1" LIMIT 1,1;')->fetch_assoc();

		$this->assertEquals(
			'fail',
			$dbResult['status']
		);
	}

	/**
	 * @test
	 */
	public function checkRequestAgainstThrottlingLimitsChecksOnlyAgainstRecordsOfThePreviousHours() {
		$db = $this->enableTestDatabase();
		$twoHoursAgo = new \DateTime('-2 hours');

		$db->query('INSERT INTO logging SET source_ip="127.0.0.1", remote_url="http://www.example.org/", timestamp="' . $twoHoursAgo->format('Y-m-d H:i:s') . '", status="pass";');


		$this->fixture->setLimitBySourceIp(0);

		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org/',
			TRUE
		);
		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org/'
		);


		$this->assertEquals(
			2,
			$this->fixture->getCheckResults()->count()
		);

		$this->assertEquals(
			'pass',
			$this->fixture->getCheckResults()->current()->getStatus()
		);
		$this->fixture->getCheckResults()->next();
		$this->assertEquals(
			'fail',
			$this->fixture->getCheckResults()->current()->getStatus()
		);
	}

	/**
	 * @test
	 */
	public function verifyDatabaseConnectionReturnsTrueOnSuccessfulConnect() {
		$this->enableTestDatabase();

		$this->assertTrue(
			$this->fixture->verifyDatabaseConnection()
		);
	}

	/**
	 * @test
	 */
	public function verifyDatabaseConnectionReturnsFalseOnFailedConnect() {
		$this->fixture->setDatabaseHost('127.0.0.1');
		$this->fixture->setDatabaseName('foobar');
		$this->fixture->setDatabaseUser('foobar');
		$this->fixture->setDatabasePassword('lorem');

		$this->assertFalse(
			$this->fixture->verifyDatabaseConnection()
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

	/**
	 * @test
	 */
	public function setDatabaseUserSetsDatabaseUser() {
		$this->fixture->setDatabaseUser('foo');
		$this->assertEquals(
			'foo',
			$this->fixture->getDatabaseUser()
		);
	}

	/**
	 * @test
	 */
	public function setDatabaseUserSetsDatabasePassword() {
		$this->fixture->setDatabasePassword('bar');
		$this->assertEquals(
			'bar',
			$this->fixture->getDatabasePassword()
		);
	}

	/**
	 * @test
	 */
	public function setDatabaseUserSetsDatabaseHost() {
		$this->fixture->setDatabaseHost('localhost');
		$this->assertEquals(
			'localhost',
			$this->fixture->getDatabaseHost()
		);
	}

	/**
	 * @test
	 */
	public function setDatabaseUserSetsDatabaseName() {
		$this->fixture->setDatabaseName('quiz');
		$this->assertEquals(
			'quiz',
			$this->fixture->getDatabaseName()
		);
	}

	/**
	 * @test
	 */
	public function logVerificationLogsOneLinePerRun() {
		$db = $this->enableTestDatabase();

		$this->fixture->checkRequestAgainstThrottlingLimits(
			'127.0.0.1',
			'http://www.example.org/'
		);

		$this->fixture->logVerification(
			'foo'
		);

		$dbResult = $db->query('SELECT COUNT(*) as count FROM logging WHERE source_ip="127.0.0.1" AND remote_url="http://www.example.org/" AND result="foo"')->fetch_assoc();

		$this->assertEquals(
			1,
			$dbResult['count']
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

	/**
	 * Initializes the database connection - only for testing purpose of course...
	 *
	 * @return \mysqli a connected instance
	 */
	protected function enableTestDatabase() {
		$this->fixture->setDatabaseHost('127.0.0.1');
		$this->fixture->setDatabaseUser('travis');
		$this->fixture->setDatabasePassword('travis');
		$this->fixture->setDatabaseName('scriptverifier');

		$this->fixture->enableThrottlingAndLogging();

		$db = new \mysqli(
			'127.0.0.1', 'travis', 'travis', 'scriptverifier'
		);
		$db->query('TRUNCATE TABLE logging;');

		return $db;
	}
}
