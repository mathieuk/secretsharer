<?php
    
namespace Secretsharer;

class PlaintextSecretSharer extends Secretsharer
{
    protected $_secretCharset = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~ \t\n\r\x0b\x0c";
    protected $_shareCharset  = "0123456789abcdef";
}
