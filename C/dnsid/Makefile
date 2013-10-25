#
# dnsid Primary Makefile
#


# Define the compiler to use
CC = gcc

# Define CFLAGS
CFLAGS = -Wall
OFLAGS = -O2

# Define extra includes
INCLUDES = -I/usr/local/include

# Define extra defines
#DEFINES = -DDEBUG

# Define external libraries
LIBS = ./libbind.a

# Define binary Targets
DNSIDBIN = dnsid

# Define the sources for each binary
DNSIDSRCS = dnsid.c
	  
#DNSIDHDRS = nsb.h

# Define the OBJECTS for each binary
DNSIDOBJS = dnsid.o
	  
# Define the binaries as a target
BINS = ${DNSIDBIN}

all: ${BINS}

${DNSIDBIN} : ${DNSIDOBJS}
	${CC} ${CFLAGS} ${OFLAGS} ${DNSIDOBJS} -o $@ ${LIBS}

.c.o:
	${CC} -c ${CFLAGS} ${OFLAGS} ${DEFINES} ${INCLUDES} $<

clean:
	rm -f *.o core ${BINS}

# setup our dependancy rules for the headers
${DNSIDOBJS} : ${DNSIDHDRS}
###############
