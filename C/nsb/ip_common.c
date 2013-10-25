/* ip_common.c - platform independant way of generating IP and TCP
 * checksums.
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

unsigned short in_cksum(unsigned short *ptr, int len)
{

/* in_cksum - recieves: ptr = a pointer to either an IP or TCP header struct
 *                      len = sizeof the structure ptr points to
 *
 *             returns: the u_short checksum that is then carried (if needed)
 *
 * Called from ip_common.c
 */

  int sum;
  int nleft;
  unsigned short ans;
  unsigned short *w;

  sum = 0;
  ans = 0;
  nleft = len;
  w = ptr;

  while (nleft > 1)
  {
    sum += *w++;
    nleft -= 2;
  }

  /* mop up an odd byte, if necessary */
  if (nleft == 1)
  {
    *(u_char *)(&ans) = *(u_char *)w;   /* one byte only */
    sum += ans;
  }

  return(sum);
}

int do_checksum(u_char *buf, int protocol, int len)
{

/* do_checksum - recieves: buf = a pointer to packet that is being built
 *			   protocol = the proto we need to sum for (TCP or IP)
 *                         len = sizeof what buf points to
 *
 *             returns: an int return status (1 good, -1 bad)
 *
 * Called from killer.c
 */

  struct iphdr *iph; // Pointer to the IP header structure.
  int ihl;
  int sum;

  sum = 0;
  iph = (struct iphdr *)buf;
  ihl = iph->ihl << 2;

  switch (protocol)
  {
	case IPPROTO_TCP:
	{
	  struct tcphdr *tcph = (struct tcphdr *)(buf + ihl);

	  /* For the Solaris port:
	   * #if (STUPID_SOLARIS_CHECKSUM_BUG)
	   * tcph->check = tcph->doff << 2;
	   * return (1);
	   * #endif
	   * Of course, we might also want to ID which versions of
	   * Solaris this is a problem on.
	   */

	  tcph->check = 0;
	  sum = in_cksum((u_short *)&iph->saddr, 8);
	  sum += ntohs(IPPROTO_TCP + len);
	  sum += in_cksum((u_short *)tcph, len);
	  tcph->check = CKSUM_CARRY(sum);
	  break;
	}
	case IPPROTO_IP:
	{
	  iph->check = 0;
	  sum = in_cksum((u_short *)iph, len);
	  iph->check = CKSUM_CARRY(sum);
	  break;
	}
	default:
	{
	  fprintf(stderr,"do_checksum error: protocol not supported %d.\n", \
		  protocol);
	  return(-1);
	}
  }
  
  return (1);
}
