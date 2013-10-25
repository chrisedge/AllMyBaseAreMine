    /* Chris St. Clair  */
  /* CS 490 Project#1 */
 /* April 23, 1996   */


#include <stdio.h>
#include <math.h>
#define HR 3600 	  	    /* Define the hour     */
				   /* to be 3600 seconds  */
				  /* for time ()         */

#define FVNIN 0.5555      /* These are for ftoctof () */
#define NINFV 1.8

#define PI 3.14159       /* These are for lsa () */
#define THIRD 0.3333

void time ()
{   			    /* time() asks the user for an       */
			   /* amount of seoconds that will be   */
			  /* converted to the proper number of */
			 /* hours, minutes, and seconds. I    */
			/* used the ? operator to figure if  */
		       /* the words should be singular or   */
		      /*  plural.                          */

        int ts;

	printf( "\nEnter the number of total seconds: ");
 	scanf( "%d", &ts );

	printf( "\nIn %d second%s: %d hour%s, %d minute%s, and %d second%s.\n",
			ts, ( ts == 1 ) ? "" : "s",
			( ts / HR ), ( ts / HR ) == 1 ? "" : "s",
			(( ts % HR ) / 60 ), (( ts % HR ) / 60 ) == 1
			? "" : "s", (( ts % HR ) % 60 ), (( ts % HR )
			% 60 ) == 1 ? "" : "s" );
}


void dayOfTheWeek ()
{
	    /* dayOfTheWeek() takes a Julian day and a year from the user */
	   /* and then figures out the day of the week that day is.      */
	  /* The days are numbered 0-6 starting with Saturday. I used   */
	 /* a double subscripted array to output the day based on      */
	/* the int caclculated.                                       */

    int day, year, weekDay;
    char ssmtwrf[7][10] = {"Saturday","Sunday","Monday","Tuesday","Wednesday"
			  ,"Thursday","Friday"};

	printf( "\nEnter the Julian day and the year: ");
	scanf ( "%d %d", &day, &year );
	weekDay = (( year + day  + ( year - 1 / 4 ) - ( year - 1 / 100 ) +
		  ( year - 1 / 400 )) % 7 );
	printf( "The day of the week is a %s.\n", ssmtwrf[weekDay] );
}


void ctoftoc ()
{
            /* ctoftoc() takes a temperature from the user and */
  	   /* a char, f or c, then calculates from f to c, or */
          /* c to f based on the char input.                 */

	float temp; char scale[2];

	printf( "\nEnter the temperature and the scale (f or c): ");
	scanf ( "%f %s", &temp, scale );
	if ( scale[0] == 'f' ) 
		printf( "\n%f degrees F is %f degrees C.\n", temp, 
                        ( FVNIN * ( temp -32 ) ) );
	else 
		printf( "\n%f degrees C is %f degrees F.\n", temp, 
                        ( ( NINFV * temp ) + 32 ) );
	
}

void lsa ()
{
          /* lsa() takes the radius and height of a right circular */
         /* cone from the user and computes the volume and        */
        /* lateral surface area.                                 */
   
   float r, h;

   printf( "\nEnter the radius and volume: ");
   scanf ( "%f %f", &r, &h );

   printf( "\nThe volume is %f.\n", ( THIRD * PI * ( r * r ) * h ) );
   printf( "The lateral surface area is %f.\n", 
           ( PI * r * ( sqrt( ( r * r ) + ( h * h ) ) ) ) ); 
}

void doMenu()
{  
   /* I added the doMenu function based on some code from */
  /* Dr. Gillam's class. It makes it much easier to test */
 /* the functions interactively.                        */

   printf( "\n1. Show this menu.\n" );
   printf( "2. Test the seconds converter.\n" );
   printf( "3. Test the Julian Date program.\n" );
   printf( "4. Test the temperature converter.\n" );
   printf( "5. Test the lateral surface area program.\n" );
   printf( "6. Quit.\n\n" );
}


int main ()
{
    /* main() basically goes into an infinite for loop */
   /* that calls doMenu(), and then will switch()     */
  /* to the number entered by the user to call the   */
 /* proper function, display the menu, or return()  */

	char ch[2];
	doMenu();
	for (;;) {
		printf( "\nCommand: ");
		scanf( "%s", ch );
		switch(ch[0]) {
			case '1':
				doMenu ();
				break;
			case '2':
				time ();
				doMenu ();
				break;
			case '3':
				dayOfTheWeek ();
				doMenu ();
				break;
			case '4':
				ctoftoc ();
				break;
            		case '5':
				lsa ();
                                doMenu ();
				break;
			case '6':
				return (0);
			default:
				printf( "Enter 1-6 please.\n" );
				doMenu ();
				break;
		}
	}
}   /* end of main */
