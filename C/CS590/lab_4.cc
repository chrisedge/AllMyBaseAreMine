/* Lab 4 2-8-95 Chris St. Clair
	Wed 12-2 */

#include <iostream.h>

const int SIZE = 100;
int stringCompare(char *, char *);
char * stringConcatenate(char *, char *);
char * stringString(char *, char *);
char * replaceString(char *, char *, char *);



void main()
/* Tests all functions, return is void. */
{	char line1[]= "Line one", line2[]= "Line two";

	/* Test for stringCompare */

	int n;
	n = (stringCompare(line1, line2));
	if (n)
		cout << "\nThe difference between Line one"
		<< "and Line two is: " << n << "\n";
	n = (stringCompare(line2, line1)); /* >0 */
	if (n)
		cout << "\nThe difference between Line one"
		<< "and Line two is: " << n << "\n"; /* <0 */
	n = (stringCompare(line1, line1));  /* = */
	if (n)
		cout << "\nThe difference between Line one"
		<< "and Line two is: " << n << "\n";
		cout << "\nLine one and Line two are the same.\n";

	/* Test for stringConcatenate */
	stringConcatenate(line1, line2);
	cout << line1;

	/* Test for stringString */
	char line3[]= "Line", line4[]= "not";
	char * tmp;
	tmp = (stringString(line1, line3)); /* Success */
	cout << "\nThe first occurence of '" << line3
	<< "' in  '" << line1 << "' is " << * tmp << ", at "
	<< &tmp << "\n";
	stringString(line1, line4); /*  Failure */
	cout << "\nFailure.\n";

	/* Test for replaceString */
	char target[]= "one", replace[]= "other";
	replaceString(line1, target, replace);
	cout << line1;
	
} /* End main */


int stringCompare(char * s, char * t)
/* This function returns the sum and difference of two strings */
{	int sum1 = 0, sum2 = 0, i = 0, j = 0;
	while (s[i] != '\0')
		sum1 += s[i++];
	while (t[j] != '\0')
		sum2 += t[j++];
	cout << "\nSum of line1= " << sum1 << " Sum of line2= " << sum2;
	return(sum1 - sum2);
}


char * stringConcatenate(char s[SIZE], char * t)
/* This function returns two strings cat'ed together */ 
{	int i = 0, j;
	while (s[i] != '\0')
		s[i++];
   for (i,j=0; t[j] != '\0'; i++,j++)
	s[i] = t[j];
	s[i] = '\0';
	return(s);
}


char * stringString(char * s, char *t)
/* This function returns the first occurence of string t in string s */
/* The address is also given in main. */
{  char * tmp;
	for (int i = 0; s[i] != '\0' ; i++) {
		if (s[i] != t[0])
			continue;
		* tmp = s[i];
		for ( int j = i + 1, k = 1; s[j] == t[k] && t[k] != '\0';
j++, k++)
			{}
		if (t[k] == '\0')
		 return(tmp);
		else if (s[j] == '\0')
			return(0);
	}
	return(0);
}


char * replaceString(char * s, char * target, char * replace)
/* This function should return the string s with string replace replacing */
/* string target. I could not get this to work due to a 'Null pointer error' */
{ 
       char *tmp=stringString(s, target);
	for (int i=0; replace[i] != '\0'; i++)
        tmp[i] = replace[i];	
	return(s);
}
