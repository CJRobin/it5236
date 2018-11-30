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
		var sql = "SELECT userid FROM emailvalidation WHERE emailvalidationid = ?";

		conn.query(sql, [event.emailvalidationid], function (err, result) {
		    if (err) {
				// This should be a "Internal Server Error" error
				callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		  	} else {
		  		if(result.length != 0) {
		  			var sql = "UPDATE users SET emailvalidated = 1 WHERE userid = ?";
					conn.query(sql, [result[0]['userid']], function (err, result2) {
						if(err){
							
						} else {
							var sql = "DELETE FROM emailvalidation WHERE emailvalidationid = ?";
							conn.query(sql, [event.emailvalidationid], function (err, result3) {
								if(err){
									
								} else {
									conn.end();
									callback(null, result);
								}
							});
						}
					});
				
		  		}
		  	}
		});
	});
};
