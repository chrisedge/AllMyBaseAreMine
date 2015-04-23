/**
 * This client will connect to all tablet devices
 * on a rotating schedule and deliver mail to them.
 * This client must first perform challenge-response
 * auth prior to issuing any other commands.
 *
 * This file should be called by the scheduler application
 * and passed in a subscriberID to connect to. @TODO
 *
 * The scheduler application should first check in the
 * mail storage database for devices that have mail
 * waiting to be delivered, and if so, call this file
 * with the subscriberID.
 *
 */

// A secure (TLS) https client
// From https://github.com/ZiCog/node-tls-example/blob/master/https-client.js
var https = require('https'), fs = require('fs'), mongoskin = require('mongoskin'), forge = require('node-forge');

// Connect to the usps MongoDB database.
var db = mongoskin.db('mongodb://@localhost:27017/usps', {
	safe : true
});

// First establish the device we're connecting to.
// Need to find it's lastKnownIP in the tdd by
// subscriberID.

var arguments = process.argv.slice(2);

var subscriberID = arguments[0];

// Establish common options for our API calls.
var httpOptions = {
	//host: APIServerIP,
	port : 1170,
	method : 'POST',
	headers : {
		'Content-Type' : 'application/json'
	},
	//cert: fs.readFileSync('apiServer-cert.pem'),
	rejectUnauthorized : false
};

// Since the nature of our challenge-response is synchronous, we use async.
// From: http://www.hacksparrow.com/node-js-async-programming.html
var async = require('async');

