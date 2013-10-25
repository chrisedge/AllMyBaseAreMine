#include <stdio.h>


main()

{

	int num = 0;

	printf("Cool. Print the: \n");
	printf("number\t");
	printf("square\t");
	printf("cube\t\n");
        
	while (num <= 10) {
		printf("%d\t", num);
		printf("%d\t", num * num);
		printf("%d\t\n", num * num * num);
		num = num +1;
		}
	return 0;
}
