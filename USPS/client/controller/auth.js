window.dash = window.dash || {};
var db = require('diskdb'),
    bcrypt = require('bcrypt-nodejs'),
    forge = require('node-forge'),
    https = require('https'),
    fs = require('fs'),
    async = require('async');


db = db.connect('collections', ['users', 'device']);

var device = db.device.findOne();

var APIServerIP = '127.0.0.1';
// Establish common options for our API calls.
var httpOptions = {
  host: APIServerIP,
  port: 1169,
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  cert: fs.readFileSync('apiServer-cert.pem'),
  rejectUnauthorized: false
};

var decryptChallenge = function(challenge) {
  // Decrypt the base64 encoded challenge using the device private key.
  var privateKey = forge.pki.privateKeyFromPem(device.devicePrivateKey);
  var response = privateKey.decrypt(forge.util.decode64(challenge));
  return response;
};

dash.checkAliases = function() {
    // Look for any valid users. If there aren't
    // any, return false so the page redirects to setup.html.
    if( !db.users.findOne() ) {
        return false;
    } else {
        return true;
    }
}

 
dash.registerUser = function(firstName, middleName, lastName, userid, password) {
 
    /*
    *  response code : 0 - User with this id already exists
    *  response code : 1 - Sucessfully registered and User has already filled the settings
    *  response code : 2 - Sucessfully registered and User has not filled the settings
    */
 
    if (db.users.findOne({
        userid: userid
    })) return 0;

    // Create key pairs for this user.
    var keys = forge.pki.rsa.generateKeyPair(2048);
    
    // Use this to create PEM formatted versions.
    var pem = {
        privateKey: forge.pki.privateKeyToPem(keys.privateKey),
        publicKey: forge.pki.publicKeyToPem(keys.publicKey),
    }
    //console.log('public: ', pem.publicKey);
    //console.log('private: ', pem.privateKey);
    
    // Create a hash of the public key to be used as their postage account ID.
    var md = forge.md.sha256.create();
    md.update(pem.publicKey);
    
    //return populateUser(savedUser);
    // Need to populate the TDD with the new alias.
    async.waterfall(
          [
            function(callback) {
              // First identify ourself with our subscriberID
              var challengePost = JSON.stringify({'subscriberID': device.subscriberID});
              httpOptions.path = '/tdd/auth';
              var req = https.request(httpOptions, function(res) {
                res.on('data', function(d) {
                  callback(null, d);
                });

              });
              
              req.on('error', function(e) {
                console.error(e);
              });

              req.write(challengePost);
              req.end();
            },

            function(data, callback) {
              // We now have a temporary challengeToken, and an encrypted challenge string.
              var challengeToken = JSON.parse(data.toString()).token;
              var challenge = JSON.parse(data.toString()).challenge;
              // Decrypt the encrypted challenge text.
              var plainText = decryptChallenge(challenge);
              // Prepare a response.
              var responsePost = JSON.stringify({
                'subscriberID': device.subscriberID,
                'challengeToken': challengeToken,
                'response': plainText
              });
            
              httpOptions.path = '/tdd/auth';
              var req = https.request(httpOptions, function(res) {
                res.on('data', function(d) {
                  callback(null, d);
                });

              });
              
              req.on('error', function(e) {
                console.error(e);
              });

              req.write(responsePost);
              req.end();
            },

            function(data, callback) {
              // If the last call was successful, we should have received our accessToken.
              var accessToken = JSON.parse(data.toString()).token;
              // We can now use this accessToken to update our aliases in the TDD.
              // save the user to DB
                var postageID = md.digest().toHex();
                var savedUser = db.users.save({
                    firstName: firstName,
                    middleName: middleName,
                    lastName: lastName,
                    userid: userid,
                    password: bcrypt.hashSync(password),
                    privateKey: pem.privateKey,
                    publicKey: pem.publicKey,
                    postageID: postageID,
                    postageBalance: "0.00"
                });
              //console.log('savedUser: ', savedUser); // Don't bother, its async and you can't log to the console here.

                var updateAlias = JSON.stringify({
                    'subscriberID': device.subscriberID,
                    'accessToken': accessToken,
                    'userid': userid,
                    'firstName': firstName,
                    'middleName': middleName,
                    'lastName': lastName,
                    'publicKey': pem.publicKey,
                    'postageID': postageID
                });

              httpOptions.path = '/tdd/alias';
              var req = https.request(httpOptions, function(res) {
                res.on('data', function(d) {
                  callback(null, d);
                });

              });
              
              req.on('error', function(e) {
                console.error(e);
              });

              req.write(updateAlias);
              req.end();
            }

          ],
          // The final callback.
          function(err, status) {
            if(JSON.parse(status.toString()).result == 1) { // Success.
                // ..Right
            }
          }
        ); // async

    //return 1;
};
 
dash.authUser = function(userid, password) {
    
    /*
    *  response code : 0 - User does not exists
    *  response code : 1 - Sucessfully logged in and User has already filled the settings
    *  response code : 2 - Sucessfully logged in and User has not filled the settings
    *  response code : 2 - Authentication failed
    */
    
    // Encryption/decryption tests.
    /*var text = "This is some super secret text";
    // Get the USPS public key and convert to a forge key.
    var device = db.device.findOne();
    //console.log('device: ', device.publicKey);
    var USPSKey = forge.pki.publicKeyFromPem(device.publicKey);
    var ct = USPSKey.encrypt(text)).toString('base64'));*/

    // fetch the user from DB
    var user = db.users.findOne({
        userid: userid
    });
    
    // Encrypt/decrypt examples.
    /*
    var text = "This is some super secret text";
    var privKey = forge.pki.privateKeyFromPem(user.privateKey);
    var pubKey = forge.pki.publicKeyFromPem(user.publicKey);
    var ct = forge.util.encode64(pubKey.encrypt(text));
    console.log('encrypted base64: ', JSON.stringify(ct) );
    var pt = privKey.decrypt(forge.util.decode64(ct));
    console.log('plain: ', pt);
    */

    if (user) {
        if (bcrypt.compareSync(password, user.password)) {
            return populateUser(user);
        } else {
            return 3;
        }
 
    } else {
        return 0;
    }

    
    
};
 
dash.signOut = function () {
 
    /*
    *  Clean localstorage
    */
 
	localStorage.removeItem('user');
	localStorage.removeItem('settings');
	return 1;
};
 
var populateUser = function(user) {
    
    /*
    *  Create a "Session"
    */
 
    delete user.password; // remove password before creating a "session"
 
    // if local storage has a user object, the user is logged in 'Duh!'
    localStorage.setItem('user', JSON.stringify(user));
 
    // check if the user has completed the settings
    /*var setg = db.settings.findOne({
        uid: user._id
    });
    if (setg) {
        localStorage.setItem('settings', JSON.stringify(setg.settings));
        return 1;
    } else {
        return 2;
    }*/

    return 1;
};