/* Lab 1, 1-10-95
	Chris St. Clair 
	Wed 12-2
*/

#include<iostream.h>

void factorialList(int f, int n)
/* Outputs a list of factorials,
   no return value
*/

{   int factorial(int n);
   	cout << "Factorial " << n << "= "
		<< f << "\n";
}

int factorial(int n, int i)
/* Computes the factorial and calls the
	output function factorialList,
	returns 0
*/

{	if (n<0) {
	cerr << n << " factorial is undefined.\n";
	return(0);
	}
	if (!n)
	   return(1);
	int f=n;
	for( ;n<=i;n++) {
		f=f*n;
		factorialList(f,n);
	  }
	return(0);
}

int main()
/* Calls function factorial, returns 0 */

{	int n=1, i;
	cout << "Enter the number to be factored: ";
        cin >> i;
	factorial(n,i);
	return(0);
}

