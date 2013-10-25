#
# NSB Primary Makefile
#


# Define the compiler to use
CC = cc

# Define CFLAGS
CFLAGS = -Wall
OFLAGS = -O2

# Define extra includes
INCLUDES = -I/usr/local/include

# Define extra defines
#DEFINES = -DDEBUG

# Define external libraries
LIBS = -lpcap

# Define binary Targets
NSBBIN = nsbd
#NSBKEY = nsb_keymaster

# Define the sources for each binary
NSBSRCS = globals.c check_acl.c interface.c ip_common.c killer.c log.c main.c \
	  parse_acl.c packet.c
	  
#NSBKEYSRCS = license.c keymain.c
NSBHDRS = nsb.h

# Define the OBJECTS for each binary
NSBOBJS = globals.o check_acl.o interface.o ip_common.o killer.o log.o main.o \
	  parse_acl.o packet.o
	  
#NSBKEYOBJS = license.o keymain.o

# Define the binaries as a target
BINS = ${NSBBIN}  ${NSBKEY}

all: ${BINS}

${NSBBIN} : ${NSBOBJS}
	${CC} ${CFLAGS} ${OFLAGS} ${NSBOBJS} -o $@ ${LIBS}

#${NSBKEY} : ${NSBKEYOBJS}
#	${CC} ${CFLAGS} ${OFLAGS} ${NSBKEYOBJS} -o $@ ${LIBS}

.c.o:
	${CC} -c ${CFLAGS} ${OFLAGS} ${DEFINES} ${INCLUDES} $<

clean:
	rm -f *.o *~ core ${BINS}

# setup our dependancy rules for the headers
${NSBOBJS} : ${NSBHDRS}

###############
