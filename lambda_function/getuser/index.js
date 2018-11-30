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
		var sql = "SELECT userid, username, email, isadmin FROM users WHERE userid = ?";

		conn.query(sql, [event.userid], function (err, result) {
		    if (err) {
				// This should be a "Internal Server Error" error
				conn.end();
				callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		  	} else {
		  		conn.end();
		    	callback(null,result);
		  	}
		});
	});
};
