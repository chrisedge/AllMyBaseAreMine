/* OK - the plan here is to allow the user to give us a domain name.
 * We then use our local resolver to build a list of name servers for
 * that given domain. We will then begin to query these servers using
 * our finger printing methods. And even better - we'll query one server
 * for one of the other server's A records.
 *
 * So the first few calls will be to find, add, and queryNameServers to
 * build our list of servers. Then we'll make our own calls to res_mkquery()
 * with our own brew of options.
 */

#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <netdb.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <errno.h>
#include <arpa/nameser.h>
#include <arpa/inet.h>
#include <resolv.h>

extern int h_errno;	/* for resolver errors */
extern int errno;	/* general system errors */

/* Function prototypes */
void nsError();
void findNameServers();
void addNameServers();
void queryNameServers();
void returnCodeError();

/* Define maximum number of servers we'll hit for a given domain.
 * Maybe make this is a user-definable option? */
#define MAX_NS 10

/* 
 * Resolver library functions. Used for creating and sending queries.
 */

 /******************************************************************************
  * res_init - returns 0, always.
  *
  * int res_init(void)

  Reads resolv.conf and initializes a struct called _res (see resolv.h).
  All other functions below will call res_init() if it has not been called.
  Calling first allows the _res members to be set to our needs. _res.options
  is a u_long bitmask of options. These may be useful:
  RES_INIT - res_init() has been called
  RES_AAONLY - auth answers only (not implemented)
  RES_USEVC - make the query with TCP
  RES_RECURSE - recursion desired
  RES_STAYOPEN - keep TCP socket open
  RES_IGNTC - don't auto retry with TCP if response is truncated
  *****************************************************************************/
 

/*******************************************************************************
 * res_mkquery - returns the size of the query packet, or -1
 *
 * int res_mkquery(int op,
 *					const char *dname,
 * 					int class,
 *					int type,
 *					const u_char *data,
 *					int datalen
 *					const u_char *newrr,
 *					u_char *buf,
 *					int buflen)

 Members and their possible values (from arpa/nameser.h):
 op: 0 - standard query
 	 1 - inverse query (deprecated in BIND 4.9.4 and later)
	 2 - name server status (unsupported)
	 3 - undefined/reserved
	 4 - zone change notification
	 5 - zone update notification
	 6 - ns_o_max (?)
 NOTE: this is a 2 byte field, there are a lot of undefined bits.

 dname: a fully qualified domain name

 class: 0 - cookie (invalid?)
 		1 - internet
		2 - unallocated/unsupported
		3 - chaos
		4 - hesiod
		254 - prereq. sections in update requests
		255 - any
		65536 - ns_c_max (?)

 type: 0 - cookie (invalid?)
 	   1 - host address
	   255 - any
	   256 - BIND-specific, nonstandard
	   65536 - ns_t_max (?)

 NOTE: there are between 0-41, 250-256, 65536 defined types. Only the
 most common are listed above for breavity.

 data: a buffer of data for inverse queries - should be NULL when
 	   op == 1.

 datalen: sizeof(data) - should be 0 if data == NULL

 newrr: a buffer used for dynamic updates - usually NULL

 buf : the buffer res_mkquery stuffs the packet into - should be
 	   PACKETSZ or larger(?)

 buflen: sizeof(buf)
 ******************************************************************************/


/*******************************************************************************
 * res_send - returns the size of the response, or -1
 *
 * int res_send(const u_char *msg,
 *              int msglen,
 *              u_char *answer,
 *              int anslen)

 Members and their possible values:

 msg: buffer with the query packet (buf from res_mkquery?)

 msglen: sizeof(msg)

 answer: buffer where answer packet is stored

 anslen: sizeof(answer)
 ******************************************************************************/


int
main (argc, argv)
int argc;
char *argv[];
{

	char *nsList[MAX_NS];	/* list of name servers */
	int nsNum = 0;			/* number of name servers in the list */

	if (argc != 2)
	{
		fprintf(stderr, "%s <domain name>\n", argv[0]);
		exit(1);
	}

	(void) res_init();

	findNameServers(argv[1], nsList, &nsNum);

	queryNameServers(argv[1], nsList, nsNum);

	exit(0);
}


/*
 * find all the name servers and store their names in nsList. nsNum
 * is the number of servers in the nsList array.
 */

 void
 findNameServers(domain, nsList, nsNum)
 char *domain;
 char *nsList[];
 int *nsNum;
 {
 	union {
		HEADER hdr;					/* defined in resolv.h */
		u_char buf[NS_PACKETSZ];	/* defined in arpa/nameser.h */
	} response;						/* response buffers */
	int responseLen;				/* buffer length */

	ns_msg handle;					/* handle for response packet */

	/* Here we go */
	if ((responseLen =
			res_query(domain,		/* the domain we're after */
					  ns_c_in,		/* the internet class */
					  ns_t_ns,		/* we want type ns records */
					  (u_char *)&response,	/* response buffer */
					  sizeof(response)))	/* buffer size */
					  < 0) {				/* if negative */
		nsError(h_errno, domain);
		exit(1);
	}

	/* OK - we just sent a query and our response is now sitting
	 * in response.buf
	 */

	/* initialize a handle to this response, we'll use it later */
	if (ns_initparse(response.buf, responseLen, &handle) < 0) {
		fprintf(stderr, "ns_initparse: %s\n", strerror(errno));
		return;
	}

	/* create our list from the response. NS records may be in the
	 * answer and/or authority sections depending on implementation.
	 * we look in both.
	 */

	 /* parse the servers from the answer section first */
	 addNameServers(nsList, nsNum, handle, ns_s_an);

	 /* now grock out the authority section */
	 addNameServers(nsList, nsNum, handle, ns_s_ns);
}


