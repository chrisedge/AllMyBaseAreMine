/* main.c - main entrypoint for nsbd
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

int main(int argc, char *argv[])
{

  char *argPtr;
  int argCtr;

  init();

  /* Some initial arg grooming. Maybe getopt() is better? */
  if ( argc > 5 )
    printUsage(argv[0]);

  argCtr = 1;
  argPtr = argv[argCtr++];
  while (argPtr != NULL)
  {
    // process each argument
    if (argPtr == NULL)
        break;

    if (!strcmp(argPtr, "-i") || !strcmp(argPtr, "--i"))
    {
      // replace the default interface
      argPtr = argv[argCtr++];
      if (argPtr == NULL)
      {
        printf("Warning: null interface specified. Using eth0.\n");
      }
      else
      {
        printf("Using %s as the interface.\n", argPtr);
        strcpy(interface, argPtr);
      }
    }

    if (!strcmp(argPtr, "-f") || !strcmp(argPtr, "--f"))
    {
      // replace the default config file
      argPtr = argv[argCtr++];
      if (argPtr == NULL)
      {
        printf("Warning: null config file specified. Using ./default.cnf\n");
      }
      else
      {
        printf("Using %s as the config file.\n", argPtr);
        strcpy(configFile, argPtr);
      }
    }

    if (!strcmp(argPtr, "-p") || !strcmp(argPtr, "--p"))
    {
      // replace the default pid file
      argPtr = argv[argCtr++];
      if (argPtr == NULL)
      {
        printf("Warning: null pid file specified. Using /nsbd.pid\n");
      }
      else
      {
        printf("Using %s as the pid file.\n", argPtr);
        strcpy(pidFile, argPtr);
      }
    }

    if (!strcmp(argPtr, "-h")    || !strcmp(argPtr, "--h")    ||
        !strcmp(argPtr, "-help") || !strcmp(argPtr, "--help") ||
        !strcmp(argPtr, "-?")    || !strcmp(argPtr, "--?"))
      printUsage(argv[0]);

    if (!strcmp(argPtr, "-v") || !strcmp(argPtr, "--v"))
    {
      verbose = TRUE;
      printf("Verbose mode enabled.\n");
    }

    argPtr = argv[argCtr++];
  }

  // args processed.  Pull in the configuration.
  if (parseConfig() == PARSE_ERROR)
  {
    printf("Error parsing config file.  Error listed above.\n");
    exit(10);
  }

  // We call daemon_init here after the parent pulls in the config file.
  daemon_init();

  // Setup the listener through pcap
  setupListener();

  // Setup the talker through raw IP
  setupTalker();

  // Enter the main loop
  mainLoop();

  // Shutdown, if we get here, something's odd.
  closeAll(0);

  return 0;

}

// Ripped almost char for char from APUE by Stevens. He is missed.
int daemon_init(void)
{
  pid_t	pid;
  FILE *fp;

  if ( (pid = fork() ) < 0)
	return (-1);
  else if (pid !=0)
	exit (0);	/* parent goes bye-bye */
  
  /* child continues */

  // write our pid to a file so we can be killed later (-QUIT works).
  fp = fopen(pidFile, "wb");
  if (fp == NULL)
    printf("Error opening pid file %s. PID is %d.\n", pidFile, getpid() );
  else {
      	fprintf( fp, "%d", getpid() );
      	fclose(fp);
       }

  setsid();
  chdir("/");		/* change working directory */
			/* We chdir to / because it will always be there. */
  umask(0);

  return (0);
}

int printUsage(char *argv0)
{
  printf("Usage: %s [options]\n\n", argv0);
  printf("Options:\n");
  printf("  -i interface    specify which interface to use.\n");
  printf("  -f configfile   specify which config file to use.\n");
  printf("                  (defaults to ./default.cnf)\n");
  printf("  -p pidfile      specify where to write the pidfile.\n");
  printf("                  (defaults to /nsbd.pid)\n");
  printf("  -v verbose      much verbosity is printed, use it.\n");
  printf("  -h, -help, -?   this help screen.\n");
  printf("\nNOTE: nsbd runs as a daemon, there is no need to background it.\n");
  printf("To kill it, issue the command: kill -QUIT `cat nsbd.pid`\n");
  printf("See the README file for more information.\n");
  exit(0);
}

void init(void)
{

  // setup the default interface for capturing
  strcpy(interface, "eth0");

  // setup the default config file name.
  strcpy(configFile, "./default.cnf");

  // setup the default pid file name.
  strcpy(pidFile, "/nsbd.pid");

  listener = NULL;
  rawSocket = 0;
  verbose = FALSE;

  // catch the signals and dispatch them to the closeAll method.
  if (signal(SIGHUP, closeAll) == SIG_ERR ||
      signal(SIGINT, closeAll) == SIG_ERR ||
      signal(SIGQUIT, closeAll) == SIG_ERR)
    printf("Error: seting up signal handlers for clean exit. Continuing.\n");

}

void mainLoop(void)
{
  int status;

  // Loop forever
  while(1)
  {
    status = pcap_dispatch(listener, 0, packetHandler, NULL);

    if (status == -1)
    {
      printf("Error in collection!\n");
      return;
    }
  }
}

void closeAll(int signalNo)
{
  printf("Cleaning up.\n");

  if (listener)  pcap_close(listener);
  if (rawSocket) close(rawSocket);
  unlink(pidFile);
  exit(0);
}
