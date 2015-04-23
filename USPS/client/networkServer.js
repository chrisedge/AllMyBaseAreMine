var express = require('express'),
	https = require('https'),
	fs = require('fs'),
	db = require('diskdb'),
	bodyParser = require('body-parser'),
	forge = require('node-forge');

var app = express();
app.use(bodyParser.json());

// For SSL
var key = fs.readFileSync('networkServer-key.pem');
var cert = fs.readFileSync('networkServer-cert.pem');
var options = {
    key: key,
    cert: cert
};

// Connect to the local diskdb collections.
db = db.connect('collections', ['device', 'inbox', 'catchall', 'outbox', 'users']);

app.get('/', function (req, res) {
    res.send('Error\r\n');
});

/*
The USPS servers connect to the devices by POSTing the unique
APIKEY=value, where value is a device-specific api key generated
on the device, and inserted into the TDD the first time the device
comes on-line:
var deviceAPIKey = forge.util.bytesToHex(forge.random.getBytesSync(32));
*/


// Parse a POST to /device/auth with deviceAPIKey=value
app.post('/device/auth', function(req, res, next) {
	if (!req.body.deviceAPIKey) return res.status(400).send('Invalid data\r\n');
	console.log('deviceAPIKey: ', req.body.deviceAPIKey);
	// Validate their API key.
	var device = db.device.findOne();
	if (device.deviceAPIKey == req.body.deviceAPIKey) {
		if(!req.body.challengeToken) { // No token yet...
			// Retrieve the USPS public key.
			var pubKey = forge.pki.publicKeyFromPem(device.publicKey);
			// Generate a random string to be used as the challenge..
	    	var randomChallenge = forge.util.bytesToHex(forge.random.getBytesSync(16));
	    	// Create a random temporary challenge token for this client to use for providing a reponse.
	    	var challengeToken = forge.util.bytesToHex(forge.random.getBytesSync(16));
	    	// Store the plaintext challenge in the device collection with the challenge token.
	    	db.device.save( { challengeToken: challengeToken, challenge: randomChallenge } );
	    	console.log('plaintext challenge: ', randomChallenge);
	    	// Encrypt the challenge with the USPS public key.
			var encryptedChallenge = forge.util.encode64(pubKey.encrypt(randomChallenge));
			var response = {token: challengeToken, challenge: encryptedChallenge};
			// Send the response back to the client.
			res.status(200);
	    	res.write(JSON.stringify(response), 'utf8');
	    	console.log('sent response: ', response);
			res.end();
		
		} else { // They have a challengeToken, so they want to present a response.
			// Validate their response. Get the challenge from the device collection using the token as the key.
			var device = db.device.findOne( { challengeToken: req.body.challengeToken })
			if(req.body.response == device.challenge) {
				// Delete the challengeToken.
				db.device.remove( {challengeToken: req.body.challengeToken}, false); // Multi = false
				// Create an access token.
				var accessToken = forge.util.bytesToHex(forge.random.getBytesSync(16));
				// Save it in the device collection.
				db.device.save({accessToken: accessToken});
				// Send it back.
				var response = {token: accessToken};
				console.log('auth success. returning access token: ', response);
	    		res.status(200).send(response);
				res.end();
			} else { // Failed to match.
				var response = {error: 'Unauthorized request'};
				console.log('Sent 401 - challenge reponse mismatch.')
				res.status(401).send(response);
				res.end();
			}			
		};
	} else { // Not our API key.
		var response = {error: 'Unauthorized request'};
		console.log('Sent 401 - invalid API key');
		res.status(401).send(response);
		res.end();
	}
});

