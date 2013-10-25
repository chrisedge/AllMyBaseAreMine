/* log.c - logging facilities for NSB
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

void logOpen(void)
{
	// Open syslog so that the loggin facility is opened now (insted of
	// on first write), if any error occures with the syslog file, write
	// the message to the console, and log to the /var/log/secure file.
	openlog( SYSLOG_APPLICATION_NAME, LOG_NDELAY | LOG_CONS, LOG_AUTHPRIV);
}

void logMessage(char *message)
{
	// log with a notice priority
	syslog(LOG_NOTICE, "%s", message);
}

void logCriticalMessage(char *message)
{
	// log with a warning priority
        syslog(LOG_WARNING, "%s", message);
}

void logErrorMessage(char *message)
{	
	// log with an error priority.
        syslog(LOG_ERR, "%s", message);
}

void logClose(void)
{
	// close the logging facility.
	closelog();
}
