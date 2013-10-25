
#include <stdio.h>
#define SIZE 20

void compute ( int [] );

main ()
{
	int a[SIZE], j;

	for ( j = 0; j <= SIZE - 1; j++ )  /* Fill the array */
		a[j] = j;

	compute ( a );
}


void compute ( int array[] )
{
	int i, min = 0, max = 0, sum = 0, avg = 0;

	for( i = 0; i <= SIZE - 1; i++ )
	{
		if ( array[i] <= min )
			min = array[i];

		if ( array[i] >= max )
			max = array[i];

		sum += array[i];
	}

	avg = sum / SIZE;

	printf( "The min value was %i, max was %i, sum was %i, ",min,max,sum);
	printf( "and the average was %i.\n", avg );
}
