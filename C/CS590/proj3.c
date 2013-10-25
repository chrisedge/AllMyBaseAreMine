/* Chris St. Clair  */
/* CS 490 Project#3 */
/* June 1, 1996     */


#include <stdio.h>
#include <math.h>

#define G 32.2
#define PI 3.14
#define SIZE 20

void projectile ( int num_of_tries ) {

	float dist, angle, angle_r, velo, traveled_dist, tmp, percent;

	while ( num_of_tries > 0 ) {
		printf( "\nYou have %i chances left.\n", num_of_tries );
		printf( "Enter distance to the target: ");
		scanf ("%f", &dist );
		printf( "Enter the firing angle: ");
		scanf ("%f", &angle );
		printf( "Enter the initial velocity: ");
		scanf ("%f", &velo );

		angle_r = angle * ( PI / 180 );

		traveled_dist = ( ( velo * velo ) * ( sin( 2 * angle_r )) ) / G;

		if ( traveled_dist > dist ) {
			tmp = dist;
			dist = traveled_dist;
			traveled_dist = tmp;
			}

		percent = ( 1 - ( traveled_dist / dist ) ) * 100 ;

		if ( percent <= 0.1 ) {
			printf( "G R E A T  S H O T !!! You win!\n" );
			num_of_tries = 0;
			}
		else {
			printf( "Too bad. You missed by %f\%.\n", percent );
			num_of_tries--;
			}
		}

		printf( "Game Over.\n" );
}

/*************Begin block of functions for arrayMenu *******************/

	int a[SIZE];   /* Globals for all */
	char c[2];    /* functions called by arrayMain () */


void arrayMenu () {

	printf("\nChoices:\n");
	printf("1. Show this menu.\n");
	printf("2. Create and fill the array.\n");
	printf("3. Print the array.\n");
	printf("4. Sort.\n");
	printf("5. Search\n");
	printf("6. Quit.\n\n");
}

void printArray () {

	int i;			/* Local */

	for ( i = 0; i <= SIZE - 1; i++ ) {
		if ( i % 20 == 0 )
			printf( "\n" );
		printf( "%i ", a[i] );
		}
	printf( "\n" );
}

void createArray () {

	int j;		/* Local */

	for ( j = 0; j <= 4; j++ )  /* Fill the array */
		a[j] = j + 1;
	for ( j = 5; j <= 9; j++ )
		a[j] = 0 - j;
	for ( j = 10; j <= SIZE - 1; j++ )
		a[j] = 0;

	printf( "The array has been created and filled.\n" );
	printArray ();
}

void sort () {		/* The good old bubble method */

	 int count, i, tmp;

	 for ( count = 1; count <= SIZE - 1; count++ ) {
		for ( i = 0; i <= SIZE - 2; i++ ) {
			if ( a[i] > a[i + 1] ) {
				tmp = a[i];
				a[i] = a[i + 1];
				a[i + 1] = tmp;
				}
			}
		}

	 printArray ();
	 printf( "A bubble sort.\n" );
}

void search () { 	/* Get the key and look, uses boolean to decide */

	int i, key, found; 		/* Local variables */

	printf( "Enter an integer to search for: " );
	scanf ( "%i", &key );

	for( i = 0; i <= SIZE - 1; i++ ) {
		if ( a[i] == key ) {
			printf( "Found %i at position %i.\n", key, i );
			i = SIZE - 1;
			found = 1;
			}
		else
			found = 0;
		}

	if ( ! ( found ) )
		printf( "Did not find %i.\n", key );
}

int arrayMain () {

	arrayMenu();
	for (;;) {
		printf("\nCommand: ");
		scanf("%s", c);
		switch (c[0]) {
		case '1':
			arrayMenu();
			break;
		case '2':
			createArray();
			arrayMenu();
			break;
		case '3':
			printArray();
			arrayMenu();
			break;
		case '4':
			sort();
			arrayMenu();
			break;
		case '5':
			search();
			arrayMenu();
			break;
		case '6':
			fflush(stdin);
			return (0);
		default:
			printf("Enter 1-6 please.\n");
			arrayMenu();
			break;
		}
	}
}

/*********** End block of funcitons for arrayMenu ************************/

void printSvi ()
{
	printf( "#!/bin/sh\nif test -f \"$1\"\nthen\n" );
     	printf( "cp $1 $HOME/$1.tmp\n$EDITOR $1\n" );
	printf( "rm -i $HOME/$1.tmp\nelse\n" );
	printf( "echo \"File not found, try again.\"\n" );
	printf( "fi\nexit 0\n$_\n" );
}

void doMenu()
{
	/*
	 * I added the doMenu function based on some code from Dr. Gillam's
	 * class. It makes it much easier to test the functions
	 * interactively.
	 */

	printf("\n1. Show this menu.\n");
	printf("2. Test the Projectile Game.\n");
	printf("3. Test the Array Program.\n");
	printf("4. Print the svi-script.\n");
	printf("5. Quit.\n\n");
}


int main()
{
	/* main() basically goes into an infinite for loop */
	/* that calls doMenu(), and then will switch()     */
	/* to the number entered by the user to call the   */
	/* proper function, display the menu, or return()  */

	char            ch[2];

	doMenu();
	for (;;) {
		printf("\nCommand: ");
		scanf("%s", ch);
		switch (ch[0]) {
		case '1':
			doMenu();
			break;
		case '2':
			projectile(5);
			doMenu();
			break;
		case '3':
			arrayMain();
			doMenu();
			break;
		case '4':
			printSvi();
			doMenu();
			break;
		case '5':
			fflush(stdin);
			return (0);
		default:
			printf("Enter 1-5 please.\n");
			doMenu();
			break;
		}
	}
}
