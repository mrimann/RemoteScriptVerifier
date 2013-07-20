<?php

namespace Mrimann\RemoteScriptVerifier\Tests;

class CheckResultTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var \Mrimann\RemoteScriptVerifier\CheckResult
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = new \Mrimann\RemoteScriptVerifier\CheckResult('foo', 'Lorem ipsum.');
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function constructorSetsStatusProperly() {
		$this->assertEquals(
			'foo',
			$this->fixture->getStatus()
		);
	}

	/**
	 * @test
	 */
	public function constructorSetsMessageProperly() {
		$this->assertEquals(
			'Lorem ipsum.',
			$this->fixture->getMessage()
		);
	}
}
