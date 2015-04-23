window.dash = window.dash || {};

var db = require('diskdb'),
	forge = require('node-forge');

dash.encryptMessage = function(text, messageFields, user, key) {
	// Encrypt the message using AES. Generate a random 32 bit AES
    // key with a random IV. This random AES key is then used to
    // encrypt the message (since RSA keys can't encrypt very much
    // data). The AES key (that is required for decryption) is then
    // encrypted with the USPS public key (since it's only 256 bytes).
    // The IV and the encrypted AES key are stored along with the
    // message cipher text.
    db = db.connect('collections', ['device', 'users']);
    var device = db.device.findOne();
    if(key == 'usps') { // encrypt with USPS pub key.
      var pubKey = forge.pki.publicKeyFromPem(device.publicKey);
    } else { // Headed to drafts - encrypt with our key.
      var users = db.users.findOne({userid: user.userid});
      var pubKey = forge.pki.publicKeyFromPem(users.publicKey);
    }
    var key = forge.random.getBytesSync(32);
    var iv = forge.random.getBytesSync(32);
    var cipher = forge.cipher.createCipher('AES-CBC', key);
    cipher.start({iv: iv});
    cipher.update(forge.util.createBuffer(text));
    cipher.finish();
    var encrypted = cipher.output;
    // outputs encrypted hex
    //console.log('encrypted text: ', encrypted.toHex());

    // Message text is encrypted. Now encrypt the key.
    var encryptedKey = forge.util.encode64(pubKey.encrypt(key));
    console.log('encryptedKey: ', encryptedKey);
    // Now create the message data structure.
    var mail = {
      postmarkTimestamp: (Date.now() / 1000 | 0), // UTC timestamp
      deliveryTimestamp: "", // Filled in once delivered
      services: {
        readReceipt: "",
        certified: "",
        returnPostagePaid: ""
      },
      message: {
        messageData: encrypted.toHex(),
        iv: forge.util.encode64(iv),
        key: encryptedKey
      },
      fromPhysicalAddress: device.physicalAddress,
      fromFullName: user.firstName + ' ' + user.lastName,
      fromAlias: user.userid,
      fromSubscriberID: device.subscriberID,
      toPhysicalAddress: messageFields.toPhysicalAddress,
      toFullName: messageFields.toName,
      toAlias: messageFields.toAlias,
      toSubscriberID: messageFields.toSubscriberID,
      toAliasID: ""
    }

    return mail;
}

dash.calcPostage = function(text) {
	// Postage rates: $0.50 per 500k (524,288 bytes) of message.
    var stamp = .50;
    // Calculate the cost of this message.
    if(text.length <= 524288) {
      var postage = parseFloat(stamp * 1).toFixed(2);
    } else {
      var division = Math.floor(text.length / 524288);
      var rem = text.length % 524288;
      if(rem >= 1) {
        var remainder = 1;
      } else {
        var remainder = 0;
      }
      var postage = parseFloat(stamp * (division + remainder)).toFixed(2);
    }
    console.log('postage: ', postage);
    return postage;
}
