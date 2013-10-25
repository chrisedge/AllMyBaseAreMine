/* packet.c - This file takes care of all packet decomposition and inspection.
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

void packetHandler(unsigned char *buffer, const struct pcap_pkthdr *pkt, const unsigned char *pktData)
{
  struct ether_header *eth;
  struct iphdr *iph;
  struct tcphdr *tcph;
  struct in_addr *ina, *inb;
  char outBuffer[2048];
  char *p;

  // Decompose the packet.
  // First we check the ether pkt type.
  eth = (struct ether_header *) pktData;
  if (ntohs(eth->ether_type) != ETHERTYPE_IP)
    return;

  // it's an IP packet.  Inspect the payload type.
  iph = (struct iphdr *) (pktData + sizeof(struct ether_header));

  #ifdef __DEBUG
  // dump the addresses
  ina = (struct in_addr *) &iph->saddr;
  printf("DEBUG packetHandler: iph->saddr = %s\n", inet_ntoa(*ina));
  inb = (struct in_addr *) &iph->daddr;
  printf("DEBUG packetHandler: iph->daddr = %s\n", inet_ntoa(*inb));
  #endif

  if ((iph->protocol != IPPROTO_TCP) && (iph->protocol != IPPROTO_IP))
    return;

  // it's a tcp packet.  Extract the sequence number, check the acl and
  // kill it if it's not on the acl.
  tcph = (struct tcphdr *) (pktData + sizeof(struct ether_header) + \
                            sizeof(struct iphdr));

  #ifdef __DEBUG
  // dump the ports and sequence numbers.
  printf("DEBUG packetHandler: tcph->source: %d\n", ntohs(tcph->source));
  printf("DEBUG packetHandler: tcph->dest: %d\n", ntohs(tcph->dest));
  printf("DEBUG packetHandler: tcph->seq: %d\n", ntohl(tcph->seq));
  printf("DEBUG packetHandler: tcph->ack_seq: %d\n", ntohl(tcph->ack_seq));
  #endif

  // check to see if this is a reset packet.
  if (tcph->rst)
    return;


  /*
  #ifdef __DEBUG
  printf("DEBUG: Passed to searchACL:\n");
  printf("DEBUG: iph->saddr: %d tcph->source: %d.\n", iph->saddr, \
	ntohs(tcph->source) );
  printf("DEBUG: iph->daddr: %d tcph->dest: %d.\n", iph->daddr, \
	ntohs(tcph->dest) );
  #endif
  */

  // search the acl for the action we need to take.
  // Pass the ports to searchACL as ntohs() conversions.
  if (searchACL(iph->saddr, ntohs(tcph->source), iph->daddr, \
      ntohs(tcph->dest)) == FALSE)
    return;

  #ifdef __DEBUG
  printf("DEBUG packetHandler: Searched ACL, result is TRUE.\n");
  #endif

  // we've searched the ACL and it's a packet we need to kill.
  // construct a killer (rst) tcp packet
  sendKillerPacket(iph->saddr, iph->daddr, tcph->source, tcph->dest, \
                   tcph->seq, tcph->ack_seq);

  /* Log the denied connection, but here we're only logging the kill of
   *  the initial connection. */
  if (tcph->syn && !tcph->ack) 
  {
    ina = (struct in_addr *) &iph->saddr;
    inb = (struct in_addr *) &iph->daddr;

  /* OK, this only appears to be a hack. inet_ntoa returns a pointer to
   * a location in static memory. This is why we were getting logging that
   * appeared wrong. See page 71 of UNP v.1. Thanks Paulie. 
   * So when we called inet_ntoa twice within the same sprintf call we
   * got a pointer to the same memory location. */

    p = outBuffer;
    sprintf(p, "DENY from %s:%d to ", inet_ntoa(*ina), \
    	  ntohs(tcph->source));
    p += strlen(p);
    sprintf(p, "%s:%d", inet_ntoa(*inb), ntohs(tcph->dest));
    syslog( LOG_NOTICE | LOG_DAEMON, outBuffer);
  }

  // HACK.
  // Src->seq = 1
  // Src->ack_seq = 0
  // Dest->seq = 40
  // Dest->ack_seq = 2
  //x Src->seq = 2
  //x Src->ack_seq = 41
  // Dest->seq = 41
  // Dest->ack_seq = 3

  // Fake Packet (Forged)
  // Dest->seq = (Src->ack_seq)
  // Dest->ack_seq = (Src->seq + 1)
}
