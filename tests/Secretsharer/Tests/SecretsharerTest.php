<?php

namespace Secretsharer\Tests;

use Secretsharer;

class SecretsharerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->_sharer = new Secretsharer();
    }
    
    public function testSharesCannotHaveDashes()
    {   
    }
    
    public function testSecretMaxLengthIs128Bytes()
    {
    }
    
    public function testSecretSharerCreates2SharesWhichRecoverToTheInitialSecret()
    {   
    }
    
    public function testSecretSharerRecoversSecretFrom3Shares()
    {
    }
}