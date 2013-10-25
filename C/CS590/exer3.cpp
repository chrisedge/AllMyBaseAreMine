#include <iostream.h>



void showBits(unsigned);
unsigned getBits(unsigned,int,int);
unsigned setBits(unsigned,int,int,unsigned);
unsigned invertBits(unsigned,int,int);
unsigned leftRotateBits(unsigned,int);
unsigned rightRotateBits(unsigned,int);
void showMenu();



main()

{ unsigned int x=1,y;
	int n,p;
	char ch;
	showMenu();
	for (;;) {
		cout << "\nCommand:";
		cin >> ch;
		switch(ch) {
			case '1':
			showMenu();
			break;
		case '2':
			cout << "x = ";
			showBits(x);
			break;
		case '3':
			cout << "Enter an unsigned integer x: ";
			cin >> x;
			cout << "x = ";
			showBits(x);
			break;
		case '4':
			cout << "Enter the start bit position p and the length n: ";
			cin >> p; cin >> n;
			cout << "The specified bits right adjusted: ";
			showBits(getBits(x,p,n));
			break;
		case '5':
			cout << "Enter the start bit position p and the length n: ";
			cin >> p; cin >> n;
			cout << "Now the replacement bits right adjusted: ";
			cin >> y;
			cout << "The changed x: ";
			showBits(setBits(x,p,n,y));
			break;
		case '6':
		        cout << "Enter the start bit position p
			and the length n: ";
        		cin >> p; cin >> n;
        		cout << "The specified bit sequence inverted: ";
        		showBits(invertBits(x,p,n));
        		break;
		case '7':
			cout << "Enter the rotation count: ";
			cin >> n;
			cout << "x rotated left by " << n << " positions: ";
			showBits(leftRotateBits(x,n));
			break;
		case '8':
			cout << "Enter the rotation count: ";
			cin >> n;
			cout << "x rotated right by " << n << " positions: ";
			showBits(rightRotateBits(x,n));
			break;
		case '9':
			return(0);
		default:
			cout << "Invalid choice!!\n";
	} /* end of switch */
  }/* end of for loop */
}


void showMenu()

{  cout << "\t1\tShow this menu.\n";
	cout << "\t2\tShow x as a bit string.\n";
	cout << "\t3\tEnter a new x value.\n";
	cout << "\t4\tGet a sequence of bits from x.\n";
	cout << "\t5\tSet a sequence of bits of x.\n";
	cout << "\t6\tInvert a sequence of bits of x.\n";
	cout << "\t7\tRotate x to the left.\n";
	cout << "\t8\tRotate x to the right.\n";
	cout << "\t9\tQuit.\n\n";
}



void showBits(unsigned n)

/*  Outputs the bits of n from high order to low order.

*/

{ 	for (int mask=1;mask>0;mask<<=1)
		;

	/* now mask=100... */

	cout << ((mask & n) ? '1' : '0');

  /* We now want to make mask = 0100..  On first thought all we need do

     is shift mask to the right by 1; i.e. mask>>1.  However, if the right

     shift is arithmetic (and Borland C++ has arithmetic right shift), the

     result would be 1100..  Hence the need for the complicated

     initialization in the following for loop:

   */

	int count=2;
	for (mask=(mask>>1) & ~mask /* mask now 01000.. */;mask;mask>>=1,count++) {
		cout << ((mask & n) ? '1' : '0');
		if (!(count & 3))  /* i.e. count mod 4 == 0 */
			cout << ' ';
	}
	cout << "\n";
}



unsigned getBits(unsigned x,int p,int n)

/*  Computes and returns the n bits of x beginning at position p

    right adjusted; i.e. the low order n bits of the return are the

    specified bits of x.  Bit positions are 0 for the low order bit,

    1 for the next bit to the left, etc; i.e. bit position p is

    the coefficient of 2^p in the binary representation of x.

*/

{ 	return((x>>(p-n+1)) & ~(~0<<n));

   /*  The specified n bits of x are at bit positions p through and including

       p-(n-1).  Thus shifting x to the right by amount p-n+1 puts these

       bits and all higher order bits to the extreme right.  ~0<<n =

       11..100..0 where there are exactly n 0's to the right.  Hence

       ~(~0<<n) = 00..011..1 where there are exactly n 1's to the right.

       "Clearly" then the return value is correct.

   */

}



unsigned setBits(unsigned x,int p,int n,unsigned y)

/*  This function returns x with the n bits of x beginning at position p

    set to the low order n bits of y.  All other bits of x are left unchanged.

*/

{ 	unsigned mask=~(~0<<n);
	int shift_count=p-(n+1);
	return((x & ~(mask<<shift_count)) | ((y & mask) << shift_count));

	/* You determine that this is correct. */
}



unsigned invertBits(unsigned x,int p,int n)

/*  This function inverts (changes 0 to 1 and 1 to 0) the n bits of x

	 beginning at position p (other bits of x left unchanged), and returns

	 the result.

	 Hint: For a bit b, b^0 = b and b^1 is b inverted.

*/

{

	unsigned mask=~(~0<<n);
	return(x ^ (mask << (p-n+1)));

	/* of course this isn't only a stub */

}



unsigned leftRotateBits(unsigned x,int n)
/*  This function returns x rotated to the left by n positions; i.e.
    n single left rotations of x.  A left rotation:  for example, a
    left rotation of 6 bits abcdef yields bcdefa.  Similarly for k bits.
*/
{
        if (n > 16)
                n-=16;
        return((x<<n) | (x>>(16-n)));
}

unsigned rightRotateBits(unsigned x,int n)

/*  This function returns x rotated to the right by n positions; i.e.

	 n single right rotations of x.  As for left rotations, bits

	 abcdef rotated right by 1 position yields fabcde.

*/
{	if (n > 16)
		n-=16;
	return((x>>n) | (x<<(16-n)));
}

