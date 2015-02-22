<pre>
<?php
// Copyright (c) 2015 Steve Thomas <steve AT tobtu DOT com>

function parallel($hashType, $outlen, $in, $salt, $t_cost, $m_cost)
{
	$hashLength = strlen(mhash($hashType, ''));
	$upgradeLoops = $t_cost >> 16;
	$parallelLoops   = $t_cost & 0xffff;
	if ($m_cost > 18 || $parallelLoops > 30 || $upgradeLoops > 31 || $outlen > $hashLength)
	{
		return false;
	}
	// $upgradeLoops = 1, 2, 3, 4, 6, 8, 12, 16, ...
	if ($upgradeLoops == 0)
	{
		$upgradeLoops = 1;
	}
	else
	{
		$upgradeLoops = (3 - ($upgradeLoops & 1)) << (($upgradeLoops - 1) >> 1);
	}
	// $parallelLoops = 1, 2, 3, 4, 6, 8, 12, 16, ...
	if ($parallelLoops == 0)
	{
		$parallelLoops = 1;
	}
	else
	{
		$parallelLoops = (3 - ($parallelLoops & 1)) << (($parallelLoops - 1) >> 1);
	}
	$parallelLoops = 3 * 5 * 128 * $parallelLoops;

	// key = hash(hash(salt) || in)
	$key = mhash($hashType, mhash($hashType, $salt) . $in);

	// Work
	for ($i = 0; $i < $upgradeLoops; $i++)
	{
		// Clear work
		$work = str_repeat("\0", $hashLength);

		for ($j = 0; $j < $parallelLoops; $j++)
		{
			// work ^= hash(WRITE_BIG_ENDIAN_64(j) || key)
			$work ^= mhash($hashType, pack('N2', 0, $j) . $key);
		}

		// Finish
		// key = truncate(hash(hash(work || key)), outlen) || zeros(HASH_LENGTH - outlen)
		$key = substr(mhash($hashType, mhash($hashType, $work . $key)), 0, $outlen) . str_repeat("\0", $hashLength - $outlen);
	}

	return substr($key, 0, $outlen);
}

function PHS_SHA1($outlen, $in, $salt, $t_cost, $m_cost)
{
	return parallel(MHASH_SHA1, $outlen, $in, $salt, $t_cost, $m_cost);
}

function PHS_SHA256($outlen, $in, $salt, $t_cost, $m_cost)
{
	return parallel(MHASH_SHA256, $outlen, $in, $salt, $t_cost, $m_cost);
}

function PHS_SHA512($outlen, $in, $salt, $t_cost, $m_cost)
{
	return parallel(MHASH_SHA512, $outlen, $in, $salt, $t_cost, $m_cost);
}

function testVector($p, $s, $t, $m, $ol)
{
	if ($ol > 32)
	{
		echo "outlen:          {20,32,$ol}\n";
	}
	else if ($ol > 20)
	{
		echo "outlen:          {20,$ol,$ol}\n";
	}
	else
	{
		echo "outlen:          $ol\n";
	}
	echo "password hex:    " . bin2hex($p) . "\n";
	echo "salt hex:        " . bin2hex($s) . "\n";
	printf("t_cost:          0x%05x\n", $t);
	echo "m_cost:          0\n";
	echo "parallel-SHA1:   " . bin2hex(PHS_SHA1(min(20, $ol), $p, $s, $t, $m)) . "\n";
	echo "parallel-SHA256: " . bin2hex(PHS_SHA256(min(32, $ol), $p, $s, $t, $m)) . "\n";
	echo "parallel-SHA512: " . bin2hex(PHS_SHA512($ol, $p, $s, $t, $m)) . "\n\n";
}

testVector('password', 'salt', 0x00000, 0, 64);
testVector('password', 'salt', 0x00000, 0, 16);
testVector('passwordpassword', 'salt', 0x00001, 0, 64);
testVector('password', 'saltsalt', 0x00001, 0, 64);
testVector('password', 'salt', 0x00001, 0, 64);
testVector('password', 'salt', 0x00002, 0, 64);
testVector('password', 'salt', 0x00003, 0, 64);
testVector('password', 'salt', 0x00004, 0, 64);
testVector('password', 'salt', 0x10001, 0, 64);
testVector('password', 'salt', 0x20002, 0, 64);
testVector('password', 'salt', 0x30003, 0, 64);
testVector('password', 'salt', 0x40004, 0, 64);

testVector("\xff\0", "\x80", 0x00000, 0, 64);
testVector("\xff\0", "\x80", 0x10001, 0, 64);
testVector("\xff\0", "\x80", 0x20002, 0, 64);
testVector("\xff\0", "\x80", 0x30003, 0, 64);

for ($i = 0; $i < 32; $i++)
{
	$p = '';
	for ($j = 0; $j < $i; $j++)
	{
		$p .= chr($j);
	}
	testVector($p, strrev($p), 0x00002, 0, 64);
}
