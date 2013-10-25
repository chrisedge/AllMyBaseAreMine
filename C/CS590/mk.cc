#include <iostream.h>
/* The four functions for string manipulation
   with main test program
*/

int stringCompare(char *s, char *t)
{
   if (s==t) return(0);
   for (int i=0; s[i] == t[i] ; i++)
	if (s[i] == '\0') return(0);
   if (s[i] > t[i]) return(1);
   if (s[i] < t[i]) return(-1); 
}

char *stringConcatenate(char *s, char *t)
{  
   for (int i=0; s[i]!='\0'; i++) ;
   for (int j=0; t[j]!='\0'; i++, j++)
     s[i] = t[j]; 
   s[i]='\0';     
   return(s);
}

char *stringString(char *s, char *t)
{  
   for (int i=0; s[i] != '\0'; i++) 
   {  
      if (s[i] == t[0]) 
      { 
	for (int j=0, k=i; s[k] == t[j] ; k++, j++);
        if (t[j] == '\0') return(s+i);
      } 
   }
   return(0);
}

char *replaceString(char *s, char *target, char *replace)
{  
   char *j=stringString(s,target); 
   for (int i=0; replace[i] != '\0'; i++)
	j[i] = replace[i]; 
}


void main()
{
	char str1[]="hello there, how are you";
	char str2[]="are";
	int n=stringCompare(str1, str2);
	if (n == 1) 
		cout << "String s is greater than string t\n";
	else if (n == 0) 
		cout << "String s is equal to string t\n";
	else 
		cout << "String s is less than string t\n";
	cout << "String s and t together is: "; 
	cout << (stringConcatenate(str1, str2)) << " \n\n";
	char str3[]="hello there, how are you";
        char str4[]="there";
	char str5[]="stuff";
	if (stringString(str3, str4) == 0) 
		cout << "String t, " << str4 << ", is not in string s," << str3 << "\n\n";
	else  cout << "String t, " << str4 << ", was found in string s," << str3 << "\n\n";
	cout << "The replacement string, " << str5 << ", replaced the target, " << str4 << ", in the string, " << str3 << "\n"; 
	cout << (replaceString( str3, str4, str5)) << " \n";
}

