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
		var sql = "INSERT INTO auditlog (context, message, logdate, ipaddress, userid) VALUES (?, ?, NOW(), ?, ?)";

		conn.query(sql, [event.context, event.message, event.ipaddress, event.userid], function (err, result) {
		    if (err) {
				// This should be a "Internal Server Error" error
				callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		  	} else {
		  		conn.end();
		    	callback(null,"Added Audit log");
		  	}
		});
	});
};
