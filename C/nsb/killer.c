/* killer.c - traffic killer code. This generates the TCP resets.
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

// Send the rst packet (don't worry about forging the mac address).
void sendKillerPacket(unsigned long saddr, unsigned long daddr, \
                      unsigned short source, unsigned short dest, \
                      unsigned long seq, unsigned long ack_seq)
{

/* sendKillerPacket - recieves: saddr = a source IP in network byte order
 *                       daddr = a destination IP in network byte order
 *                       source = a source port in network byte order
 *                       dest = a destination port in network byte order
 *
 *             	      returns: nothing
 * 
 * Called from packet.c
 */

#define BUFFER_SIZE (sizeof(struct iphdr) + sizeof(struct tcphdr))
  char buffer[BUFFER_SIZE];
  struct iphdr *iph;
  struct tcphdr *tcph;
  struct sockaddr_in dst;
  struct in_addr attackDest;
  int i;

#ifdef __DEBUG
printf("DEBUG sendKillerPacket: Sending killer pkt.\n");
#endif

  iph = (struct iphdr *)buffer;
  tcph = (struct tcphdr *) (buffer + sizeof(struct iphdr));

  // clear the buffer space
  for(i = 0; i < BUFFER_SIZE; i++)
  {
    buffer[i] = 0;
  }

  // fill in the ip header
  iph->ihl = sizeof(struct iphdr) >> 2;
  iph->version = 4;
  iph->tos = 0;
  iph->tot_len = BUFFER_SIZE;
  iph->id = htons(8192);
  iph->frag_off = 0;
  iph->ttl = 251;
  iph->protocol = IPPROTO_TCP;
  iph->check = 0; // kernel fills in at tx time. right.... - Osc
  iph->saddr = daddr; // forge the sender as the dest
  iph->daddr = saddr; // set the dest to the sender

  // now forge the tcp rst packet
  tcph->source = dest;
  tcph->dest = source;
  tcph->seq = ack_seq;
  tcph->ack_seq = htonl(ntohl(seq) + 1); // add one to the sequence number
  tcph->rst = 1;
  tcph->window = 0;
  tcph->check = 0;  // kernel does not fill this in.  we fill in later.
  tcph->urg_ptr = 0;

  if (do_checksum(buffer, IPPROTO_IP, sizeof(struct iphdr)) == -1)
	fprintf(stderr, "Error calling do_checksum for IP.\n");

  if (do_checksum(buffer, IPPROTO_TCP, sizeof(struct tcphdr)) == -1)
	fprintf(stderr, "Error calling do_checksum for TCP.\n");

  // Now, setup the sending socket and send the beast.
  attackDest.s_addr = saddr;
  dst.sin_addr = attackDest;
  dst.sin_family = AF_INET;
  dst.sin_port = 0;

  sendto(rawSocket, buffer, BUFFER_SIZE, 0, (struct sockaddr *) &dst, sizeof(dst));
}
