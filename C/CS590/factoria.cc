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
	<< f;
}

int factorial(int n)

{	if (n<0) {
	cerr << n << " factorial is undefined.\n";
	return(0);
	}
	if (!n)
	   return(1);
	int f=n;
	while (--n) {
          f=f*n;
          factorialList(f,n);
	  }
	return(0);
}

int main()

{	int n=1;
	factorial(n);
	return(0);
}

