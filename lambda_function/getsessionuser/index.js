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
		var sql = "SELECT usersessionid, usersessions.userid, email, username, usersessions.registrationcode, isadmin FROM usersessions LEFT JOIN users on usersessions.userid = users.userid WHERE usersessionid = ? AND expires > now()";

		conn.query(sql, [event.sessionid], function (err, result) {
		    if (err) {
				// This should be a "Internal Server Error" error
				conn.end();
				callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		  	} else {
		  		conn.end();
		    	callback(null, result);
		  	}
		});
	});
};
