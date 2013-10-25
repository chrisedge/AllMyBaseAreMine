/* nsb.h - All encompassing include file. This is the main #include file which
 * all other source files point to.
 *
 * Authors: Mark Carey, Chris St. Clair, Paul Cardon
 * Copyright 1999 Wolfpak Enterprises, Ltd.
 * All Rights Reserved.
 *
 */

/*
 * Copyright (c) 1993, 1994, 1995, 1996, 1998
 *      The Regents of the University of California.  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that: (1) source code distributions
 * retain the above copyright notice and this paragraph in its entirety, (2)
 * distributions including binary code include the above copyright notice and
 * this paragraph in its entirety in the documentation or other materials
 * provided with the distribution, and (3) all advertising materials mentioning
 * features or use of this software display the following acknowledgement:
 * ``This product includes software developed by the University of California,
 * Lawrence Berkeley Laboratory and its contributors.'' Neither the name of
 * the University nor the names of its contributors may be used to endorse
 * or promote products derived from this software without specific prior
 * written permission.
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND WITHOUT ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, WITHOUT LIMITATION, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.
 */

#ifndef __NSB_H__

/*******************************************************************************
 * All the include lines.
 ******************************************************************************/

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <syslog.h>
#include <signal.h>
#include <fcntl.h>
#include <sys/socket.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <netinet/in.h>
#include <netinet/in_systm.h>
#include <netinet/ip.h>
#include <netinet/ip_icmp.h>
#include <netinet/tcp.h>
#include <arpa/inet.h>
#include <net/ethernet.h>
#include <pcap/pcap.h>

/*******************************************************************************
 * Common defines application wide.
 ******************************************************************************/

#define __BSD_SOURCE
//#define __DEBUG
#define APPLICATION_NAME                ("nsbd")
#define APPLICATION_VERSION             ("v0.9.1")
#define SYSLOG_APPLICATION_NAME         ("nsbd")

#define PARSE_ERROR  (-1)
#define PARSE_SUCCESS (0)
#define POLICY_ALLOW  (1)
#define POLICY_DENY   (0)
#define MAX_RULES     (1024)
#define MAXLINE       (1024)
#define FALSE (0)
#define TRUE  (1)
#define SI struct in_addr	/* a la Stevens */

// Port defines
#define LOW_START	(0)
#define LOW_END		(1023)
#define HIGH_START	(1024)
#define HIGH_END	(65535)

// For the checksumming.
#define CKSUM_CARRY(x) \
	(x = (x >> 16) + (x & 0xffff), (~(x + (x >> 16)) & 0xffff))

/*******************************************************************************
 * Data structures.
 ******************************************************************************/

// Policy rule data struct
typedef struct _ruleEntry {
  unsigned long hostA;
  unsigned long hostB;
  unsigned short portA_low;	/* These are for port ranges. */
  unsigned short portA_high;	/* For single ports they will be equal. */
  unsigned short portB_low;	
  unsigned short portB_high;	
  char policy;
} ruleEntry;

/*******************************************************************************
 * Global variable declarations declared extern, and defined in globals.c
 ******************************************************************************/

// The listener (promisc mode packet reader)
extern pcap_t *listener;

// The raw socket (used to send TCP RSTs or other raw pkt tricks)
extern int rawSocket;

// Verbose flag (tells us to output a bunch of extra information for debuging)
extern int verbose;

// the global packet buffer
extern unsigned char packetBuffer[65535];

// Misc. variable declarations.
extern char interface[255];
extern char configFile[1024];
extern char pidFile[1024];
extern ruleEntry ruleList[MAX_RULES];
extern int defaultPolicy;
extern int maxRule;

/*******************************************************************************
 * Function prototypes.
 ******************************************************************************/

// Generic
void init(void);
int daemon_init(void);
int parseConfig(void);
void setupListener(void);
void setupTalker(void);
void mainLoop(void);
void closeAll(int signalNo);
void packetHandler(unsigned char *buffer, const struct pcap_pkthdr *pkt, \
		   const unsigned char *pktData);
int searchACL(unsigned long src, unsigned short sp, unsigned long dest, \
	      unsigned short dp);
void sendKillerPacket(unsigned long saddr, unsigned long daddr, \
		      unsigned short source, unsigned short dest, \
		      unsigned long seq, unsigned long ack_seq);
unsigned short in_cksum(unsigned short *ptr, int len);
int do_checksum(u_char *buf, int protocol, int len);
int printUsage(char *argv0);

// logging prototypes (log.c)
void logOpen(void);
void logMessage(char *message);
void logCriticalMessage(char *message);
void logErrorMessage(char *message);
void logClose(void);

#endif
