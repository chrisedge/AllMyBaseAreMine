/* Chris St. Clair  */
/* CS 490 Project#2 */
/* May 6, 1996      */


#include <stdio.h>
#include <math.h>
#include <ctype.h>



void ldBill()
{
	int             i, j, k, calls, startTime = 0, len;
	char            day[10];
	float           cost[10], maxCost = 0.0, minCost = 0.0, length;

	printf("\nEnter the number of calls, no more than 10: ");
	scanf("%d", &calls);

	for (j = 1; j <= calls; j++) {
		printf("\nEnter the start of call %d and the length ", j);
		printf("in minutes: ");
		scanf("%d %f", &startTime, &length);
		printf("Now enter the day of the week the call was made: ");
		scanf("%s", day);

		/* Make sure it's all lowercase */

		len = strlen(day);
		for (i = 0; i < len; i++) {
			day[i] = tolower(day[i]);
		}

		/* switch() to the day and perform the calculations */

		switch (day[0]) {
		case 's':	/* day[0] == s, must be weekend */
			cost[j] = (.20 * length) - ((.20 * length) * .15);
			break;

		case 'm':
		case 't':
		case 'w':
		case 'f':
			if (startTime >= 800 && startTime <= 1700)	/* full price */
				cost[j] = .20 * length;
			if (startTime > 1700 && startTime < 2300)	/* 10% off */
				cost[j] = (.20 * length) - ((.20 * length) * .10);
			if (startTime >= 2300 || startTime < 800)	/* 15% off */
				cost[j] = (.20 * length) - ((.20 * length) * .15);
			break;

		default:
			printf("\nInvalid input.\n");
			break;
		}		/* end switch */

		if (cost[j] > maxCost)
			maxCost = cost[j];
		if (j == 1)
			minCost = maxCost;
		else if (cost[j] <= minCost)
			minCost = cost[j];

		printf("\nCost for call %d is %.2f.\n", j, cost[j]);
	}			/* end for */
	printf("The most expensive call cost %.2f.\n", maxCost);
	printf("The least expensive call cost %.2f.\n", minCost);
}



void ames()
{
	int             curSalary;

	printf("\nEnter your current salary as a whole number: ");
	scanf("%d", &curSalary);
	if (curSalary < 1000)
		curSalary += 100;
	else if (curSalary >= 1000 && curSalary < 3000)
		curSalary += 200;
	else
		curSalary += 300;
	printf("\nYour new salary will be %d.\n", curSalary);
}


void ctoptoc()
{
	char            choice[2];
	double          x, y, theta, r;

	printf("\nEnter 'c' to convert from C to P, or 'p' ");
	printf("for vice versa: ");
	scanf("%s", choice);
	if (choice[0] == 'c') {	/* C to P */
		printf("\nEnter the x and y values like 'x,y': ");
		scanf("%lf,%lf", &x, &y);
		r = sqrt((x * x) + (y * y));
		theta = atan2(y, x);
		printf("The coordinates are r = %lf, and theta = %lf.\n", r, theta);
	}
	if (choice[0] == 'p') {	/* P to C */
		printf("\nEnter the r and theta values like 'r,theta': ");
		scanf("%lf,%lf", &r, &theta);
		x = r * cos(theta);
		y = r * sin(theta);
		printf("The coordinates are x = %lf, and y = %lf.\n", x, y);
	}
}



void doMenu()
{
	/*
	 * I added the doMenu function based on some code from Dr. Gillam's
	 * class. It makes it much easier to test the functions
	 * interactively.
	 */

	printf("\n1. Show this menu.\n");
	printf("2. Test the Long Distance Bill Program.\n");
	printf("3. Test the Ames Salary Program.\n");
	printf("4. Test the Cartesian to Polar Program.\n");
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
			ldBill();
			doMenu();
			break;
		case '3':
			ames();
			doMenu();
			break;
		case '4':
			ctoptoc();
			doMenu();
			break;
		case '5':
			fflush(stdin);
			return (0);
		default:
			printf("Enter 1-6 please.\n");
			doMenu();
			break;
		}
	}
}
