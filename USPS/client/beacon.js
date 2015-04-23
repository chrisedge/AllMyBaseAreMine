// A secure (TLS) https client
// From https://github.com/ZiCog/node-tls-example/blob/master/https-client.js
var https = require('https');
var fs = require('fs');

// Find our IP address
// From http://stackoverflow.com/questions/3653065/get-local-ip-address-in-node-js
var os = require('os');
var ifaces = os.networkInterfaces();

var db = require('diskdb');
db = db.connect('collections', ['device']);
var device = db.device.findOne();

var forge = require('node-forge');

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

// Since the nature of our challenge-response is synchronous, we use async.
// From: http://www.hacksparrow.com/node-js-async-programming.html
var async = require('async');

var decryptChallenge = function(challenge) {
  // Decrypt the base64 encoded challenge using the device private key.
  var privateKey = forge.pki.privateKeyFromPem(device.devicePrivateKey);
  var response = privateKey.decrypt(forge.util.decode64(challenge));
  return response;
};

function doUpdate() {

  Object.keys(ifaces).forEach(function (ifname) {
    
    ifaces[ifname].forEach(function (iface) {
      if ('IPv4' !== iface.family || iface.internal !== false) {
        // skip over internal (i.e. 127.0.0.1) and non-ipv4 addresses
        return;
      }
      //console.log(ifname, iface.address);
      //var currentIP = iface.address;
      // For our purposes, we're always using going to use localhost.
      var currentIP = '127.0.0.1';

      if(device.lastKnownIP !== currentIP) {
        console.log('lastKnown: ', device.lastKnownIP, ' current: ', currentIP);
        // Need to update our IP with the USPS API server.
        // This requires us to go through the challenge-response authentication.
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
              // We can now use this accessToken to update our IP address in the TDD.
              var updatePost = JSON.stringify({
                'subscriberID': device.subscriberID,
                'accessToken': accessToken,
                'IPaddress': currentIP
              });

              httpOptions.path = '/tdd/update';
              var req = https.request(httpOptions, function(res) {
                res.on('data', function(d) {
                  callback(null, d);
                });

              });
              
              req.on('error', function(e) {
                console.error(e);
              });

              req.write(updatePost);
              req.end();
            }

          ],
          // The final callback.
          function(err, status) {
            if(JSON.parse(status.toString()).result == 1) { // Success.
              // Update the local device collection.
              db.device.update( {subscriberID: device.subscriberID}, {lastKnownIP: currentIP, lastUpdateTime: new Date()}, {multi: false, upsert:false} );
              console.log('IP updated');
            }
          }
        ); // async

      } else {
        console.log('no IP address change');
        db.device.update( {subscriberID: device.subscriberID}, {lastUpdateTime: new Date()}, {multi: false, upsert:false} );
      }; // endif
    }); //foreach
  }); //Object

}; // doUpdate()

// This page is loaded by all pages in the application. If 5 minutes have
// passed since the last IP address check, it will run the doUpdate() function.

// The updateTime is 5 minutes from the value of lastUpdateTime.
var updateTime = new Date( new Date(device.lastUpdateTime).getTime() + 5*60000);
console.log('last update: ' + device.lastUpdateTime + ' 5 minutes from then: ' +updateTime);
// Is the current time greater than the "5 minutes later" time?
var current = new Date();
if ( (current > updateTime) || (device.lastUpdateTime == '') ) {
  console.log('Checking IP update.');
  doUpdate();
} else {
  console.log('Skipping IP update');
}
