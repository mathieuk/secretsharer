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
    
    /**
     * Split a secret string of max 64 bytes into $numShares and require $threshold shares to be provided
     * to recover the secret.
     *
     * @param $secret string The secret to split up
     * @param $threshold integer The amount of shares needed to recover the secret ( must be < $numShares )
     * @param $numShares integer The amount of shares to generate
     * @return string[] Array of shares 
     */ 
	function splitSecret($secret, $threshold, $numShares)
	{
        // SSSS only seems to work for certain key lengths. Limit to a known working length. 
        if (strlen($secret) > 64)
        {
            throw new \InvalidArgumentException("Hit artificial limit of 64 bytes for secrets.");
        }
        
        // Obviously, we're going to need more than 1 share to reconstruct the secret
    	if ($threshold < 2)
        {
    		throw new \InvalidArgumentException(
                "The threshold (minimum number of shares to reconstruct the secret) must be larger than 2"
            );
        }
	
        // Can't require more shares than we generate
    	if ($threshold > $numShares) 
        {
    		throw new \InvalidArgumentException(
                "The threshold (minimum number of shares to reconstruct the secret) must be less than or equal to the total number of shares created."
            );
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
	
    /**
     * Take the provided shares and try to recover the secret. Note: when given invalid shares this will
     * still return data but it will be gibberish.
     *
     * @param $shares string[] array of share strings
     * @return string the recovered secret 
     */
	public function recoverSecret($shares)
	{
        if (!is_array($shares))
        {
            throw new \InvalidArgumentException("Provided shares must be array of strings");
        }
        
        // Protect ourselves against many provided shares. 
        if (count($shares) > 6)
        {
            throw new \InvalidArgumentException("Hit artificial limit of 6 shares for recovery");
        }
        
		$points = [];
		foreach($shares as $idx => $share)
		{
            // Protect ourselves against weird share variables
            if (!is_string($share))
            {
                throw new \InvalidArgumentException("Share #" . ($idx+1) . " is not a string");
            }
            
            // Protect ourselves against huge shares which may cause significant CPU usage 
            if (strlen($share) > 140)
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
    	$prime        = SecUtil\get_large_enough_prime([$int, $num_points]);
        
        if ($prime === FALSE)
            throw new \InvalidArgumentException("Secret is too large");
        
    	$coefficients = SecUtil\random_polynomial($threshold - 1, $int, $prime);
    	$points       = SecUtil\get_polynomial_points($coefficients, $num_points, $prime);
        
    	return $points;
    }
    
}
