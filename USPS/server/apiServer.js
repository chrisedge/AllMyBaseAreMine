var express = require('express'),
	https = require('https'),
	fs = require('fs'),
	mongoskin = require('mongoskin'),
	bodyParser = require('body-parser'),
	forge = require('node-forge');

var app = express();
app.use(bodyParser.json());

// For SSL
var key = fs.readFileSync('apiServer-key.pem');
var cert = fs.readFileSync('apiServer-cert.pem');
var options = {
    key: key,
    cert: cert
};

// Connect to the usps MongoDB database.
var db = mongoskin.db('mongodb://@localhost:27017/usps', {safe:true});

// This API server is dealing exclusively with the tdd, so establish
// it as our route path.
app.param('collectionName', function(req, res, next, collectionName) {
	req.collection = db.collection(collectionName);
	return next();
});

app.get('/', function (req, res) {
    res.send('Error\r\n');
});

// Parse a POST to /tdd/auth with subscriberID=0005551212
app.post('/:collectionName/auth', function(req, res, next) {
	//console.log('req.body: ', req.body);
	if (!req.body.subscriberID) return res.status(400).send('Invalid data\r\n');
	console.log('subscriberID: ', req.body.subscriberID);
	//res.writeHead(200);
	if(!req.body.challengeToken) { // No token yet...
		// Retrieve the device public key for this subscriberID.
		req.collection.findOne({subscriberID:req.body.subscriberID}, function(err, result) {
    		//console.log('devicePublicKey');
    		//console.log(result.devicePublicKey);
    		if(result.devicePublicKey) {
    			// Generate a random string to be used as the challenge..
    			var randomChallenge = forge.util.bytesToHex(forge.random.getBytesSync(16));
    			// Create a random temporary challenge token for this client to use for providing a reponse.
    			var challengeToken = forge.util.bytesToHex(forge.random.getBytesSync(16));
    			// Store the plaintext challenge in the challenge collection with the access token as the key.
    			db.collection('challenge').insert({createdAt: new Date(), token: challengeToken, challenge: randomChallenge}, function(err, result) {
    				if (err) throw err;
    				if (result) console.log('Added!');
				});
				console.log('plaintext challenge: ', randomChallenge);
				// Encrypt the challenge with the device public key.
				var pubKey = forge.pki.publicKeyFromPem(result.devicePublicKey);
				var encryptedChallenge = forge.util.encode64(pubKey.encrypt(randomChallenge));
				var response = {token: challengeToken, challenge: encryptedChallenge};
    			//res.status(200).send('response: ', response);
    			res.status(200);
    			res.write(JSON.stringify(response), 'utf8');
    			console.log('sent response: ', response);
				res.end();
    		} else {
    			res.status(401).send('Invalid subscriberID\r\n');
    			res.end();
    		};
		});
	} else { // They have a challengeToken, so they want to present a response.
		// Get the challenge from the challenge collection using the token as the key.
		db.collection('challenge').findOne({token: req.body.challengeToken}, function(err, result) {
			if (err) throw err;
			if(!result) {
				res.status(404).send('Invalid or expired token\r\n');
				res.end();
			} else if(result.challenge == req.body.response) {
				// Create an access token for them.
				var accessToken = forge.util.bytesToHex(forge.random.getBytesSync(16));
				// Store it in the access collection.
				db.collection('access').insert({createdAt: new Date(), token: accessToken}, function(err, result) {
    				if (err) throw err;
    				if (result) console.log('Added!');
				});
				// Send it back to them.
				var response = {token: accessToken};
				console.log('auth success. returning access token: ', response);
    			res.status(200).send(response);
				res.end();
			};
		});
		
	};
});

// Parse a POST to /tdd/update
app.post('/:collectionName/update', function(req, res, next) {
	if (!req.body.subscriberID) return res.status(400).send('Invalid data\r\n');
	//console.log('subscriberID: ', req.body.subscriberID);
	// Validate their access token.
	db.collection('access').findOne({token: req.body.accessToken}, function(err, result) {
			if (err) throw err;
			if(!result) {
				var response = {result: 0, message: 'Invalid or expired token'};
				res.status(404).send(response);
				res.end();
			} else if(result.token == req.body.accessToken){
				// console.log('matched access token: ' + result.token + ' = ' + req.body.accessToken );
				// Update their IP.
				req.collection.update({subscriberID:req.body.subscriberID}, {$set:{lastKnownIP:req.body.IPaddress}}, function(err, result) {
    				//console.log('update result: ', result);
    				if (!err) {
        				var response = {result: 1};
						res.status(200).send(response);
						res.end();
    				} else {
    					var response = {result: 0, message: 'Update failed'};
						res.status(501).send(response);
						res.end();
    				}
				});
				
			} else {
				var response = {result: 0, message: 'Invalid or expired token'};
				res.status(401).send(response);
				res.end();
			};
	});
	
});

// Parse a POST to /tdd/alias
app.post('/:collectionName/alias', function(req, res, next) {
	if (!req.body.subscriberID) return res.status(400).send('Invalid data\r\n');
	// Validate their access token.
	db.collection('access').findOne({token: req.body.accessToken}, function(err, result) {
			if (err) throw err;
			if(!result) {
				var response = {result: 0, message: 'Invalid or expired token'};
				res.status(404).send(response);
				res.end();
			} else if(result.token == req.body.accessToken){
				// console.log('matched access token: ' + result.token + ' = ' + req.body.accessToken );
				// Update their alias.
				// To do an update, you have to retrieve the existing record, and add new information
				// to it.
				db.collection('tdd').findOne({subscriberID: req.body.subscriberID}, function(err, result) {
					var obj = result; // Copy the existing record.
					obj['deliveryAliases'].push({
						'userid':req.body.userid,
						'firstName':req.body.firstName,
						'middleName':req.body.middleName,
						'lastName':req.body.lastName,
						'publicKey':req.body.publicKey,
						'postageID':req.body.postageID
					});
					db.collection('tdd').update({subscriberID: req.body.subscriberID}, {$set: obj}, function(err, result) {
						console.log('update result: ', result);
						
					});
				});
				var response = {result: 1};
				res.status(200).send(response);
				res.end();
				
				/*req.collection.update({subscriberID:req.body.subscriberID}, {$set:{lastKnownIP:req.body.IPaddress}}, function(err, result) {
    				//console.log('update result: ', result);
    				if (!err) {
        				var response = {result: 1};
						res.status(200).send(response);
						res.end();
    				} else {
    					var response = {result: 0, message: 'Update failed'};
						res.status(501).send(response);
						res.end();
    				}
				});*/
				
			} else {
				var response = {result: 0, message: 'Invalid or expired token'};
				res.status(401).send(response);
				res.end();
			};
	});
	
});

https.createServer(options, app).listen(1169, function () {
	console.log('HTTPS server listening');
});
