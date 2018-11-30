var mysql = require('./node_modules/mysql');
var config = require('./config.json');

function formatErrorResponse(code, errs) {
	return JSON.stringify({
		error  : code,
		errors : errs
	});
}

exports.handler = (event, context, callback) => {
	var conn = mysql.createConnection({
		host 	: config.dbhost,
		user 	: config.dbuser,
		password : config.dbpassword,
		database : config.dbname
	});
	context.callbackWaitsForEmptyEventLoop = false;
	conn.connect(function(err) {

		if (err)  {
			// This should be a "Internal Server Error" error
			callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		};
		console.log("Connected!");
		var sql = "INSERT INTO comments (commentid, commenttext, commentposted, commentuserid, commentthingid, commentattachmentid) VALUES (?, ?, now(), ?, ?, ?)";

		conn.query(sql, [event.commentid, event.commenttext, event.commentuserid, event.commentthingid, event.commentattachmentid], function (err, result) {
		    if (err) {
				// This should be a "Internal Server Error" error
				callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		  	} else {
		    	callback(null,"Success");
		  	}
		});
	});
};
