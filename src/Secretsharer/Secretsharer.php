<?php

namespace Secretsharer; 

use Secretsharer\Util as SecUtil;
use Secretsharer\CharsetConverter;

require __DIR__ . '/functions.php';

abstract class Secretsharer
{
    protected $_secretCharset = NULL;
    protected $_shareCharset  = NULL;
    
    function __construct($class = CharsetConverter::class)
    {
        $this->_secretConverter = new $class($this->_secretCharset);
        $this->_shareConverter  = new $class($this->_shareCharset);
    }
    
	function splitSecret($secret, $threshold, $numShares)
	{
        if (strlen($secret) > 64)
        {
            throw new \InvalidArgumentException("Hit artificial limit of 64 bytes for secrets.");
        }
        
		$secretInteger = $this->_secretConverter->stringToInteger($secret);
		$points        = $this->_secretIntegerToPoints($secretInteger, $threshold, $numShares);
		$shares        = []; 
		
		foreach ($points as $point)
		{
			$shares[] = $this->_pointToShareString($point);
		}
        
        return $shares;
	}
	
	public function recoverSecret($shares)
	{
		$points = [];
		foreach($shares as $share)
		{
            if (strlen($share) > 128)
            {
                throw new \InvalidArgumentException("Hit artificial limit of 128 bytes per share");
            }
            
			$points[] = $this->_shareStringToPoint($share);
		}

		$secretInt    = $this->_pointsToSecretInteger($points);
		$secretString = $this->_secretConverter->integerToString($secretInt);
        
		return $secretString;
	}
    
    protected function _pointsToSecretInteger($points)
    {
    	$xValues = $yValues = [];
    	foreach ($points as $p)
    	{
    		$xValues[] = $p[0];
    		$yValues[] = $p[1];
    	}
	
    	$prime         = SecUtil\get_large_enough_prime($yValues);
    	$secretInteger = SecUtil\modular_lagrange_interpolation(0, $points, $prime);

    	return $secretInteger;
    }
    
    protected function _shareStringToPoint($share)
    {
        $components = explode('-', $share);
    
        if (count($components) != 2)
        {
            throw new \InvalidArgumentException("Share should have exactly one '-'");
        }
    
    	list($xString, $yString) = explode('-', $share,2);
    	return [
            $this->_shareConverter->stringToInteger($xString),
            $this->_shareConverter->stringToInteger($yString)
    	];
    }
    
    protected function _pointToShareString($point)
    {
    	list($x,$y) = $point;
    
        return sprintf (
            '%s-%s', 
            $this->_shareConverter->integerToString($x),
            $this->_shareConverter->integerToString($y)
        );
    }
    
    protected function _secretIntegerToPoints($int, $threshold, $num_points)
    {
    	if ($threshold < 2)
        {
    		throw new \InvalidArgumentException(
                "The threshold (minimum number of shares to reconstruct the secret) must be larger than 2"
            );
        }
	
    	if ($threshold > $num_points) 
        {
    		throw new \InvalidArgumentException(
                "The threshold (minimum number of shares to reconstruct the secret) must be less than or equal to the total number of shares created."
            );
        }
	
    	$prime        = SecUtil\get_large_enough_prime([$int, $shares]);
        
        if ($prime === FALSE)
            throw new \InvalidArgumentException("Secret is too large");
        
    	$coefficients = SecUtil\random_polynomial($threshold - 1, $int, $prime);
    	$points       = SecUtil\get_polynomial_points($coefficients, $num_points, $prime);
        
    	return $points;
    }
    
}
