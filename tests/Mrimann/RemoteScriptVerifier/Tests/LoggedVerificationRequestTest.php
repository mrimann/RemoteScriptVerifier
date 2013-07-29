<?php

namespace Mrimann\RemoteScriptVerifier\Tests;

class LoggedVerificationRequestTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var \Mrimann\RemoteScriptVerifier\LoggedVerificationRequest
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \Mrimann\RemoteScriptVerifier\LoggedVerificationRequest();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function setSourceIpSetsTheIpProperly() {
		$this->fixture->setSourceIp('127.0.0.99');

		$this->assertEquals(
			'127.0.0.99',
			$this->fixture->getSourceIp()
		);
	}

	/**
	 * @test
	 */
	public function setTargetUrlSetsTheUrlProperly() {
		$this->fixture->setTargetUrl('http://www.rimann.org/foo.php');

		$this->assertEquals(
			'http://www.rimann.org/foo.php',
			$this->fixture->getTargetUrl()
		);
	}

	/**
	 * @test
	 */
	public function setTargetIpSetsTheIpProperly() {
		$this->fixture->setTargetIp('127.0.0.19');

		$this->assertEquals(
			'127.0.0.19',
			$this->fixture->getTargetIp()
		);
	}

	/**
	 * @test
	 */
	public function setTimestampSetsTimestampProperly() {
		$timestamp = new \DateTime();
		$this->fixture->setTimestamp($timestamp);

		$this->assertEquals(
			$timestamp,
			$this->fixture->getTimestamp()
		);
	}

}