// Parse a POST to /device/inbox with deviceAPIKey=value,accessToken=value,message=value
// Accessed by delivererClient.js
app.post('/device/inbox', function(req, res, next) {
	if(!req.body.deviceAPIKey) return res.status(400).send('Invalid data\r\n');
	// Validate their access token
	var device = db.device.findOne({accessToken: req.body.accessToken});
	if(!device) {
		var response = {error: 'Invalid or expired token'};
		res.status(401).send(response);
		res.end();
	} 
	//console.log('req.body', req.body);
	var messages = req.body.messages;
	//console.log('length: ', messages.length);
	for (var i in messages) {
		//console.log(messages[i]);
		// First need to make sure there's an alias on this device that matches the toAlias value in the message.	
		var user = db.users.findOne({userid: messages[i].toAlias});
		if(!user) {
			//var response = {error: 'No such user'};
			//res.status(404).send(response);
			//res.end();
			// No matching alias found, so put this in the catchall collection.
			var catchall = {
				postmarkTimestamp: messages[i].postmarkTimestamp,
				deliveryTimestamp: Math.floor(Date.now() / 1000),
				services: messages[i].services,
				messageData: messages[i].message.messageData,
				fromPhysicalAddress: messages[i].fromPhysicalAddress,
				fromFullName: messages[i].fromFullName,
				fromAlias: messages[i].fromAlias,
				fromSubscriberID: messages[i].fromSubscriberID,
				toPhysicalAddress: messages[i].toPhysicalAddress,
				toFullName: messages[i].toFullName,
				toAlias: messages[i].toAlias,
				toSubscriberID: messages[i].toSubscriberID,
				toAliasID: ''
			};
			db.catchall.save(catchall);
			//console.log('catchall: ', catchall);

		} else {
			//console.log('messageData: ', messages[i].message.messageData);
			// Store the message with decrypted messageData.
			var privateKey = forge.pki.privateKeyFromPem(user.privateKey);
			var messageKey = privateKey.decrypt(forge.util.decode64(messages[i].message.key));
			var decipher = forge.cipher.createDecipher('AES-CBC', messageKey);
			var iv = forge.util.decode64(messages[i].message.iv);
			var messageData = forge.util.hexToBytes(messages[i].message.messageData);
			decipher.start({iv: iv});
			var buffer = forge.util.createBuffer();
			buffer.putBytes(messageData);
			decipher.update(buffer);
			decipher.finish();
			// outputs decrypted hex
			//console.log('decrypted: ', decipher.output.data);
			var messageText = forge.util.encode64(decipher.output.data);

			var inbox = {
				postmarkTimestamp: messages[i].postmarkTimestamp,
				deliveryTimestamp: Math.floor(Date.now() / 1000),
				services: messages[i].services,
				messageData: messageText,
				fromPhysicalAddress: messages[i].fromPhysicalAddress,
				fromFullName: messages[i].fromFullName,
				fromAlias: messages[i].fromAlias,
				fromSubscriberID: messages[i].fromSubscriberID,
				toPhysicalAddress: messages[i].toPhysicalAddress,
				toFullName: messages[i].toFullName,
				toAlias: messages[i].toAlias,
				toSubscriberID: messages[i].toSubscriberID,
				toAliasID: user._id
			};
			db.inbox.save(inbox);
			//console.log('inbox: ', inbox);
				
		}
	}

	var response = {result: messages.length};
	console.log('success');
	res.status(200).send(response);
	res.end();

}); // app.post

// Parse a POST to /device/outbox with deviceAPIKey=value,accessToken=value
// Accessed by deliverClient.js to retrieve mail.
// We should return a JSON structure of message objects, including a count.
app.post('/device/outbox', function(req, res, next) {
	if(!req.body.deviceAPIKey) return res.status(400).send('Invalid data\r\n');
	// Validate their access token
	var device = db.device.findOne({accessToken: req.body.accessToken});
	if(!device) {
		var response = {error: 'Invalid or expired token'};
		res.status(401).send(response);
		res.end();
	} 
	
	// Get all the messages in the outbox.
	var messages = db.outbox.find();
	//console.log('messages: ', messages[0]._id);
	var total = db.outbox.count();
	//var response = {messageTotal: total, messages: messages };
	var response = {messages: messages };
	//@TODO - iterate over the messages object and db.outbox.remove each one.
	// db.outbox.remove() will also remove the collection and file, which we
	// don't want to do.
	console.log('delivered');
	res.status(200).send(messages);
	// Remove the item from the outbox, if there was one.
	if(messages[0]._id)
		db.outbox.remove({_id: messages[0]._id}, false);
	res.end();
}); // app.post


https.createServer(options, app).listen(1170, function () {
	console.log('HTTPS server listening');
});
