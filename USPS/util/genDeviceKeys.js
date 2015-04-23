var db = require('diskdb'),
	forge = require('node-forge'),
	fs = require('fs');

db = db.connect('collections', ['device']);

// Create device public and private key pairs.
var keys = forge.pki.rsa.generateKeyPair(2048);
    var pem = {
        privateKey: forge.pki.privateKeyToPem(keys.privateKey),
        publicKey: forge.pki.publicKeyToPem(keys.publicKey),
    }

var device = {
	subscriberID: "0005551212",
	publicKey: pem.publicKey
}

db.device.update( {subscriberID: "0005551212"}, {devicePublicKey: pem.publicKey, devicePrivateKey: pem.privateKey}, {multi: false, upsert:false} );