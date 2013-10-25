#include <iostream.h>
#include <ctype.h>


void outputDecimal(int base, char c)
{	char ch=(c < 'A') ? c - '0' : c - 'A' + 10;
	cout << ch << "\n" << base;
}






int readInteger(char c)
{  int base;
	/* Put some loop here */ /* Read input till ? */
	cin.get(c);
	if (c == '0') {         /* Check first for 0 */
		cin.get(c);
		if (c == 'x')        /* Check second for x */
			base=16;          /* If x, must be base 16 */
		else
			base=8;           /* If not, must be 8 */
		}
		else
			base=10;             /* First char wasn't 0, base 10 */
	outputDecimal(base,c);
	return(0);
}




int main()
{
	char c;
	cout << "\nEnter a number, ^Z to end: ";
	readInteger(c);
	return(0);
}
