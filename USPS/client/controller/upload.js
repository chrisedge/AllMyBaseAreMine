window.dash = window.dash || {};
var formidable = require('formidable'), // form processing
    http = require('http'), // web server
    // util = require('util'), // only needed for debugging
    Datauri = require('datauri'), // convert image to datauri
    fs = require('fs'); // deleting of files

dash.webServer = function(webPort) {
  http.createServer(function(req, res) {
  if (req.url == '/upload' && req.method.toLowerCase() == 'post') {
    // parse a file upload
    var form = new formidable.IncomingForm(),
        files = [],
        fields = [];

    form.uploadDir = 'uploads';
    form.keepExtensions = true;

    form
      .on('field', function(field, value) {
        //console.log(field, value);
        fields.push([field, value]);
      })
      .on('file', function(field, file) {
        //console.log(field, file);
        files.push([field, file]);
        fileName = file.path;
        //console.log('fileName ' + fileName);
      })
      .on('end', function(field, file) {
        //console.log('-> upload done');
        res.writeHead(200, {'content-type': 'application/json'});
        //console.log('received files:\n\n '+util.inspect(files));
        var duri = Datauri(fileName); // Convert the file to a datauri
        fs.unlink(fileName, function (err) { // delete the file
          if(err) throw err;
          //console.log('deleted ' + fileName);
        })
        res.end('{"link":"' + duri + '"}'); // return the datauri as the link
      });
    form.parse(req);
  } else {
    res.writeHead(404, {'content-type': 'text/plain'});
    res.end('404');
  }
  }).listen(webPort);
  //console.log('listening on: '+webPort)
};
