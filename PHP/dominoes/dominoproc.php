#!/opt/local/bin/php
<?php

	error_reporting(E_ALL ^ E_NOTICE);

	$GLOBALS['gameSize'] = '';
	$GLOBALS['numPlayers'] = '';
	$GLOBALS['numToDraw'] = '';
	$GLOBALS['currentRound'] = '';
	$GLOBALS['player'] = array();
	$GLOBALS['playerTrain'] = array();
	$GLOBALS['ourDominoes'] = array();
	$GLOBALS['longest'] = array();
	$GLOBALS['ourTrainLength'] = 0;
	$GLOBALS['mexicanTrain'] = array();
	$GLOBALS['OPEN_DOUBLE'] = array();
	$GLOBALS['override'] = 0;
	
	$opts = getopt('s:r:n:h');

	foreach ( array_keys($opts) as $opt )
		switch ($opt) {
			case 's':
			case 'r':
			case 'n':
				$size = $opts['s'];
				$round = $opts['r'];
				$players = $opts['n'];
				break;
			case 'h':
				echo "Run with -s [game size of {6,9,12,15,18} ] -r [round number from 0 - {game size} ] -n [number of human players]\n";
				exit(1);
		}
		
	
	if(isset($round)) {
		// They want to start in the middle of a game.
		$GLOBALS['gameSize'] = $size;
		$GLOBALS['numPlayers'] = $players;
		
		switch($GLOBALS['gameSize']) {
			case 6:
				$numToDraw = 7;
				break;
			case 9:
				$numToDraw = 15;
				break;
			case 12:
				switch($GLOBALS['numPlayers']) {
					case 3:
					case 4:
						$numToDraw = 15;
						break;
					case 5:
					case 6:
						$numToDraw = 12;
						break;
					case 7:
					case 8:
						$numToDraw = 11;
				}
				break;
			case 15:
			case 18:
				$numToDraw = 11;
				break;
		}
			
		$GLOBALS['numToDraw'] = $numToDraw;
		$GLOBALS['currentRound'] = $round;
		
		echo "Player 0 is always the computer player.\n";
		createPlayer('Computer', $numToDraw);
		$computerScore = readline("What is the computer's score? ");
		$GLOBALS['player'][0]['score'] = $computerScore;
		
		for($i = 1; $i <= $players; $i++) {
			$playerName = readline("What is player " . $i . "'s name? ");
			$playerScore = readline("What is the player's score? ");
			createPlayer($playerName, $numToDraw);
			$GLOBALS['player'][$i]['score'] = $playerScore;
		}
		
		main($round);
		
	} else {
		
		main();
		
	}
	
	function main ($newRound = NULL) {
			
		if($newRound === NULL) {
		
			passthru('clear');
			echo "Welcome to the domino player.\n";

			$retry = 1;
			while ( $retry ) {// The poor man's retry().
				$e = NULL;
				$GLOBALS['gameSize'] = readline("What size of a game will we be playing - 6, 9, 12, 15, or 18? ");
				$GLOBALS['numPlayers'] = readline("How many players (including me)? ");
				try {
					createGame($GLOBALS['gameSize'], $GLOBALS['numPlayers']);
					// New game created?
				} catch( Exception $e) { // This needs to be fixed - throwback to OOP code originally tried.
					// throw $e;
					echo $e -> getMessage();
				}
				if (!isset($e))
					$retry = 0;
			}
			
			switch($GLOBALS['gameSize']) {
			case 6:
				$numToDraw = 7;
				break;
			case 9:
				$numToDraw = 15;
				break;
			case 12:
				switch($GLOBALS['numPlayers']) {
					case 3:
					case 4:
						$numToDraw = 15;
						break;
					case 5:
					case 6:
						$numToDraw = 12;
						break;
					case 7:
					case 8:
						$numToDraw = 11;
				}
				break;
			case 15:
			case 18:
				$numToDraw = 11;
				break;
			}
			
			$GLOBALS['numToDraw'] = $numToDraw;

			$GLOBALS['currentRound'] = $GLOBALS['gameSize'];
			// Initially set at highest double.
			echo "Starting with yourself and moving to your left, please provide a name for each player.\n";
			for ( $i = 0; $i < $GLOBALS['numPlayers']; $i++ ) {//
				// player 0 will always be us.
				$name = readline("Player " . $i . "'s name: ");
				createPlayer($name, $numToDraw);
			}
		} else { // New round of the same game.
			// Reset some GLOBALS.
			$GLOBALS['playerTrain'] = array();
			$GLOBALS['ourDominoes'] = array();
			$GLOBALS['longest'] = array();
			$GLOBALS['ourTrainLength'] = 0;
			$GLOBALS['mexicanTrain'] = array();
			$GLOBALS['OPEN_DOUBLE'] = array();
			
			foreach($GLOBALS['player'] as $index => $value) { 
				$GLOBALS['player'][$index]['rackCount'] = $GLOBALS['numToDraw']; // Reset everyone's rackcount...
				$GLOBALS['player'][$index]['open'] = 0; // ...close any open trains...
				$GLOBALS['playerTrain'][] = ''; // ...and recreate their train holders.
			}
		}

		/**
		 * Read in a players dominoes after they draw them.
		 * Double 6 - each draws 7
		 * Double 9 - each draws 15
		 * Double 12 4 players - each draws 15
		 * Double 12 5-6 players - each draws 12
		 * Double 12 7-8 players - each draws 11
		 * Double 15 - each draws 11
		 * Double 18 - each draws 11
		 *
		 */

		readDominoes($GLOBALS['numToDraw']);
		
		echo "Here are your dominoes:\n";
			
		showDominoes($GLOBALS['ourDominoes']);
		
		sortWrapper($GLOBALS['currentRound']);
		
		echo "Here are your sorted dominoes:\n";
			
		showDominoes($GLOBALS['ourDominoes']);
		
		echo "Starting round " . $GLOBALS['currentRound'] . "\n";
		roundLoop($GLOBALS['currentRound']); // Hand off to roundLoop
		
		exit;
		

	}

	function roundLoop($currentRound) {
		
		/**
		 * The roundLoop should run until the
		 * round is over. The round is over
		 * when we, or another player have played
		 * all of our dominoes. We will know when
		 * we're out internally, but we'll need
		 * to keep track of the other players.
		 * We also have the command option to end
		 * the round should all the dominoes be drawn
		 * and no one can play.
		 */
			
			// First check if we have the starting double for this round.
			// Theoretically, if we do it should be at index 0 in our set after sorting.
			if( ( $hasStart = array_search( array($currentRound,$currentRound), $GLOBALS['ourDominoes'] ) ) !== FALSE ) {
				// We have the double, so place it at the 'hub'. In this case, the hub is the root
				// for all the player's trains.
				foreach($GLOBALS['playerTrain'] as $index => $player) {
					$GLOBALS['playerTrain'][$index][] = $GLOBALS['ourDominoes'][$hasStart];
				}
				
				echo "We have the double to start this round!\n";
				
				// Remove it from our rack.
				unset($GLOBALS['ourDominoes'][$hasStart]);
				$GLOBALS['ourDominoes'] = array_values($GLOBALS['ourDominoes']);
				// Decrement our tile count.
				$GLOBALS['player'][0]['rackCount']--;
				$GLOBALS['ourTrainLength']--; // Don't forget this!
				
			} else { // We don't have the double, so prompt to find out if anyone else does.
				$answer = readline("We can't start the round. Does another player have the double (y/n)? ");
				switch($answer) {
					case 'y':
						showPlayers();
						$which = readline("What player had the double? ");
						// Decrement their tile count.
						$GLOBALS['player'][$which]['rackCount']--;
						// Update all player's trains to have the double at the head.
						foreach($GLOBALS['playerTrain'] as $index => $player) {
							$GLOBALS['playerTrain'][$index][] = array( 0 => $currentRound, 1 => $currentRound);
						}
						break;
					case 'n':
						// We all have to draw simultaneously.
						echo "All players must draw simultaneously.\n";
						readDominoes(1);
						sortWrapper($GLOBALS['currentRound']);
						foreach($GLOBALS['player'] as $index => $value) {
							$GLOBALS['player'][$index]['rackCount']++;
						}
						showPlayers();
						showDominoes($GLOBALS['ourDominoes']);
						roundLoop($currentRound); // Recurse until someone has it.
					default:
						roundLoop($currentRound);
				}	
			}
			
			// When we get here, we now have a double at the center for
			// everyone to play off of.
			echo "Ready to start this round.\n";
			
			$continue = 1;
			while ($continue) {
				
				$lastCommand = processCommand();
				if($lastCommand == 'END') // Trigger to end this round when there are no more dominoes to draw.
					$continue = 0;
				
				// Goal test for normal end of round.
				foreach($GLOBALS['player'] as $index => $value) {
					if( $GLOBALS['player'][$index]['rackCount'] == 0 )
						$continue = 0;
				}
			}
			
			// End of this round.
			$GLOBALS['currentRound']--;
			// Tally scores. First, our own.
			$ourTotal = 0;
			if($GLOBALS['player'][0]['rackCount'] > 0) {
				foreach($GLOBALS['ourDominoes'] as $index => $value) {
					$ourTotal += ($value[0] + $value[1]);
				}
			}
			
			$GLOBALS['player'][0]['score'] += $ourTotal;
			
			foreach($GLOBALS['player'] as $index => $value) {
				if($index == 0) // Skip ourselves.
					continue;
				if( $GLOBALS['player'][$index]['rackCount'] != 0 ) {
					$tmpScore = readline("What was player " . $index . "'s score? ");
					$GLOBALS['player'][$index]['score']+= $tmpScore;
				} 
			}
			
			// Re-call main with currentRound.
			if($GLOBALS['currentRound'] >= 0) {
				main($GLOBALS['currentRound']);
			} else {
				// End of the game. Show the scores and exit.
				echo "End of game!\n";
				showPlayers();
				exit;
			}
					
	}

	function processCommand() {
		
		$command = readline("Command options: R, P, D, S, B, C, M, V, E, O, H, Q, or ?(help): ");
			
			switch($command) {
					case '':
					case '?':
						echo "R: report a play by another player.\n";
						echo "P: make a play yourself.\n";
						echo "D: another player drew.\n";
						echo "S: show players.\n";
						echo "B: show the board.\n";
						echo "C: clear the screen.\n";
						echo "M: mark a player's train open or closed.\n";
						echo "V: view your rack.\n";
						echo "E: end the round.\n";
						echo "O: override double enforcement and allow direct human play. Currently: ".$GLOBALS['override'].".\n";
						echo "H: human play (if override enabled).\n";
						echo "Q: quit the game.\n";
						echo "?: show this help screen.\n";
						break;
					case 'H':
					case 'h':
						$dominoArray = array();
						$playerIndex = 0;
						$locationIndex = readline("Train number, or mexican: ");
						$dominoPair = readline("Domino - x,y: ");
						$dominoArray[] = explode(',', $dominoPair);
						if( playDomino($playerIndex, $dominoArray, $locationIndex) ) {
							echo "Tile played successfully.\n";
							// Always re-sort after we override one.
							echo "Re-sorting.\n";
							$lastIndex = count($GLOBALS['playerTrain'][0]) - 1; // Should always be the last element.
							$matchNumber = $GLOBALS['playerTrain'][0][$lastIndex][1];
							sortWrapper($matchNumber);
						} else {
							echo "Tile could not be played.\n";
						}
						break;
					case 'O':
					case 'o':
						if($GLOBALS['override'] == 0) {
							$GLOBALS['override'] = 1;
						} else {
							$GLOBALS['override'] = 0;
						}
						break;
					case 'E':
					case 'e':
						$answer = readline("Really end this round?(y/n): ");
						if($answer == 'y')
							return 'END';
						break;
					case 'D':
					case 'd':
						$playerIndex = readline("Which player drew? ");
						$GLOBALS['player'][$playerIndex]["rackCount"]++;
						if($playerIndex == 0) {
							readDominoes(1);
							// Always resort after we draw one.
							echo "Re-sorting.\n";
							$lastIndex = count($GLOBALS['playerTrain'][0]) - 1; // Should always be the last element.
							$matchNumber = $GLOBALS['playerTrain'][0][$lastIndex][1];
							sortWrapper($matchNumber);
						}
						echo "Rack count updated.\n";
						break;  
					case 'M':
					case 'm':
						$playerIndex = readline("Which player's train? ");
						$openClose = readline("Open = 1, Close = 0: ");
						$GLOBALS['player'][$playerIndex]["open"] = (integer)$openClose;
						echo "Train marked.\n";
						break;
					case 'V':
					case 'v':
						showDominoes($GLOBALS['ourDominoes']);
						break;
					case 'P':
					case 'p':
						if( computerPlay() ) {
							echo "Tile played successfully.\n";
						} else {
							echo "Tile could not be played.\n";
						}
						break;
					case 'R':
					case 'r':
						$dominoArray = array();
						$playerIndex = readline("Player number: ");
						$locationIndex = readline("Train number, or mexican: ");
						$dominoPair = readline("Domino - x,y: ");
						$dominoArray[] = explode(',', $dominoPair);
						if( playDomino($playerIndex, $dominoArray, $locationIndex) ) {
							echo "Tile played successfully.\n";
						} else {
							echo "Tile could not be played.\n";
						}
						break;
					case 'S':
					case 's':
						showPlayers();
						break;
					case 'B':
					case 'b':
						showBoardInfo();
						break;
					case 'C':
					case 'c':
						passthru('clear');
						break;
					case 'Q':
					case 'q':
						$answer = readline("Really quit this game?(y/n): ");
						if($answer == 'y') {
							echo "Bye.\n";
							exit;
						}
				}
			return;
	}

	function computerPlay() {
		
		if( !empty($GLOBALS['OPEN_DOUBLE']) ) {
				$earliestIndex = 255; // We have to satisfy the first open double that was played. This is a safe number to be less than.
				foreach($GLOBALS['OPEN_DOUBLE'] as $index => $value) {
					if($index < $earliestIndex)
						$earliestIndex = $index;
				}
				
				$location = $GLOBALS['OPEN_DOUBLE'][$earliestIndex]["location"];
				$number = $GLOBALS['OPEN_DOUBLE'][$earliestIndex]["number"];
				
				//If the open double is on our train, AND our train length is at least 1, just call playHeadOfTrain.
				if( ($location == '0') && ($GLOBALS['ourTrainLength'] >= 1) )
					return playHeadOfTrain(); // playDomino unsets the OPEN_DOUBLE.
					
				if( playUnconnected($location, $number) ) { // If trainLength == 0, this will try all of our dominoes...
					// Satisfy the double...
					unset($GLOBALS['OPEN_DOUBLE'][$earliestIndex]); // Should've been unset by playDomino also...
					return TRUE;
					
				} else if( $GLOBALS['ourTrainLength'] >= 1 ) { // ...and if we have a train, this will try all of them....
				
					if( playLastFromTrain($location, $number) ) { // Couldn't play a non-connected, so try from the back of our train.
						// Satisfy the double...
						unset($GLOBALS['OPEN_DOUBLE'][$earliestIndex]);
						return TRUE;
					}
					
				}
				// ...so if we get here, we've tried all of our dominoes and can't satisfy the double - we draw.
				
				echo "Can't satisfy the double, we have to draw.\n";
				return computerDraw(); 
				
		} // !empty($GLOBALS['OPEN_DOUBLE'])

		// Full train, OR, our train is open and we need to try and close it AND we have train length of at least 1.
		if( ($GLOBALS['ourTrainLength'] == count($GLOBALS['ourDominoes'])) || (  ($GLOBALS['player'][0]["open"] == 1) && ($GLOBALS['ourTrainLength'] >= 1) ) ) {
			return playHeadOfTrain();
				
			// Step 2	
		} else if( $GLOBALS['ourTrainLength'] >= 1 ) {
				
			if( empty($GLOBALS['mexicanTrain']) ) { // Try playUnconnected to start the mexican train.
					
				echo "Trying playUnconnected on empty mexican train.\n";
				if ( playUnconnected('mexican', $GLOBALS['currentRound']) )
					return TRUE;
			} else {
				// Try playUnconnectedDouble on the mexican train.
				$lastIndex = count($GLOBALS['mexicanTrain'][0]) - 1; // Should always be the last element.
				$matchNumber = $GLOBALS['mexicanTrain'][0][$lastIndex][1];
				if ( playUnconnectedDouble('mexican', $matchNumber) )
					return TRUE;
			}
				
			// Try playUnconnectedDouble on everyone else's train.
			foreach($GLOBALS['playerTrain'] as $index => $value) {
				if($index == 0)
					continue; // Don't try our own.		
				$lastIndex = count($GLOBALS['playerTrain'][$index]) - 1; // Should always be the last element.
				$matchNumber = $GLOBALS['playerTrain'][$index][$lastIndex][1];
				if ( playUnconnectedDouble($index, $matchNumber) )
					return TRUE;		
			}
				
			// Begin step 3. Sort our unconnected's by heaviest first and try to play them everywhere.
			if ( playHeaviestUnconnected() )
				return TRUE;

			// Can't play any of our non-connected dominoes, try to play on our train.
			if( playHeadOfTrain() ) // This should always return TRUE since our train length is >= 1
				return TRUE;
					
			// We couldn't play anywhere else - time to draw.
			echo "Should never have gotten here since ourTrainLength >= 1 ?\n";
			return computerDraw(); // We should never get here.
		
		} else { // We don't have a train, so essentially everything is unconnected. Play heaviest tries everywhere.
			
			// Try playUnconnectedDouble on the mexican train.
			$lastIndex = count($GLOBALS['mexicanTrain'][0]) - 1; // Should always be the last element.
			$matchNumber = $GLOBALS['mexicanTrain'][0][$lastIndex][1];
			if ( playUnconnectedDouble('mexican', $matchNumber) )
				return TRUE;
			
			// Try playUnconnectedDouble on everyone else's train.
			foreach($GLOBALS['playerTrain'] as $index => $value) {
				if($index == 0)
					continue; // Don't try our own.		
				$lastIndex = count($GLOBALS['playerTrain'][$index]) - 1; // Should always be the last element.
				$matchNumber = $GLOBALS['playerTrain'][$index][$lastIndex][1];
				if ( playUnconnectedDouble('mexican', $matchNumber) )
					return TRUE;		
			}
			
			if ( playHeaviestUnconnected() )
				return TRUE;
			
			// No plays, time to draw.
			return computerDraw();
			
		}
	}

	function computerDraw() {
		
		echo "No play, we have to draw.\n";
		readDominoes(1);
		$GLOBALS['ourDominoes'] = array_values($GLOBALS['ourDominoes']);
		$GLOBALS['player'][0]['rackCount']++;
		// Re-sort
		$lastIndex = count($GLOBALS['playerTrain'][0]) - 1; // Should always be the last element.
		$matchNumber = $GLOBALS['playerTrain'][0][$lastIndex][1]; // Right side number
		sortWrapper($matchNumber);
		return FALSE;
	}
	
	function playHeadOfTrain() {
		
		$dominoArray = array();
		$dominoArray[] = array_shift($GLOBALS['ourDominoes']); // remove from head of our rack
		if( playDomino(0, $dominoArray, 0) ) {
			// playDomino decrements our rackCount, but we need to
			// decrement ourTrainLength as well.
			$GLOBALS['ourTrainLength']--;
			$GLOBALS['player'][0]["open"] = 0;
			echo "playHeadOfTrain played ";
			showDominoes($dominoArray, 0);
			echo "\n";
			return TRUE;
		} else { // Failed for some reason.
			array_unshift($GLOBALS['ourDominoes'], $dominoArray[0]);
			return FALSE;
		}
	}
	
	function playUnconnectedDouble($locationIndex, $numberMatch) {
		
		// First see if we have an unconnected double pair.
		$dominoArray = NULL;
		echo "Entered playUnconnectedDouble.\n";
		
		$isDouble = FALSE;
		$unconnectedArray = NULL;
		// Start at the index after our trainLength, until our dominoLength.
		for( $i = $GLOBALS['ourTrainLength'] ; $i <= count($GLOBALS['ourDominoes']) - 1; $i++ ) {
			$unconnectedArray[$i] = $GLOBALS['ourDominoes'][$i];
			if( ($unconnectedArray[$i][0] == $unconnectedArray[$i][1]) && ( $unconnectedArray[$i][0] == $numberMatch))
				$isDouble = $i; // One of these is a double that matches where we wanna play.
		}

		if(empty($unconnectedArray)) // We have no unconnected to play.
			return FALSE;
		
		if($isDouble !== FALSE) {
			// We have a double, if it's got an adjacent partner we want to play the double.
			if( findAdjacents($numberMatch, $unconnectedArray) ) {
				
				$dominoArray[] = $unconnectedArray[$isDouble];
				if( playDomino(0, $dominoArray, $locationIndex) ) {
					//readline("About to unset in playUnconnected."); 
					unset($GLOBALS['ourDominoes'][$isDouble]);
					$GLOBALS['ourDominoes'] = array_values($GLOBALS['ourDominoes']);
					echo "playUnconnectedDouble played ";
					showDominoes($dominoArray, 0);
					echo "\n";
					return TRUE;
				}	
			}	
		}
		
		return FALSE;
	}
	
	function playUnconnected($locationIndex, $numberMatch) {
			$dominoArray = NULL;
			echo "Entered playUnconnected.\n";
			
			$unconnectedArray = NULL;
			// Start at the index after our trainLength, until our dominoLength.
			for( $i = $GLOBALS['ourTrainLength'] ; $i <= count($GLOBALS['ourDominoes']) - 1; $i++ ) {
				$unconnectedArray[$i] = $GLOBALS['ourDominoes'][$i];
			}
			
			if(empty($unconnectedArray)) // We have no unconnected to play.
				return FALSE;
			
			// See if any of these have numberMatch in them.
			if( findAdjacents($numberMatch, $unconnectedArray)) { 
				// Ok, there is one - but which one:
				foreach($unconnectedArray as $index => $value) {
					if(isAdjacent($numberMatch, $index, $unconnectedArray)) {
						$matchIndex = $index;
					}
				}
				
				// Try to play it.
				$dominoArray = NULL;
				$dominoArray[] = $unconnectedArray[$matchIndex];
				if( playDomino(0, $dominoArray, $locationIndex) ) {
					//readline("About to unset in playUnconnected."); 
					unset($GLOBALS['ourDominoes'][$matchIndex]);
					$GLOBALS['ourDominoes'] = array_values($GLOBALS['ourDominoes']);
					echo "playUnconnected played ";
					showDominoes($dominoArray, 0);
					echo "\n";
					return TRUE;
				}
			}
			
			return FALSE; // No unconnected dominoes are adjacent to numberMatch.
			
	}

	function playHeaviestUnconnected(){
		
		echo "Trying playHeaviestUnconnected.\n";
		
		$heaviestIndex = '';
		$heaviest = '';
		
		$unconnectedArray = NULL;
		// Start at the index after our trainLength, until our dominoLength.
		for( $i = $GLOBALS['ourTrainLength'] ; $i <= count($GLOBALS['ourDominoes']) - 1; $i++ ) {
			$unconnectedArray[$i] = $GLOBALS['ourDominoes'][$i];
		}
			
		if(empty($unconnectedArray)) // We have no unconnected to play.
			return FALSE;
		
		while(!empty($unconnectedArray)) {
			foreach($unconnectedArray as $index => $value) {
				$weight = $unconnectedArray[$index][0] + $unconnectedArray[$index][1];
				if ($weight >= $heaviest) {
					$heaviest = $weight;
					$heaviestIndex = $index;
				}
			}
			
			// Try to play heaviestIndex.
			$dominoArray = NULL;
			$dominoArray[] = $unconnectedArray[$heaviestIndex];
				
				echo "Trying playHeaviestUnconnected on mexican.\n";	
				if( playDomino(0, $dominoArray, 'mexican')) { // Try to either create, or play on the mexican train.
					unset($GLOBALS['ourDominoes'][$heaviestIndex]);
					$GLOBALS['ourDominoes'] = array_values($GLOBALS['ourDominoes']);
					echo "playHeaviestUnconnected played ";
					showDominoes($dominoArray, 0);
					echo "\n";
					return TRUE;
				} else { // Mexican train isn't there yet, or we couldn't play there. Try all the other players.
					
					foreach($GLOBALS['playerTrain'] as $index => $value) {
						if($index == 0)
							continue; // Don't try our own.
						echo "Trying playHeaviestUnconnected on a player's train.\n";
						if( playDomino(0, $dominoArray, $index) ) {
							unset($GLOBALS['ourDominoes'][$heaviestIndex]);
							$GLOBALS['ourDominoes'] = array_values($GLOBALS['ourDominoes']);
							echo "playHeaviestUnconnected played ";
							showDominoes($dominoArray, 0);
							echo "\n";
							return TRUE;
						}	
					}	
				}
				
			// unset this from unconnected and try the next heaviest.
			unset($unconnectedArray[$heaviestIndex]);
			$heaviestIndex = '';
			$heaviest = '';
		}

		// Guess not.
		return FALSE;
	}

	function playLastFromTrain($locationIndex, $numberMatch) {
			/**
			 * Tries to play the last domino in our train - usually
			 * to try and satisfy a double without messing our train
			 * up.
			 */
			echo "Entered playLastFromTrain.\n";
			
			$reverseTrain = NULL;
			// Start at the index trainLength -1 , until 0.
			for( $i = ($GLOBALS['ourTrainLength'] - 1) ; $i >= 0; $i-- ) {
				if(isAdjacent($numberMatch, $i, $GLOBALS['ourDominoes'])) {
					// Try to play.
					$dominoArray = NULL;
					$dominoArray[] = $GLOBALS['ourDominoes'][$i];
					if( playDomino(0, $dominoArray, $locationIndex)) {
						//readline("About to unset in playLastFromTrain.");
						unset($GLOBALS['ourDominoes'][$i]);
						$GLOBALS['ourDominoes'] = array_values($GLOBALS['ourDominoes']);
						$GLOBALS['ourTrainLength']--;
						echo "playLastFromTrain played ";
						showDominoes($dominoArray, 0);
						echo "\n";
						echo "Re-sorting.\n";
						$lastIndex = count($GLOBALS['playerTrain'][0]) - 1; // Should always be the last element.
						$matchNumber = $GLOBALS['playerTrain'][0][$lastIndex][1];
						sortWrapper($matchNumber);
						return TRUE;
					}
				}
			}
			
			return FALSE; // Couldn't play any in our train.
			
	}

	function playDomino($playerIndex = NULL, $dominoArray = NULL, $locationIndex = NULL) {
			/**
			 * Register a play by a player.
			 * @param playerIndex The player number making the play.
			 * @param dominoArray A domino array.
			 * @param locationIndex Where they intend to make the play.
			 * @return Boolean If the play was legal or not.
			 * 
			 * If playerIndex == locationIndex, the player is attempting
			 * to play on their own train, which is always ok provided
			 * it is a legal play. A call to isAdjacent will determine
			 * if a play on their own train is legal.
			 * If a player is attempting to play on another player's train,
			 * we must check to make sure that player's train is open.
			 * If locationIndex is the special "mexican", we check to
			 * see if the mexican train has been instantiated yet
			 * (as a special player). This train is always open.
			 */
			
			/**
			 * First we look at this domino
			 */
			//@TODO - Need to keep track of who played the double, because their next play can be something other than a satisfier - alternative double rule.
			if($GLOBALS['override'] == 0) {
				$wasDouble = FALSE;
				if( $dominoArray[0][0] == $dominoArray[0][1]) {
					// OPEN_DOUBLE is an array, because there can be more than one, so piss off Highlander.
					$GLOBALS['OPEN_DOUBLE'][] = array("location" => $locationIndex, "number" => $dominoArray[0][0]); // Set to be location and double number.
					$wasDouble = TRUE;
				}
			
				// If there exists an open double, and we don't match it, return FALSE.
				if( (!empty($GLOBALS['OPEN_DOUBLE'])) && ($wasDouble == FALSE) ) { // You can play back to back doubles.
				
					if ( (array_search( array("location" => $locationIndex, "number" => $dominoArray[0][0]), $GLOBALS['OPEN_DOUBLE']) === FALSE) 
				  	&& (array_search( array("location" => $locationIndex, "number" => $dominoArray[0][1]), $GLOBALS['OPEN_DOUBLE']) === FALSE) ) {
						// Neither of the numbers on this domino match any of the open double numbers at the desired play location.
						echo "Unsatisfied double exists.\n";
						return FALSE;
					} else {
						// We're trying to play a domino that does satisfy a double. Remove the appropriate OPEN_DOUBLE.
						// One of these will match, and be the index we need to unset in OPEN_DOUBLE.
						$leftNumber = array_search( array("location" => $locationIndex, "number" => $dominoArray[0][0]), $GLOBALS['OPEN_DOUBLE']);
						if ($leftNumber === FALSE) {
							$rightNumber = array_search( array("location" => $locationIndex, "number" => $dominoArray[0][1]), $GLOBALS['OPEN_DOUBLE']);
							unset($GLOBALS['OPEN_DOUBLE'][$rightNumber]);
						} else {
							unset($GLOBALS['OPEN_DOUBLE'][$leftNumber]);
						}
					}
				}
			} else {
				$wasDouble = FALSE; // override.
			}
			// Play continues.
			
			if( (string)$playerIndex == (string)$locationIndex) { // Trying to play on their own train.
			
				$lastIndex = count($GLOBALS['playerTrain'][$locationIndex]) - 1; // Should always be the last element.
				$matchNumber = $GLOBALS['playerTrain'][$locationIndex][$lastIndex][1];
				if( isAdjacent($matchNumber, 0, $dominoArray) ) {
					// See if it needs a flip.
					if($matchNumber != $dominoArray[0][0])
						$dominoArray[0] = flipDomino($dominoArray[0]);
					
					// Decrement their rackCount.
					$GLOBALS['player'][$playerIndex]['rackCount']--;
					// Attach domino to their train.
					$GLOBALS['playerTrain'][$playerIndex][] = $dominoArray[0];
					
					return TRUE;
				} else {
					if($wasDouble) // If the play fails and it was a double, remove the open double.
						array_pop($GLOBALS['OPEN_DOUBLE']);
					
					return FALSE; // Illegal play - not adjacent.
				}
				
			}

			if ( ($playerIndex != $locationIndex) && ($locationIndex != 'mexican') ) {
				// They're trying to play on someone else's train. Is it open?
				if( $GLOBALS['player'][$locationIndex]["open"] == 0 ) {
					echo "That player's train is not open.\n";
					if($wasDouble) // If the play fails and it was a double, remove the open double.
						array_pop($GLOBALS['OPEN_DOUBLE']);
					return FALSE;
				}
				
				// It's open - attempt to play.
				$lastIndex = count($GLOBALS['playerTrain'][$locationIndex]) - 1; // Should always be the last element.
				$matchNumber = $GLOBALS['playerTrain'][$locationIndex][$lastIndex][1];
				if( isAdjacent($matchNumber, 0, $dominoArray) ) {
						
					// See if it needs a flip.
					if($matchNumber != $dominoArray[0][0])
						$dominoArray[0] = flipDomino($dominoArray[0]);
					
					// Decrement their rackCount.
					$GLOBALS['player'][$playerIndex]['rackCount']--;
					// Attach domino to other person's train.
					$GLOBALS['playerTrain'][$locationIndex][] = $dominoArray[0];
					// If they played on our train (those bastards!), re-sort and make a notification.
					if($locationIndex == 0) {
						echo "Someone played on our train. Re-sorting based on new match number.\n";
						$lastIndex = count($GLOBALS['playerTrain'][0]) - 1; // Should always be the last element.
						$matchNumber = $GLOBALS['playerTrain'][0][$lastIndex][1];
						sortWrapper($matchNumber);
					}
					
					return TRUE;
					
				} else {
					if($wasDouble) // If the play fails and it was a double, remove the open double.
						array_pop($GLOBALS['OPEN_DOUBLE']);
					
					return FALSE; // Illegal play - not adjacent.
				}
			}

			if( $locationIndex == 'mexican' ) {
				// If it doesn't exist, try to create it.
				$wasCreated = FALSE;
				if( empty($GLOBALS['mexicanTrain']) ) {
					$GLOBALS['mexicanTrain'][] = '';
					$matchNumber = $GLOBALS['currentRound']; // Just created, so we match this.
					$wasCreated = TRUE;
				} else {
					// Match what's there now.
					$lastIndex = count($GLOBALS['mexicanTrain'][0]) - 1; // Should always be the last element.
					$matchNumber = $GLOBALS['mexicanTrain'][0][$lastIndex][1];
				}
				
				if( isAdjacent($matchNumber, 0, $dominoArray) ) {
					// See if it needs a flip.
					if($matchNumber != $dominoArray[0][0]) {
						//readline("entered needs a flip");
						$dominoArray[0] = flipDomino($dominoArray[0]);
					}
					// Decrement their rackCount.
					$GLOBALS['player'][$playerIndex]['rackCount']--;
					// Attach domino to mexican train.
					$GLOBALS['mexicanTrain'][0][] = $dominoArray[0];
					return TRUE;
				} else {
					if($wasDouble) // If the play fails and it was a double, remove the open double.
						array_pop($GLOBALS['OPEN_DOUBLE']);
					if($wasCreated) // If play fails and the mexican was created, un-create it.
						$GLOBALS['mexicanTrain'] = array();
					
					return FALSE; // Illegal play - not adjacent.
				}
			}
	}
	
	function showBoardInfo() {
			foreach($GLOBALS['playerTrain'] as $index => $player) {
				showPlayer($index);
				echo "Current train:\n";
				showDominoes($GLOBALS['playerTrain'][$index]);
			}
			
			if(!empty($GLOBALS['mexicanTrain'])) {
				echo "Mexican Train:\n";
				showDominoes($GLOBALS['mexicanTrain'][0]);
			}
	}
	
	function showPlayer($index) {
			echo "Player " . $index . ": " . $GLOBALS['player'][$index]["name"] . "\n";
		}

	function matchNumber($domino) {
			// Provided a domino index, return the right number.
			return $domino[1];
	}	

	function showPlayers() {
		
			echo "Players:\n";
			foreach($GLOBALS['player'] as $index => $value) {
				echo "Player " . $index . ": " . $value["name"] . "\n";
				echo "Score: " . $value["score"] . "\n";
				echo "Tiles left: " . $value["rackCount"] . "\n";
				echo "Train open: " . $value["open"] . "\n";
			}
	}

	function createGame ($size, $numPlayers  ) {

		$DOUBLE_SIX_PLAYERS = array(2, 3, 4);
		$DOUBLE_NINE_PLAYERS = array(2, 3);
		$DOUBLE_TWELVE_PLAYERS = array(3, 4, 5, 6, 7, 8);
		$DOUBLE_FIFTEEN_PLAYERS = array(9, 10, 11, 12);
		$DOUBLE_EIGHTEEN_PLAYERS = array(13, 14);

		if (($size == 6) && (!in_array($numPlayers, $DOUBLE_SIX_PLAYERS))) {
			throw new Exception ("Double Six must have exactly 2-4 players.\n"  );
		} else if (($size == 9) && (!in_array($numPlayers, $DOUBLE_NINE_PLAYERS))) {
			throw new Exception ("Double Nine must have 2-3 players.\n"  );
		} else if (($size == 12) && (!in_array($numPlayers, $DOUBLE_TWELVE_PLAYERS))) {
			throw new Exception ("Double Twelve must have 3-8 players.\n"  );
		} else if (($size == 15) && (!in_array($numPlayers, $DOUBLE_FIFTEEN_PLAYERS))) {
			throw new Exception ("Double Fifteen must have 9-12 players.\n"  );
		} else if (($size == 18) && (!in_array($numPlayers, $DOUBLE_EIGHTEEN_PLAYERS))) {
			throw new Exception ("Double Eighteen must have 13-14 players.\n"  );
		}

	}
	
	function createPlayer($name, $numToDraw) {
		array_push( $GLOBALS['player'], array( "name" => $name, "score" => 0, "rackCount" => $numToDraw, "open" => 0 ) );
		$GLOBALS['playerTrain'][] = '';
	}
	
	function readDominoes($numToDraw) {
		
		/**
		 * Reads in a player's dominoes.
		 * This function should be called
		 * at the start of each new round, and
		 * whenever a player has to draw.
		*/ 
			echo "Ready for you to enter your " . $numToDraw . " dominoes after you draw them.\n";
			echo "Enter your dominoes one at a time like so - x,y\n";
			for($i = 0; $i < $numToDraw; $i++) {
				$tmpDomino = readline("Enter domino " . ($i+1) . ": ");
				$tmpDomino = trim($tmpDomino);
				$tmpDominoArray = explode(',', $tmpDomino);
				array_push($GLOBALS['ourDominoes'], array( 0 => $tmpDominoArray[0], 1 => $tmpDominoArray[1]) );
			}
	}
	
	function showDominoes($dominoes, $index = NULL) {
		
		/**
			 * Pass in an index to display just one domino
			 * like [ 6 | 7 ], or no index to display the
			 * working set - @deprecated - we're not using a working set in the revision.
			 * 
			 * If dominoes is not passed, just the full working set.
			 * If a player's set is passed in, show their dominoes.
			 * Show a players played dominoes by showDominoes(self::$_playerTrain[player_index_number])
			 */
			
			if( isset($index) ) {
				echo "[ " . $dominoes[$index][0] . " | " . $dominoes[$index][1] . " ]";
				return;
			}
			
			foreach($dominoes as $k => $domino) {
				echo "#".$k."[ ";
				foreach($domino as $index => $number) {
					echo $number;
					if(isset($domino[$index+1])) {
						echo " | ";
					} else {
						echo " ] ";
					}
				}
			}
			echo "\n";
			
			return;
	}
	
	function sortWrapper($currentRound) {
				
			/**
			 * Sort longest train.
			 * Using our start number, try all trains with a goal
			 * of all end numbers 0 - 12. No sense in seeking
			 * to an end number of a domino we don't have though,
			 * so check each number to make sure we have it - if
			 * we don't, just continue.
			 */
			
			$GLOBALS['longest'] = array();
				
			for($i=0; $i<= $GLOBALS['gameSize']; $i++) {
				if(findAdjacents($i, $GLOBALS['ourDominoes'])) { 
					sortDominoes($GLOBALS['ourDominoes'], $currentRound, $i);
				}	
			}
			
			$diff = array_diff_key($GLOBALS['ourDominoes'], $GLOBALS['longest']);
			$new = array_merge($GLOBALS['longest'], $diff);
			$GLOBALS['ourDominoes'] = '';
			$GLOBALS['ourDominoes'] = $new;
			
			$GLOBALS['ourTrainLength'] = count($GLOBALS['longest']);
			
			echo "Train length: " . $GLOBALS['ourTrainLength'] . "\n";
	}

	function findAdjacents ($numberMatch, $dominoes  ) {
			// Returns TRUE if there is at least 1 domino
			// that will match numberMatch.
			foreach ( $dominoes as $index => $value ) {

				if (isAdjacent($numberMatch, $index, $dominoes))
					return TRUE;
			}

			return FALSE; // No adjacents.
	}

	function isAdjacent ($numberMatch, $index, $dominoes  ) {
			/**
			 * Return true if a domino matches our number.
		 	 * hasChain is removing dominoes from the array as
		 	 * it uses them, but we also don't want it to stop when it finds
		 	 * the first matching end number - we want the longest chain.
		 	*/
			if ( ( (string)$dominoes[$index][0] == (string)$numberMatch ) || ( (string)$dominoes[$index][1] == (string)$numberMatch ) )
				return TRUE;

			return FALSE; // Not adjacent.
	}
	
	function sortDominoes ($dominoes, $start, $end  ) {
			$chosen = array();
			return hasChain($dominoes, $chosen, $start, $end);
	}
	
	function hasChain ($dominoes, $chosen, $start, $end  ) {

			//if ( count($chosen) > count($GLOBALS['longest']) ) { FOR COMMENTED TIE BREAK BELOW
			if ( count($chosen) >= count($GLOBALS['longest']) ) {
					
				$GLOBALS['longest'] = $chosen;
			/**
			 * We will need a tie breaker in case two
			 * potential paths are the same length, but one might use
			 * more dots than the other.
			 * @TODO - This tie breaker code was giving me problems. Maybe later.
			 */
				
			} /*else if( count($chosen)  == count($GLOBALS['longest']) ) { // Total up the nips.
				$chosenTotal = 0;
				$longestTotal = 0;
					
				foreach($chosen as $index => $value) {
					$chosenTotal += ($value[0] + $value[1]);
				}
					
				foreach($GLOBALS['longest'] as $index => $value) {
					$longestTotal += ($value[0] + $value[1]);
				}
					
				if ( $chosenTotal > $longestTotal )
					$GLOBALS['longest'] = $chosen; // Promote chosen to longest because it uses up more nips.
			}*/
				
			if (($start == $end) && !findAdjacents($end, $dominoes)) {
				return TRUE;
			} else {
				for ( $i = 0; $i < count($dominoes); $i++ ) {

					$d = $dominoes[$i];
					unset($dominoes[$i]);

					if ($d[0] == $start) {

						$chosen[$i] = $d;

						if (hasChain($dominoes, $chosen, $d[1], $end)) {
							return TRUE;
						}

						array_pop($chosen);

					} elseif ($d[1] == $start) {

						$d = flipDomino($d);
						$chosen[$i] = $d;

						if (hasChain($dominoes, $chosen, $d[1], $end)) {
							return TRUE;
						}

						array_pop($chosen);

					}

					$dominoes[$i] = $d;
				}

				return FALSE;

			}

	}

	function flipDomino ($domino  ) {
			$d[0] = $domino[1];
			$d[1] = $domino[0];
			return $d;
	}
	
?>