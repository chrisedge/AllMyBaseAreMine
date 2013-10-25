/* parse_acl.c - parses the ACL from the config file (default.cnf).
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

int parseConfig(void)
{
   FILE *fp;
  char line[MAXLINE];
  char hostA[MAXLINE], hostB[MAXLINE]; 
  char portA[MAXLINE], portB[MAXLINE];
  char *portA_low = NULL, *portA_high = NULL;
  char *portB_low = NULL, *portB_high = NULL;
  char policy[MAXLINE];
  /* In case we ever decide to make inet_ntoa work.
   * struct in_addr in_hostA, in_hostB;
   */
  int i, count, lineNo, caughtError;

  // setup the rulelist
  /* How about in the future we read the file in first, and figure out
   * how many rules we'll need, then set MAX_RULES to that?
   */
  maxRule = 0;
  defaultPolicy = POLICY_DENY;
  for (i = 0; i < MAX_RULES; i++)
  {
    // do it this way to avoid the memset/bzero inconsistancy.
    ruleList[i].hostA = 0;
    ruleList[i].portA_low = 0;
    ruleList[i].portA_high = 0;

    ruleList[i].hostB = 0;
    ruleList[i].portB_low = 0;
    ruleList[i].portB_high = 0;

    ruleList[i].policy = 0;
  }

  fp = fopen(configFile, "rb");

  if (fp == NULL)
  {
    printf("Error opening config file: %s\n", configFile);
    return PARSE_ERROR;
  }

  // otherwise we have success at file open.

  // Scan the file in creating policy rules.
  lineNo = 1;
  while (1)
  {
    // Reset everything. Bored? Trace why these had to be reset.
    portA_low = NULL; portB_low = NULL; portA_high = NULL; portB_high = NULL;

    caughtError = FALSE;

    if ((fgets(line, MAXLINE, fp)) == NULL)
       break; /* We hit EOF */

    if (!strncmp(line, "#", 1)) /* We have a comment */
    {
        #ifdef __DEBUG
        printf("DEBUG parseConfig: Skipping comment at line %d\n", lineNo);
        #endif 
        lineNo++;
        continue;
    }

    #ifdef __DEBUG
    printf("DEBUG parseConfig: Line number %d\n", lineNo);
    #endif

    // For ports, we first grab what's after ":" and stick it in
    // portX. Later we check for "-", split it apart and stuff
    // it in the structure.
    count = sscanf(line, "%[^:]:%[^: ] %[^:]:%[^: ] %s\n", \
            hostA, portA, hostB, portB, policy);

    #ifdef __DEBUG
    printf("DEBUG parseConfig: %s:%s %s:%s %s\n", hostA, portA, hostB, portB, policy);
    #endif

    if (count != 5)
    {
      printf("Error reading config file at line %d.\n", lineNo);
      return PARSE_ERROR;
    }

    // create the rule
    // check for the default rule
    // These should check for case sensitivity in the future.
    if ( !strcmp(hostA, "default") && !strcmp(portA, "any") && \
         !strcmp(hostB, "default") && !strcmp(portB, "any"))
    {
      int gotGoodRule = FALSE;

      // default rule.  form the default
      if (!strcmp(policy, "deny"))
      {
        defaultPolicy = POLICY_DENY;
        gotGoodRule = TRUE;
      }
      if (!strcmp(policy, "allow"))
      {
        defaultPolicy = POLICY_ALLOW;
        gotGoodRule = TRUE;
      }
      if (gotGoodRule == FALSE)
      {
        printf("Mangled default policy rule.\n");
        printf("Make sure the default rule is formated like this:\n");
        printf("default:any default:any [allow | deny]\n");
        exit(0);
      }
    }
    else
    {
      // create normal rule. (hosts stored in network byte order)

      // First write the IPs into the struct.

      /* Problem: inet_aton is writing the address to the struct
       * differently than inet_addr is. The call to searcACL in packet.c
       * is passing iph->saddr and iph->daddr. These match to what
       * inet_addr does. Need to find how to pass these so they match
       * what inet_aton does. For now, we revert back to inet_addr and
       * everything works great.
       * These should be changed back at some point.
       * See page 71 of UNP v.1 for more info as to why.
       * ruleList[maxRule].hostA = inet_aton(hostA, &in_hostA);
       * ruleList[maxRule].hostB = inet_aton(hostB, &in_hostB);
       */
      ruleList[maxRule].hostA = inet_addr(hostA);
      ruleList[maxRule].hostB = inet_addr(hostB);

      // Now handle the ports.
      // Check for keywords.
      if ( !strcmp(portA, "any") )
      {
        portA_low = "0";
        portA_high = "65535";
      }
      if ( !strcmp(portB, "any") )
      {
        portB_low = "0";
        portB_high = "65535";
      }

      if ( !strcmp(portA, "low") )
      {
        portA_low = "0";
        portA_high = "1023";
      }
      if ( !strcmp(portB, "low") )
      {
        portB_low = "0";
        portB_high = "1023";
      }

      if ( !strcmp(portA, "high") )
      {
        portA_low = "1024";
        portA_high = "65535";
      }
      if ( !strcmp(portB, "high") )
      {
        portB_low = "1024";
        portB_high = "65535";
      }     

      // If portX_low is still NULL, we have a singular port, or a range.
      if ( portA_low == NULL )
      {
        portA_low = strtok(portA, "-"); // Check for a range.
        if ( (portA_high = strtok(NULL, "-")) == NULL )
          portA_high = portA_low; // For singular ports, low = high.
        if ( strtok(NULL, "-") != NULL ) // They munged the port range.
        {
          fprintf(stderr, "Invalid syntax, line: %d. Ignoring rule.\n", lineNo);
          caughtError = TRUE;
        }
      }

      #ifdef __DEBUG
      printf("DEBUG parseConfig: The string portA_low = %s\n", portA_low);
      printf("DEBUG parseConfig: The string portA_high = %s\n", portA_high);
      #endif

      if ( portB_low == NULL )
      {
        portB_low = strtok(portB, "-");
        if ( (portB_high = strtok(NULL, "-")) == NULL )
          portB_high = portB_low;
        if ( strtok(NULL, "-") != NULL )
        {
          fprintf(stderr, "Invalid syntax, line: %d. Ignoring rule.\n", lineNo);
          caughtError = TRUE;
        }
      }

      #ifdef __DEBUG
      printf("DEBUG parseConfig: The string portB_low = %s\n", portB_low);
      printf("DEBUG parseConfig: The string portB_high = %s\n", portB_high);
      #endif

      // Convert the ports from strings to integers.
      ruleList[maxRule].portA_low = atoi(portA_low);
      ruleList[maxRule].portA_high = atoi(portA_high);
      ruleList[maxRule].portB_low = atoi(portB_low);
      ruleList[maxRule].portB_high = atoi(portB_high);

      #ifdef __DEBUG
      printf("DEBUG parseConfig: ruleList[%d].portA_low = %d\n", maxRule, \
        ruleList[maxRule].portA_low);
      printf("DEBUG parseConfig: ruleList[%d].portA_high = %d\n", maxRule, \
        ruleList[maxRule].portA_high);
      printf("DEBUG parseConfig: ruleList[%d].portB_low = %d\n", maxRule, \
        ruleList[maxRule].portB_low);
      printf("DEBUG parseConfig: ruleList[%d].portB_high = %d\n", maxRule, \
        ruleList[maxRule].portB_high);
      #endif

      // Define the policy for this rule.
      if ((!strcmp(policy, "allow")) || (!strcmp(policy, "ALLOW")))
      {
        ruleList[maxRule].policy = POLICY_ALLOW;
      }
      else
      {
        if ((!strcmp(policy, "deny")) || (!strcmp(policy, "DENY")))
          ruleList[maxRule].policy = POLICY_DENY;
        else
        {
          printf("Line %d: error in policy.  Rule will be ignored.\n", lineNo);
          caughtError = TRUE;
        }
      }

      // validate the rule. This might need some work.
      if (ruleList[maxRule].hostA == -1)
      {
        printf("Line %d: bad IP in hostA position, ignoring rule.\n", lineNo);
        caughtError = TRUE;
      }
      if (ruleList[maxRule].hostB == -1)
      {
        printf("Line %d: bad IP in hostB position, ignoring rule.\n", lineNo);
        caughtError = TRUE;
      }

      // If we have no errors, add this rule to the list.
      if (caughtError == FALSE)
        maxRule++;
    }
    // bump the line number
    lineNo++;
  }
  return PARSE_SUCCESS;
}
