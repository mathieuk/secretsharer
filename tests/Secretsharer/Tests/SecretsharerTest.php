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
    
    public function testSecretSharerCanSplit91ByteSecrets()
    {   
        $secret = str_repeat("a", 91);
        $shares = $this->_sharer->splitSecret($secret, 2, 4);
        
        $this->assertEquals(4, count($shares), "Secretsharer should've returned 4 shares");
        $recoveredSecret = $this->_sharer->recoverSecret([$shares[0], $shares[3]]);
        
        $this->assertEquals($secret, $recoveredSecret);
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
        
        $this->assertEquals(6, count($shares), "Secretsharer should've returned 4 shares");
        $recoveredSecret = $this->_sharer->recoverSecret([$shares[0], $shares[5], $shares[3]]);
        
        $this->assertEquals($secret, $recoveredSecret);
    }
    
    
}