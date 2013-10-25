/* check_acl.c - optimized ACL checking routines.
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

int searchACL(unsigned long src, unsigned short sp, unsigned long dest, unsigned short dp)
{

/* searchACL - recieves: src = a network byte order source IP address
 *			 sp = a host byte order source port
 *			 dest = a network byte order destination IP address
 *			 dp = a host byte order destination port
 *
 *	       returns: TRUE if a match to the ACL is found, FALSE if not
 *
 * Called from packet.c
 */

  int i, result;

  /* In the interest of speed, if the default policy is deny, set true (kill
   * packet) else set FALSE (allow packet).
   */
  if (defaultPolicy == POLICY_DENY)
    result = TRUE;
  else
    result = FALSE;

  // Search the list forward
  for (i = 0; i < maxRule; i++)
  {
    if (ruleList[i].hostA == src)
    {
      #ifdef __DEBUG
      printf("DEBUG searchACL: Match ruleList[%d].hostA: %ld = %ld.\n", \
	     maxRule, ruleList[i].hostA, src);
      #endif
      if ( ruleList[i].portA_low <= sp && ruleList[i].portA_high >= sp)
      {
        #ifdef __DEBUG
	printf("DEBUG searchACL: Match ruleList[%d].portA: %d-%d = %d.\n", \
	       maxRule, ruleList[i].portA_low, \
	       ruleList[i].portA_high, sp);
	#endif
        if (ruleList[i].hostB == dest)
        {
	  #ifdef __DEBUG
	  printf("DEBUG searchACL: Match ruleList[%d].hostB: %ld = %ld.\n", \
		 maxRule, ruleList[i].hostB, dest);
	  #endif
	  if ( ruleList[i].portB_low <= dp && ruleList[i].portB_high >= dp )
	  {
	    #ifdef __DEBUG
	    printf("DEBUG searchACL: Match ruleList[%d].portB: %d-%d = %d.\n", \
		   maxRule, ruleList[i].portB_low, \
		   ruleList[i].portB_high, dp);
	    #endif
            if (ruleList[i].policy == POLICY_DENY)
              return TRUE;
            else
              return FALSE;
	  }
        }
      }
      else
      {
        // check for a 0.0.0.0 ip and if so, apply the policy in this rule
        if (ruleList[i].hostB == 0)
        {
	  if (ruleList[i].portB_low <= dp && ruleList[i].portB_high >= dp )
	  {
            if (ruleList[i].policy == POLICY_DENY)
              return TRUE;
            else
              return FALSE;
	  }
        }
      }
    }
  }

  // Search the list backward (for reverse entries)
  for (i = 0; i < maxRule; i++)
  {
    if (ruleList[i].hostA == dest)
    {
      if (ruleList[i].portA_low <= sp && ruleList[i].portA_high >= sp )
      {
        if (ruleList[i].hostB == src)
        {
	  if (ruleList[i].portB_low <= dp && ruleList[i].portB_high >= dp )
	  {
            if (ruleList[i].policy == POLICY_DENY)
              return TRUE;
            else
              return FALSE;
	  }
        }
      }
      else
      {
        // check for a 0.0.0.0 ip and if so, apply the policy in this rule
        if (ruleList[i].hostB == 0)
        {
	  if (ruleList[i].portB_low <= dp && ruleList[i].portB_high >= dp )
	  {
            if (ruleList[i].policy == POLICY_DENY)
              return TRUE;
            else
              return FALSE;
	  }
        }
      }
    }
  }

  // Return the default policy on fall through.
  return result;
}