/* examine the RRs from a given section. also save all the names we get */
void
addNameServers(nsList, nsNum, handle, section)
char *nsList[];
int *nsNum;
ns_msg handle;
ns_sect section;
{
	int rrnum;		/* RR number (?) */
	ns_rr rr;		/* expanded RR (?) */

	int i, dup;		/* misc. */


	/* look at all the RRs in this section */
	for (rrnum = 0; rrnum < ns_msg_count(handle, section); rrnum++)
	{
		/* expand the RR rrnum into rr (?) */
		if (ns_parserr(&handle, section, rrnum, &rr))
			fprintf(stderr, "ns_parserr: %s\n", strerror(errno));

		/* if the record type is NS, save the name */
		if (ns_rr_type(rr) == ns_t_ns)
		{
			/* make some room for the name */
			nsList[*nsNum] = (char *) malloc (MAXDNAME);
			if(nsList[*nsNum] == NULL)
			{
				fprintf(stderr, "malloc failed\n");
				exit(1);
			}

			/* grab the name */
			if(ns_name_uncompress(
				ns_msg_base(handle),	/* start of the packet */
				ns_msg_end(handle),		/* end o' the packet */
				ns_rr_rdata(rr),		/* position in the packet */
				nsList[*nsNum],			/* result */
				MAXDNAME)				/* sizeof the nsList buffer */
				< 0)					/* negative, error */
			{
				fprintf(stderr, "ns_name_uncompress failed\n");
				exit(1);
			}

		/* check the name, add it to the list if it's not a dup */
		for(i = 0, dup = 0; (i < *nsNum) && !dup; i++)
			dup = !strcasecmp(nsList[i], nsList[*nsNum]);
		if(dup)
			free(nsList[*nsNum]);
		else
			(*nsNum)++;
		}
	}
}

/* query each server in nsList. this is where we will do our work. we'll
 * start by querying the current server in nsList for the A record of
 * the next server in our list, and so on.
 */
