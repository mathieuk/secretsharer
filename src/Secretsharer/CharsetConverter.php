<?php
    
namespace Secretsharer;

use Secretsharer\Util as SecUtil;

class CharsetConverter 
{
    public function __construct($charset)
    {
        $this->_charset = $charset;
    }
    
    public function stringToInteger($string)
    {
    	$output     = 0;
        $string_len = strlen($string);
    
    	for ($i = 0; $i < $string_len; $i++) 
    	{
            // $output = ( $output * strlen($charset) ) + strpos($charset, $string[$i]);
    		$output = bcadd(
    			bcmul($output, strlen($this->_charset)),
    			strpos($this->_charset, $string[$i])
    		);
    	}

    	return $output;
        
    }
    
    public function integerToString($int)
    {
        // if ($int === 0)
    	if (bccomp($int,"0") === BCMATH_COMP_EQUAL)
        {
    		return $this->_charset[0];
        }
	
    	$output = '';
	
        // while ($int > 0) 
    	while (bccomp($int, "0") == BCMATH_COMP_LEFT_BIGGER)
    	{
    		$new_int = bcdiv( 
    			$int,
    			strlen($this->_charset)
    		);
		
    		$mod     = SecUtil\bc_unsigned_mod(
    			$int, 
    			strlen($this->_charset)
    		);
		
    		$int     = $new_int;	
    		$output .= $this->_charset[$mod];
    	}
	
    	return strrev($output);
        
    }
}