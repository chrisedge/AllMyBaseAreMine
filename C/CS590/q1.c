
#include <stdio.h>

main ()
{   int i, j, count;

	printf( "\n1 1\n" );

	for( i = 2; i <= 6; i++ )
	{
		for( count = 1; count <= 2; count++ )
		{
			printf( "%i ", i );

			if ( i == 6 )
			{
				printf( "0\n" );
				return;
			}

		}

		for ( j = i - 1; j >= 1; j-- )
		{
			printf( "%i ", j );
		}

		printf( "\n" );

	}
}