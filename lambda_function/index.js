
var mysql = require('./node_modules/mysql');
var config = require('./config.json');

exports.handler = (event, context, callback) => {
  var conn = mysql.createConnection({
	host 	: config.dbhost,
	user 	: config.dbuser,
	password : config.dbpassword,
	database : config.dbname
  });

  //prevent timeout from waiting event loop
  context.callbackWaitsForEmptyEventLoop = false;

  conn.connect(function(err) {
	if (err) throw err;
	console.log("Connected!");
	var sql = "create table IF NOT EXISTS Employee3 (EmpID  int NOT NULL, Name varchar(255) NOT NULL, PRIMARY KEY (EmpID))";
	conn.query(sql, function (err, result) {
  	if (err) {
    	throw err;
  	} else {
    	console.log("Table created or already exists");
    	var sql = "insert into Employee3 (EmpID, Name) values(1, 'Joe')";
    	conn.query(sql, function (err, result) {
      	if (err) {
        	throw err;
      	} else {
        	console.log("First record created");
        	var sql = "insert into Employee3 (EmpID, Name) values(2, 'Bob')";
        	conn.query(sql, function (err, result) {
          	if (err) {
            	throw err;
          	} else {
            	console.log("Second record created");
            	var sql = "insert into Employee3 (EmpID, Name) values(3, 'Sue')";
            	conn.query(sql, function (err, result) {
              	if (err) {
                	throw err;
              	} else {
                	console.log("Third record created");
                	var sql = "select * from Employee3";
                	conn.query(sql, function (err, result) {
                  	if (err) {
                    	throw err;
                  	} else {
                    	console.log("Third record created");
                    	callback(null,result);
                  	}
                	});
              	}
            	});
          	}
        	});
      	}
    	});
  	}
	});
  });
};
