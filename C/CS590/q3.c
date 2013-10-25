
#include <stdio.h>
#define SIZE 20

int search ( int [], int );

void main ()
{
	int a[SIZE], j, pos, neg, zero;

	for ( j = 0; j <= 4; j++ )  /* Fill the array */
		a[j] = j + 1;
	for ( j = 5; j <= 9; j++ )
		a[j] = 0 - j;
	for ( j = 10; j <= SIZE - 1; j++ )
		a[j] = 0;

	printf( "There were %i positive values.\n", ( search ( a, 1  ) ) );
	printf( "There were %i negative values.\n", ( search ( a, -1 ) ) );
	printf( "There were %i zero values.\n",     ( search ( a, 0  ) ) );
}


int search ( int array[], int key )
{
	int i, pos = 0, zero = 0, neg = 0;

	if ( key == 1 )
		{
			for( i = 0; i <= SIZE - 1; i++ )
			{
				if ( array[i] > 0 )
					pos++;
			}
		return ( pos );
		}

	if ( key == 0 )
		{
			for( i = 0; i <= SIZE - 1; i++ )
			{
				if ( array[i] == 0 )
					zero++;
			}
		return ( zero );
		}

	if ( key == -1 )
		{
			for( i = 0; i <= SIZE - 1; i++ )
			{
				if ( array[i] < 0 )
					neg++;
			}
		return ( neg );
		}
}
