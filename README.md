Secretsharer

A port of https://github.com/blockstack/secret-sharing to PHP. This implementation of the Shamir Secret Sharing Scheme allows you to take 
a secret and split it up into N shares for split control of that secret. The secret can be recovered by providing $threshold amount of shares. 
You determine the $threshold when generating the shares. 

## Sample Usage

### Splitting a secret value into shares

```php
<?php

use Secretsharer\PlaintextSecretSharer;

$sharer = new PlaintextSecretSharer();

// Generate 4 shares and require 2 for recovering the secret
var_dump($sharer->splitSecret("ThisIsMySecretValue",2,4));

/*
Result: 

array(4) {
  [0]=> string(34) "1-1949b4dbf224d3a457f52b9c0d998657"
  [1]=> string(33) "2-911b48f7117cdd76466fef30d2fa918"
  [2]=> string(34) "3-78d9b442f00ac80a70d8d24a0cc5cbd8"
  [3]=> string(34) "4-68a1b3f66efdc23d7d4aa5a10c5bee99"
}
*/
```

### Recovering a secret from 2 shares

```php
<?php

use Secretsharer\PlaintextSecretSharer;

$sharer = new PlaintextSecretSharer();
var_dump(
	$sharer->recoverSecret([
		"1-1949b4dbf224d3a457f52b9c0d998657", 
		"4-68a1b3f66efdc23d7d4aa5a10c5bee99"
	])
);

// Result: string(19) "ThisIsMySecretValue"
```