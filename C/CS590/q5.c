
#include <stdio.h>

int main ()
{
	int number, rightMost, sum;

	printf( "Enter the digit to add up: ");
	scanf ("%i", &number );

	while ( number > 0 )
	{
		rightMost = number % 10;
		sum += rightMost;
		number -= rightMost;
		number = number / 10;
	}

	printf( "The total is %i.\n", sum );
}

