/* globals.c - Global definitions for all other source files.
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

#include "nsb.h"

/*******************************************************************************
 * Global variable declarations.
 ******************************************************************************/

// The listener (promisc mode packet reader)
pcap_t *listener;

// The raw socket (used to send TCP RSTs or other raw pkt tricks)
int rawSocket;

// Verbose flag (tells us to output a bunch of extra information for debuging)
int verbose;

// the global packet buffer
unsigned char packetBuffer[65535];

// Misc. variable declarations.
char interface[255];
char configFile[1024];
char pidFile[1024];
ruleEntry ruleList[MAX_RULES];
int defaultPolicy;
int maxRule;