// Begin the challenge-response scenarion...
async.waterfall([

function(callback) {
	db.collection('tdd').findOne({
		subscriberID : subscriberID
	}, function(err, result) {
		if (err)
			throw err;
		//console.log(result);
		if (!result) {
			console.log('Could not locate subscriberID: ', subscriberID);
		} else {
			//console.log('result: ', result);
			if (result.lastKnownIP == '') {
				console.log('No current known IP for device: '.subscriberID);
			} else {
				lastKnownIP = result.lastKnownIP;
				//result.lastKnownIP = '127.0.0.1';
				//deliverMail(result);
				//console.log('result: ', result);
				deviceAPIKey = result.deviceAPIKey;
				callback(null, result);
			}
		}
	});
},

function(data, callback) {
	// First identify ourself with the deviceAPIKey we're connecting to.
	var devicePost = JSON.stringify({
		'deviceAPIKey' : deviceAPIKey
	});

	httpOptions.host = lastKnownIP;
	httpOptions.path = '/device/auth';
	var req = https.request(httpOptions, function(res) {
		res.on('data', function(d) {
			//console.log('data, d: ', d);
			//d.deviceAPIKey = data.deviceAPIKey;
			//d.privateKey = data.privateKey;
			// Pass this along as well.
			callback(null, d);
		});

	});

	req.on('error', function(e) {
		console.error(e);
	});

	req.write(devicePost);
	req.end();
},

function(data, callback) {
	// We now have a temporary challengeToken, and an encrypted challenge string.
	var challengeToken = JSON.parse(data.toString()).token;
	var challenge = JSON.parse(data.toString()).challenge;
	// Decrypt the base64 encoded challenge using the USPS private key.
	db.collection('keys').findOne({}, function(err, result) {
		var privateKey = forge.pki.privateKeyFromPem(result.privateKey);
		var response = privateKey.decrypt(forge.util.decode64(challenge));
		//console.log('plaintext response: ', response);
		// Prepare the response.
		var responsePost = JSON.stringify({
			'deviceAPIKey' : deviceAPIKey,
			'challengeToken' : challengeToken,
			'response' : response
		});

		var req = https.request(httpOptions, function(res) {
			res.on('data', function(d) {
				//d.deviceAPIKey = data.deviceAPIKey;
				// Pass this along.
				callback(null, d);
			});

		});

		req.on('error', function(e) {
			console.error(e);
		});

		req.write(responsePost);
		req.end();

	});
	// db.find

},

function(data, callback) {
	// If the last call was successful, we should have received our accessToken.
	accessToken = JSON.parse(data.toString()).token;
	//console.log('accessToken: ', accessToken);
	// We can now use this accessToken to deliver messages to this device.
	// First, get all of the messages for this device.
	db.collection('mail_storage').find({toSubscriberID: subscriberID}).toArray(function(err, result) {
		console.log('mail_storage result: ', result);
		var messagePost = JSON.stringify({
			'deviceAPIKey' : deviceAPIKey,
			'accessToken' : accessToken,
			'messages' : result
		});
		if(result == '') {
			console.log('skipping delivery')
			callback(null, result);
		} else {
			//console.log('messagePost: ', messagePost);
			// Make our POST to networkServer.js /device/inbox
			httpOptions.path = '/device/inbox';
			var req = https.request(httpOptions, function(res) {
				res.on('data', function(d) {
					// Once delivered, delete the message from mail_storage.
					db.collection('mail_storage').remove({toSubscriberID: subscriberID}, function(err, result) {
    					if (!err) console.log('Mail deleted.');
    					callback(null, d);
    				});
					
				});

			});
			
			req.on('error', function(e) {
				console.error(e);
			});

			req.write(messagePost);
			req.end();
		}

	});
	//db.find

},

function(data, callback) {
	// Issue a POST to /device/outbox to retrieve mail from the device.
	//console.log('accessToken:', accessToken);
	//console.log('data: ', data.toString());
	//console.log('APIKey: ', deviceAPIKey);
	var messagePost = JSON.stringify({
		'deviceAPIKey' : deviceAPIKey,
		'accessToken' : accessToken
	});
	
	httpOptions.path = '/device/outbox';
	var req = https.request(httpOptions, function(res) {
		res.on('data', function(d) {
			//console.log('d in outbox: ', JSON.parse(d.toString()));
			//if(JSON.parse(d.toString()) != '') {
				var message = JSON.parse(d.toString());
				console.log(message[0]);
				// Here we need to decrypt the message content from each message
				// with the USPS private key, and then re-encrypt with the destination
				// user's public key. Then, we store the message in the mail_storage collection.
				db.collection('keys').findOne({}, function(err, result) {
					var privateKey = forge.pki.privateKeyFromPem(result.privateKey);
					var messageKey = privateKey.decrypt(forge.util.decode64(message[0].message.key));
					var decipher = forge.cipher.createDecipher('AES-CBC', messageKey);
					var iv = forge.util.decode64(message[0].message.iv);
					var messageData = forge.util.hexToBytes(message[0].message.messageData);
					decipher.start({iv: iv});
					var buffer = forge.util.createBuffer();
	        		buffer.putBytes(messageData);
	        		decipher.update(buffer);
	        		decipher.finish();
	        		//console.log('decrypted: ', decipher.output.data);
	        		// Re-encrypt with the destination user's public key.
	        		db.collection('tdd').findOne({subscriberID: message[0].toSubscriberID}, function(err, result) {
	        			// First have to find a matching alias on this device.
	        			var aliases = result.deliveryAliases;
	        			for(var i in aliases) {
	        				if(aliases[i].userid == message[0].toAlias) {
	        					var pubKey = forge.pki.publicKeyFromPem(aliases[i].publicKey);
	        				}
	        			}
	        			//var pubKey = forge.pki.publicKeyFromPem(result.publicKey);
	        			var key = forge.random.getBytesSync(32);
	    				var iv = forge.random.getBytesSync(32);
	    				var cipher = forge.cipher.createCipher('AES-CBC', key);
	    				cipher.start({iv: iv});
	    				//console.log('decrypted: ', decipher.output.data)
	    				cipher.update(forge.util.createBuffer(decipher.output.data));
	    				cipher.finish();
	    				var encrypted = cipher.output;
	    				// Message text is encrypted. Now encrypt the key.
	    				var encryptedKey = forge.util.encode64(pubKey.encrypt(key));
	    				// Now update the values in the message.
	    				message[0].message.messageData = encrypted.toHex();
	    				message[0].message.iv = forge.util.encode64(iv);
	    				message[0].message.key = encryptedKey;
	    				// Remove the existing _id prior to storage.
	    				delete message[0]._id;
	    				console.log('new message: ', message[0]);
	    				// Store the message in the mail_storage collection.
	    				db.collection('mail_storage').insert(message[0], function(err, result) {
	    					if (err) throw err;
	    					if (result) console.log('Added to mail_storage');
	    					callback(null, d);
	    				})
	    				
	        			
	        		});
					
				});
			//} else {
			//	callback(null, d);
			//}
			
		});

	});

	req.on('error', function(e) {
		console.error(e);
	});

	req.write(messagePost);
	req.end();
	
}], // That was the last waterfall function.

function (err, status) {
	//console.log('status: ', status.toString());
    process.exit(0);
}
); //end async