<?php

namespace Secretsharer\Util;

define ('BCMATH_COMP_LEFT_SMALLER', -1);
define ('BCMATH_COMP_EQUAL', 0);
define ('BCMATH_COMP_LEFT_BIGGER', 1);

/**
 * Return a large random number, larger than PHP can support on its own. 
 *
 * @param $min LargeInt minimal value
 * @param $max LargeInt maximum value
 * @returns LargeInt
 */
function big_random_number($min, $max) 
{
	mt_srand(hexdec(bin2hex(openssl_random_pseudo_bytes(7))));
	
	$difference	  = bcadd(bcsub($max,$min),1);
	$rand_percent = bcdiv(mt_rand(), mt_getrandmax(), 8); // 0 - 1.0
	$result		  = bcadd($min, bcmul($difference, $rand_percent, 8), 0);
	
	return $result;
}

/**
 * Modulus in PHP and BCMATH works differently than the original package expects:
 * PHP: -4 % 1024 = -4 
 * Python: -4 % 1024 = 1020
 *	
 * This function mimics the Python behaviour using BCMATH. 
 *
 * @param $a LargeInt
 * @param $b LargeInt
 * @return LargeInt
 */ 
function bc_unsigned_mod ($a, $b)
{
	$c = bcmod($a, $b);

	if (bccomp($c,"0") === BCMATH_COMP_LEFT_SMALLER)
	{
		$c = bcadd($c,$b);
	}

	return $c;
}


function generate_standard_primes()
{
	$primes = [];
	
	// Mersenne 
	$mersenne_prime_exponents = [
		2, 3, 5, 7, 13, 17, 19, 31, 61, 89, 107, 127, 521, 607, 1279			
	];
	
	foreach ($mersenne_prime_exponents as $exp)
	{
		$prime = "1";
		foreach (range(1,$exp) as $i => $v)
			$prime = bcmul($prime, 2);
		
		$prime = bcsub($prime, 1);
		$primes[] = $prime;
	}
	
	// Standard 
	$primes[] = bcadd(bcpow(2, 256),297);
	$primes[] = bcadd(bcpow(2, 320), 27);
	$primes[] = bcadd(bcpow(2, 384), 231);
	
	sort($primes);
	return $primes;
}

function get_large_enough_prime($batch)
{
	$primes = generate_standard_primes();
	
	foreach ($primes as $prime)
	{
		$numbers_greater_than_prime = [];
		foreach ($batch as $i)
		{
			if ($i > $prime)
			{
				$numbers_greater_than_prime[] = $i;
			}
		}
		
		if (count($numbers_greater_than_prime) == 0)
		{
			return $prime;
		}
	}

	return FALSE;
}

function random_polynomial($degree, $intercept, $upperbound)
{
	if ($degree < 0)
	{
		throw new InvalidArgumentException("Must be non-negative");
	}
	
	$coefficients = [$intercept];

	for ($i = 0; $i < $degree; $i++)
	{
		$random = big_random_number(0,bcsub($upperbound,1));
		$coefficients[] = $random;
	}
	
	return $coefficients;
}

function get_polynomial_points($coefficients, $shares, $prime)
{
	$points = [];
	
	for($x = 1; $x <= ($shares); $x++)
	{
		$y = $coefficients[0];
		
		for ($i = 1; $i < count($coefficients); $i++ )
		{
			// $exp = ($x ** $i) % $prime 
			$exp  = bc_unsigned_mod(bcpow($x, $i), $prime);
			
			// $term = ($coefficients[$i] * $exp) % $prime
			$term = bc_unsigned_mod(bcmul($coefficients[$i],$exp), $prime);
			
			// $y = ( $y + $term ) % $prime
			$y	  = bc_unsigned_mod(bcadd($y, $term), $prime);
		}
		
		$points[] = [$x,$y];
	}
	
	return $points;
}

function modular_lagrange_interpolation($x, $points, $prime)
{
	$x_values=$y_values=[];
	foreach ($points as $p)
	{
		$x_values[] = $p[0];
		$y_values[] = $p[1];
	}
	
	$f_x		= "0";
	$num_points = count($points);
	
	for ($i = 0; $i < $num_points; $i++)
	{
		$numerator = $denominator = "1";
		for ($j = 0; $j < $num_points; $j++)
		{
			if ($i === $j)
			{
				continue;
			}

			// ( $numerator * ($x - $x_values[$j]) ) % $prime
			$numerator = bc_unsigned_mod (
				bcmul( $numerator, bcsub($x, $x_values[$j])),
				$prime
			);

			// ( $denominator * ($x_values[$i] - $x_values[$j]) ) % $prime
			$denominator = bc_unsigned_mod ( 
				bcmul(
					$denominator, 
					bcsub(
						$x_values[$i], 
						$x_values[$j]
					)
				),
				$prime
			);
		}
					
		$lagrange_polynomial = bcmul(
			$numerator,
			mod_inverse($denominator,$prime)
		);

		// $prime + ( $f_x + ( $y_values[$i] * $lagrange_polynomial ) )
		$f_x = bc_unsigned_mod(
			bcadd(
				$prime,
				bcadd(
					$f_x,
					bcmul($y_values[$i], $lagrange_polynomial)
				)
			),
			$prime
		);
	}

	return $f_x;
}

/**
 * Extended Greatest Common Divisor
 * http://mathworld.wolfram.com/ExtendedGreatestCommonDivisor.html
 */

function egcd($a,$b)
{
	if (bccomp($a,"0") === BCMATH_COMP_EQUAL)
	{
		return [$b,"0","1"];
	}
	else
	{
		list($g,$y,$x)=egcd(bc_unsigned_mod($b,$a), $a);
		
		// [ $g, $x - (($b/$a)*$y), $y]
		return [$g, bcsub($x, bcmul(bcdiv($b,$a,"0"),$y)), $y];
	}
}

function mod_inverse($k, $prime)
{
	$k = bc_unsigned_mod($k, $prime);
	
	// if ($k < 0)
	if (bccomp($k,"0",0) === BCMATH_COMP_LEFT_SMALLER)
	{
		// egcd($prime, -$k)
		$r = egcd($prime, bcmul($k,"-1"))[2];
	}
	else
	{
		$r = egcd($prime, $k)[2];
	}

	return bc_unsigned_mod(bcadd($prime,$r),$prime);
}