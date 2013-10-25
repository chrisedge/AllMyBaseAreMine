#include <stdio.h>
#include <stdlib.h>

/*  The following is an implementation of rational numbers (fractions).
    That is, we create an ADT Fraction.  This ADT has data values and
    operators on the data values.  The programmer can thus use Fraction
    as though it were a built in C++ type.
*/

class Fraction {
    long a;
    long b;
    /*  The rational number a/b is represented by numerator a and
        denominator b.  a>=0 and b>0 is an invariant of the class;i.e.
        rationals are constructed with these properties and all operators
        maintain this property.  Member negative is true iff the
        rational a/b is negative.
    */
    char negative;
    void reduce();
    void minus() { negative^=1; } // unary minus operator
  public:
    Fraction(long m=0,long n=1);  // default constructor
    Fraction(const Fraction& x)
      { a=x.a; b=x.b; negative=x.negative; } // copy constructor
    Fraction& operator=(const Fraction &x); // assignment operator
    void print(char c=' ');
    ~Fraction() { ; } // really don't need destructor but have one anyway

    int is_zero() { return(a==0); }
    int is_one() { return(a==1 && b==1 && !negative); }
    int is_int() { return(b==1); }
    long floor()
      { long tmp=a/b;
        return((negative && !is_int()) ? tmp-1 : tmp); }
    long ceil()
      { return((negative) ? -a/b : (a+b-1)/b); }

    // Following are the obvious assignment operators
    Fraction& operator+=(const Fraction& x);
    Fraction& operator-=(const Fraction& x);
    Fraction& operator*=(const Fraction& x);
    Fraction& operator/=(const Fraction& x);

    friend int operator==(const Fraction& x,const Fraction& y);
    friend int operator<(const Fraction& x,const Fraction& y);
};

Fraction::Fraction(long m,long n)
{ if (!n) {
    fprintf(stderr,"Rational constructor failed; denominator 0\n");
    exit(1);
  }
  a=labs(m);
  b=labs(n);
  negative=(m<0 && n>0) || (m>0 && n<0);
  reduce();
}

void Fraction::reduce()
{ long temp,x=a,y=b;
  while (y) {
    temp=x%y;
    x=y;
    y=temp;
  }
  a/=x;
  b/=x;
}

Fraction&  Fraction::operator=(const Fraction& x)
// implementation of assignment operator
{  if (this != &x) { // always check assignment to self
     a=x.a;
     b=x.b;
     negative=x.negative;
   }
   return(*this);
}

Fraction& Fraction::operator+=(const Fraction& x)
{ long temp;
  temp=((negative) ? -a : a)*x.b+b*((x.negative) ? -x.a : x.a);
  b*=x.b;
  a=labs(temp);
  negative=(temp<0);
  reduce();
  return(*this);
}

Fraction& Fraction::operator-=(const Fraction& x)
{ Fraction temp=x;
  temp.minus();
  return(operator+=(temp));
}

Fraction& Fraction::operator*=(const Fraction& x)
{  a*=x.a;
   b*=x.b;
   negative^=x.negative;
   reduce();
   return(*this);
}

Fraction& Fraction::operator/=(const Fraction& x)
{ if (x.a==0) {
    fprintf(stderr,"Error: rational divide by 0\n");
    exit(1);
  }
  Fraction temp((x.negative ? -x.b : x.b),x.a);
  return(operator*=(temp));
}

Fraction operator+(const Fraction& x,const Fraction& y)
{ Fraction sum=x;
  sum+=y;
  return(sum);
}

Fraction operator-(const Fraction& x,const Fraction& y)
{ Fraction diff=x;
  diff-=y;
  return(diff);
}

Fraction operator*(const Fraction& x,const Fraction& y)
{ Fraction prod=x;
  prod*=y;
  return(prod);
}

Fraction operator/(const Fraction& x,const Fraction& y)
{ Fraction quot=x;
  quot/=y;
  return(quot);
}

inline int operator==(const Fraction& x,const Fraction& y)
{ return(x.a==y.a && x.b==y.b && x.negative==y.negative);
}

inline int operator!=(const Fraction& x,const Fraction& y)
{ return(!operator==(x,y));
}

inline int operator<(const Fraction& x,const Fraction& y)
{ Fraction temp=x-y;
  return(temp.negative);
}

inline int operator<=(const Fraction& x,const Fraction& y)
{ return(operator<(x,y) || operator==(x,y));
}

inline int operator>(const Fraction& x,const Fraction& y)
{ return(operator<(y,x));
}

inline int operator>=(const Fraction& x,const Fraction& y)
{ return(operator<(y,x) || operator==(x,y));
}

void Fraction::print(char c)
{  printf("%s%ld/%ld = ",negative ? "-" : "",a,b);
   if (a>=b)
     printf("%s%ld and %ld/%ld = ",negative ? "-" : "",a/b,a%b,b);
   double x=double(a)/b;
   if (negative)
     x=-x;
   printf("%g%c",x,c);
}


main()
{ Fraction half(1,2);
  Fraction x;
  x=-2+half;
  x.print('\n');
  Fraction sum(0);
  for (int i=1;i<=20;i+=2) {
    sum+=Fraction(1)/i;
    sum-=Fraction(1)/(i+1);
  }
  sum.print('\n');
  return(0);
}
