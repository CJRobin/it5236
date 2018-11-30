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
		var sql = `SELECT things.thingid, things.thingname, convert_tz(things.thingcreated,@@session.time_zone,'America/New_York') 
				as thingcreated, things.thinguserid, things.thingattachmentid, things.thingregistrationcode, username, filename
                FROM things LEFT JOIN users ON things.thinguserid = users.userid 
                LEFT JOIN attachments ON things.thingattachmentid = attachments.attachmentid 
                WHERE thingid = ?`;
		conn.query(sql, [event.thingid], function (err, result) {
		    if (err) {
				conn.end();
				callback(formatErrorResponse('INTERNAL_SERVER_ERROR', [err]));
		  	} else {
		  		conn.end();
		    	callback(null,result);
		  	}
		});
	});
};