void
queryNameServers(domain, nsList, nsNum)
char *domain;
char *nsList[];
int nsNum;
{
	union 
	{
		HEADER hdr;
		u_char buf[NS_PACKETSZ];
	} query, response;
	int responseLen, queryLen;

	u_char *cp;		/* char pointer to parse DNS packet */

	struct in_addr saveNsAddr[MAXNS];	/* addrs saved from _res */
	int nsCount;					/* count of addresses saved from _res */
	struct hostent *host;			/* struct for looking up ns addr */
	int i;

	ns_msg handle;					/* handle for response packet */
	ns_rr rr;						/* expanded RR */

	/* save the _res name server list since we restore it later */
	nsCount = _res.nscount;
	for(i = 0; i < nsCount; i++)
		saveNsAddr[i] = _res.nsaddr_list[i].sin_addr;
	
	/* set some _res.options - turn off searching and appending default
	 * domain name since the names will be fully qualified.
	 */
	 _res.options &= ~(RES_DNSRCH | RES_DEFNAMES);

	 /* query each server for the A record of the next server in the list */
	 for(nsNum--; nsNum >= 0; nsNum--)
	 {
	 	/* first restore values in _res that were altered in the
		 * previous iteration of the loop (by gethostbyname).
		 */
		_res.options |= RES_RECURSE;	/* turn recursion on */
		_res.retry = 4;					/* the default */
		_res.nscount = nsCount;			/* original name servers */
		for(i = 0; i < nsCount; i++)
			_res.nsaddr_list[i].sin_addr = saveNsAddr[i];

		/* look up the name server's address */
		host = gethostbyname(nsList[nsNum]);
		if (host == NULL)
		{
			fprintf(stderr, "no address for %s\n", nsList[nsNum]);
			continue;
		}

		/* host now has IPs for the server we're testing. store the
		 * first address for host in the _res struct.
		 */
		(void) memcpy((void *)&_res.nsaddr_list[0].sin_addr,
			(void *)host->h_addr_list[0], (size_t)host->h_length);
		_res.nscount = 1;

		/* turn off recursion. this server should be auth for the A
		 * record data.
		 */
		_res.options &= ~RES_RECURSE;

		/* reduce the number of retires since we only have one address
		 * to query
		 */
		_res.retry = 2;

		/* we want to see the response code so we have to make the
		 * query packet and send it ourselves instead of having
		 * res_query() do it. no need to check for res_mkquery()
		 * returning -1. if the compression was going to fail it
		 * would've failed when we called res_query() on the domain
		 * name earlier.
		 */
		queryLen = res_mkquery(
			ns_o_query,		/* a regular query */
			nsList[nsNum],	/* we look up our own name */
			ns_c_in,		/* internet type */
			ns_t_a,			/* an A record */
			(u_char *)NULL, /* always NULL */
			0,				/* sizeof(NULL) */
			(u_char *)NULL, /* always NULL */
			(u_char *)&query, /* buf for the query */
			sizeof(query));	/* obvious */

		/* now we send the packet. if there is no name server running
		 * res_send() returns -1 and errno is ECONNREFUSED. clear
		 * out errno first.
		 */
		errno = 0;
		if((responseLen = res_send((u_char *)&query, /* the query */
									queryLen,		 /* true len */
									(u_char *)&response, /* buf */
									sizeof(response)))   /* buf size */
									< 0)
		{
			if(errno == ECONNREFUSED) /* no server on the host */
			{
				fprintf(stderr, "no name server on %s\n", nsList[nsNum]);
			} else					  /* anything else is no response */
			{
				fprintf(stderr, "no response from %s\n", nsList[nsNum]);
			}
			continue; /* nsNum for-loop */
		}

		/* setup a handle to this response - we'll use it later to snarf
		 * out the info from the response.
		 */
		if (ns_initparse(response.buf, responseLen, &handle) < 0)
		{
			fprintf(stderr, "ns_initparse: %s\n", strerror(errno));
			return;
		}

		/* if the response is an error, let us know and keep going */
		if(ns_msg_getflag(handle, ns_f_rcode) != ns_r_noerror)
		{
			returnCodeError(ns_msg_getflag(handle, ns_f_rcode), nsList[nsNum]);
			continue; /* nsNum for-loop */
		}

		/* was the response auth? check the bit, if not, report it and go on */
		if(!ns_msg_getflag(handle, ns_f_aa))
		{
			fprintf(stderr, "%s not auth for itself?\n", nsList[nsNum]);
			continue; /* nsNum for-loop */
		}

		/* the response should only have one answer, if not report and go on */
		if(ns_msg_count(handle, ns_s_an) != 1)
		{
			fprintf(stderr, "%s expected 1 answer, got %d\n", nsList[nsNum],
				ns_msg_count(handle, ns_s_an));
			continue; /* nsNum for-loop */
		}

		/* expand answer section record number 0 into rr */
		if (ns_parserr(&handle, ns_s_an, 0, &rr))
		{
			if (errno != ENODEV)
				fprintf(stderr, "ns_parserr: %s\n", strerror(errno));
		}

		/* we wanted an A record, if we got something else, report and go on */
		if (ns_rr_type(rr) != ns_t_a)
		{
			fprintf(stderr, "%s: expected answer %d, got %d\n", nsList[nsNum],
				ns_t_a, ns_rr_type(rr));
			continue; /* nsNum for-loop */
		}

		/* setup cp to point to the A record */
		cp = (u_char *)ns_rr_rdata(rr);
		
		/* if all went well, we should see the address */
		printf("%s has A record of %s\n", nsList[nsNum], inet_ntoa(*(struct in_addr *)(cp)));
	} /* end of nsNum for-loop */
}

/* print an error message from h_errno for failure in looking up records.
 * res_query() converts the packet return code to a smaller list of errors
 * and places the value in h_errno. there is a routine called herror()
 * for printing strings from h_errno like perror() does for errno. however
 * the herror() messages assume you're looking up only A records. we need
 * our own error messages.
 */
void
nsError(error, domain)
int error;
char *domain;
{
	switch(error)
	{
		case HOST_NOT_FOUND:
			fprintf(stderr, "unknown domain: %s\n", domain);
			break;
		case NO_DATA:
			fprintf(stderr, "no records for %s\n", domain);
			break;
		case TRY_AGAIN:
			fprintf(stderr, "no response for query\n");
			break;
		default:
			fprintf(stderr, "unexpected error\n");
			break;
	}
}

/* print  an error message from DNS response return code */
void
returnCodeError(rcode, nameserver)
ns_rcode rcode;
char *nameserver;
{
	fprintf(stderr, "%s: ", nameserver);
	switch(rcode)
	{
		case ns_r_formerr:
			fprintf(stderr, "FORMERR response\n");
			break;
		case ns_r_servfail:
			fprintf(stderr, "SERVFAIL response\n");
			break;
		case ns_r_nxdomain:
			fprintf(stderr, "NXDOMAIN response\n");
			break;
		case ns_r_notimpl:
			fprintf(stderr, "NOTIMP reponse\n");
			break;
		case ns_r_refused:
			fprintf(stderr, "REFUSED response\n");
			break;
		default:
			fprintf(stderr, "unexpected return code\n");
			break;
	}
}
