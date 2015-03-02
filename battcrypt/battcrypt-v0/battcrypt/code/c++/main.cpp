// Copyright (c) 2014 Steve Thomas <steve AT tobtu DOT com>

#include <stdio.h>
#include "battcrypt.h"

void printHash(uint64_t *hash)
{
	printf(
		"%016" PRIx64 "%016" PRIx64 "%016" PRIx64 "%016" PRIx64 "\n"
		"%016" PRIx64 "%016" PRIx64 "%016" PRIx64 "%016" PRIx64 "\n",
		WRITE_BIG_ENDIAN_64(hash[0]),
		WRITE_BIG_ENDIAN_64(hash[1]),
		WRITE_BIG_ENDIAN_64(hash[2]),
		WRITE_BIG_ENDIAN_64(hash[3]),
		WRITE_BIG_ENDIAN_64(hash[4]),
		WRITE_BIG_ENDIAN_64(hash[5]),
		WRITE_BIG_ENDIAN_64(hash[6]),
		WRITE_BIG_ENDIAN_64(hash[7]));
}

void benchmark(const void *in, size_t inlen, const void *salt, size_t saltlen, unsigned int t_cost, unsigned int m_cost)
{
	uint64_t out[8];
	TIMER_TYPE s, e;

	TIMER_FUNC(s);
	PHS(out, sizeof(out), in, inlen, salt, saltlen, t_cost, m_cost);
	TIMER_FUNC(e);
	printf("battcrypt pass: %s salt: %s t:% 2u, m:% 2u: %0.4f ms\n", in, salt, t_cost, m_cost, 1000.0 * TIMER_DIFF(s, e));
	// printHash(out);
}

int main()
{
	uint64_t out[8];

	PHS(out, sizeof(out), "password", 8, "salt", 4, 0, 0);
	printf("hash: ");
	printHash(out);

	for(int t_cost = 1; t_cost <= 13; t_cost++) {
		benchmark("passwordpasswordpasswordpassword", 32, "saltsaltsaltsalt", 16, t_cost, 3);
	}

	// benchmark("password", 8, "salt", 4, 1, 3);
	// benchmark("password", 8, "salt", 4, 1, 4);
	// benchmark("password", 8, "salt", 4, 1, 5);
	// benchmark("password", 8, "salt", 4, 1, 6);
	// benchmark("password", 8, "salt", 4, 1, 7);
	// benchmark("password", 8, "salt", 4, 1, 8);
	// benchmark("password", 8, "salt", 4, 1, 9);
	// benchmark("password", 8, "salt", 4, 1, 10);
	// benchmark("password", 8, "salt", 4, 1, 11);
	// benchmark("password", 8, "salt", 4, 1, 12);
	// benchmark("password", 8, "salt", 4, 1, 13);

	return 0;
}
