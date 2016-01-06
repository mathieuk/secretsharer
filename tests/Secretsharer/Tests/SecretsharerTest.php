<?php

namespace Secretsharer\Tests;

use Secretsharer\PlaintextSecretSharer;

class SecretsharerTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->_sharer = new PlaintextSecretSharer();
	}
	
	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Share should have exactly one '-'
	 */
	public function testProvidedSharesCannotHaveDashes()
	{
		$this->_sharer->recoverSecret([
			'1--1234',
			'2-abcdef'
		]);
	}
	
	public function testSecretSharerCanSplit64ByteSecrets()
	{	
		$secret = str_repeat("a", 64);
		$shares = $this->_sharer->splitSecret($secret, 2, 4);

		$this->assertEquals(4, count($shares), "Secretsharer should've returned 4 shares");
		$recoveredSecret = $this->_sharer->recoverSecret([$shares[0], $shares[3]]);
		
		$this->assertEquals($secret, $recoveredSecret);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Hit artificial limit of 64 bytes for secrets.
	 */
	
	public function testSecretSharerWontSplit65ByteSecrets()
	{	
		$secret = str_repeat("a", 65);
		$shares = $this->_sharer->splitSecret($secret, 2, 4);
		
		$this->assertEquals(4, count($shares), "Secretsharer should've returned 4 shares");
		$recoveredSecret = $this->_sharer->recoverSecret([$shares[0], $shares[3]]);
		
		$this->assertEquals($secret, $recoveredSecret);
	}
	
	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage The threshold (minimum number of shares to reconstruct the secret) must be less than or equal to the total number of shares created.
	 */
	public function testSecretSharerFailsForWeirdThresholdConfigurations()
	{
		$secret = "ABCDEF";
		
		// Require 4 shares to be provided to recover the secret, but only generate 2 shares 
		$this->_sharer->splitSecret($secret, $threshold=4, $numShares=2);
	}
	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Share #2 is not a string
	 */
	public function testSecretSharerWontRecoverSecretFromNonstringShares()
	{
		$shares = [ "1-abcdefg", new \StdClass ];
		$this->_sharer->recoverSecret($shares);
	}
	
	public function testSecretSharerRecoversSecretFrom2Shares()
	{	
		$secret = "ABCDEF";
		$shares = $this->_sharer->splitSecret($secret, 2, 4);
		
		$this->assertEquals(4, count($shares), "Secretsharer should've returned 4 shares");
		$recoveredSecret = $this->_sharer->recoverSecret([$shares[0], $shares[3]]);
		
		$this->assertEquals($secret, $recoveredSecret);
	}
	
	public function testSecretSharerRecoversSecretFrom3Shares()
	{
		$secret = "ABCDEF";
		$shares = $this->_sharer->splitSecret($secret, 3, 6);
		
		$this->assertEquals(6, count($shares), "Secretsharer should've returned 6 shares");
		$recoveredSecret = $this->_sharer->recoverSecret([$shares[0], $shares[5], $shares[3]]);
		
		$this->assertEquals($secret, $recoveredSecret);
	}
	
	
}