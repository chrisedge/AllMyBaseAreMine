/* interface.c - Handles the setup of the interface for sniffing and killing.
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

void setupListener(void)
{

/* setupListener - recieves: nothing
 *
 *             	   returns: nothing
 *
 * Called from main.c
 */

  pcap_t *localListener;

  // setup our capture with max packet size 65535, promisc, 10 second timeout
  localListener = pcap_open_live(interface, 65535, 1, 10000, packetBuffer);

  if (localListener == NULL)
  {
    printf("Error opening interface: %s for promisc mode.\n", interface);
    printf("pcap_open_live returned: %s\n", packetBuffer);
    exit(0);
  }

  listener = localListener;
  #ifdef __DEBUG
  printf("DEBUG: Listener created successfully.\n");
  #endif

}
void setupTalker(void)
{

/* setupTalker - recieves: nothing
 *
 *             	 returns: nothing
 *
 * Called from main.c
 */

  int s, on;

  on = 1;

  if ( (s = socket(AF_INET, SOCK_RAW, IPPROTO_RAW ) ) < 0)
  {
    printf("Error opening raw socket for writing.\n");
    pcap_close(listener);
    exit(10);
  }

  if ( setsockopt(s, IPPROTO_IP, IP_HDRINCL, &on, sizeof(on)) < 0)
  {
    printf("Error setting socket options.\n");
    pcap_close(listener);
    exit(10);
  }

  rawSocket = s;
  #ifdef __DEBUG
  printf("DEBUG: Talker created successfully.\n");
  #endif
}
