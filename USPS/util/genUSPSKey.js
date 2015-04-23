var db = require('diskdb'),
	forge = require('node-forge'),
	fs = require('fs');

db = db.connect('collections', ['device']);

// Create USPS public and pribvate key pairs.
var keys = forge.pki.rsa.generateKeyPair(2048);
    var pem = {
        privateKey: forge.pki.privateKeyToPem(keys.privateKey),
        publicKey: forge.pki.publicKeyToPem(keys.publicKey),
    }

var device = {
	subscriberID: "0005551212",
	publicKey: pem.publicKey
}

db.device.save(device);

fs.writeFile("USPS-PubKey.pem", pem.publicKey, function(err) {
	if(err) {
		return console.log(err);
	}

	console.log("Success");
});

fs.writeFile("USPS-PrivKey.pem", pem.privateKey, function(err) {
	if(err) {
		return console.log(err);
	}

	console.log("Success");
});
