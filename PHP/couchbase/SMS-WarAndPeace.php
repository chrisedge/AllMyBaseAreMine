<?php

	/**
	 * This was used to generate a "conversation"
	 * between two users in order to test SMS data
	 * structures being used in Couchbase.
	 *
	 * User accounts are represented as pre-defined
	 * DIDs. Each conversation will have a source (the
	 * initiator of the conversation), and a destination
	 * (the recipient).
	 *
	 * For simplicity's sake, we generate an MD5 hash
	 * of the DIDs and use that as the conversationID in
	 * Couchbase between these two users. Once this conversation
	 * is created, any additional messages between these two users
	 * will be part of this conversation. We can always
	 * associate a new message with this conversation by MD5'ing the src and
	 * dest DIDs and searching for a conversation ID with that
	 * hash.
	 *
	 * Next we open up and parse the entire text of War
	 * and Peace. Take 160 characters, use that text
	 * as the message from the sender to the recipient.
	 * Then read another 160 characters, and use that text
	 * as the reply. When we're done, we end up with a very
	 * long conversation (41108 messages) between two people.
	 * 
	 */

	ini_set("auto_detect_line_endings", true);
	ini_set("memory_limit", '512M');

	//$cb = new Couchbase ("xxx.xxx.xxx.xxx:8091", "admin", "password", "bucket_name"  );
	
	//$cb->set("a", '{"foo": true, "bar": false,
	// "stringKey": "stringValue", "array": [1,2,3]}');
	//var_dump($cb->get("a"));

	function gen_uuid (  ) {
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		// 32 bits for "time_low"
		mt_rand(0, 0xffff), mt_rand(0, 0xffff),

		// 16 bits for "time_mid"
		mt_rand(0, 0xffff),

		// 16 bits for "time_hi_and_version",
		// four most significant bits holds version number
		// 4
		mt_rand(0, 0x0fff) | 0x4000,

		// 16 bits, 8 bits for "clk_seq_hi_res",
		// 8 bits for "clk_seq_low",
		// two most significant bits holds zero and one for
		// variant DCE1.1
		mt_rand(0, 0x3fff) | 0x8000,

		// 48 bits for "node"
		mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
	}


	/**
	 * Establish our from and to users.
	 */
	
	$users = array();

	$users[0] = '9995551212';
	$from = $users[0];
	// Initially set. Gets mod(%)ified below.
	$users[1] = '8888881212';
	$to = $users[1];
	
	/**
	 * The msgID is the key that is used to track
	 * this conversation between these two users.
	 */
	
	$msgID = md5($users[0] . $users[1]);
	$msgCount = 0;
	$isParent = true;
	$direction = "OUTBOUND";
	$filename = "pg2600-2.txt";
	$filesize = filesize($filename);
	$fileoffset = 3;
	while ( $fileoffset < ($filesize - 160) ) {
		passthru('clear');
		$msg = file_get_contents($filename, NULL, NULL, $fileoffset, 160);
		echo "Line number: " . $msgCount;
		
		// Create a new UUID to be used as the id key to go
		// into couch for this conversation.
		$uniqID = gen_uuid();
		$timeStamp = (microtime(true) * 10000); // Expand out to 14 digits.
		
		// Shove this into an array which will get passed
		// to json_encode.
		$jsonArray = array(
							'docId' => $uniqID,
							'CAS' => "",
							'conversationId' => $msgID,
							'timeStamp' => $timeStamp,
							'from' => $from,
							'to' => $to,
							'msg' => $msg,
							'isSent' => true,
							'conversationMembers' => array($from, $to),
							'direction' => $direction,
							'isParent' => $isParent,
						);
					
		$jsonVal = json_encode($jsonArray);
		
		// $cb -> set($uniqID, $jsonVal); // Dump it into couchbase.
		echo $jsonVal;
		
		$msgCount++;
		$isParent = false; // Set to true before first run, set to false here
		
		$direction = "INBOUND"; // Set to this after initial message is sent.
		
		$fileoffset += 160;
		
		// Alternate from and to.
		$n = $msgCount % 2;
		// Odd, isn't it?
		if ($n) {
			$from = $users[$n];
			$to = $users[$n - 1];
		} else {
			$from = $users[0];
			$to = $users[1];
		}

		// Uncomment to just run a few times.
		//if ( $msgCount == 400 ) { exit; }
	}

	exit ;
?>
