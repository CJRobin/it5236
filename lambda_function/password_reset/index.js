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
			conn.end();
			callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		};
		console.log("Connected!");
		var sql = "SELECT email, userid FROM users WHERE username = ? OR email = ?";

		conn.query(sql, [event.username, event.email], function (err, result) {
		    if (err) {
				// This should be a "Internal Server Error" error
				conn.end();
				callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		  	} else {
		  		let passwordresetid = event.passwordresetid;
		  		let userid = result[0]['userid'];
		  		let email = result[0]['email'];
				var sql = "INSERT INTO passwordreset (passwordresetid, userid, email, expires) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))";
		
				conn.query(sql, [passwordresetid, userid, email], function (err, result) {
				    if (err) {
						// This should be a "Internal Server Error" error
						conn.end();
						callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
				  	} else {
				  		conn.end();
				    	callback(null,email);
				  	}
				});
		  	}
		});
	});
};
