<?php
/* if (file_exists(getcwd() . "/include/credentials.php")) {
    require('credentials.php');
} else {
    echo "Application has not been configured. Copy and edit the credentials-sample.php file to credentials.php.";
    exit();
} */

class Application {

    public $debugMessages = [];

    public function setup() {

        // Check to see if the client has a cookie called "debug" with a value of "true"
        // If it does, turn on error reporting
        if ($_COOKIE['debug'] == "true") {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }
    }

    // Writes a message to the debug message array for printing in the footer.
    public function debug($message) {
        $this->debugMessages[] = $message;
    }

    // Creates a database connection
    protected function getConnection() {

        // Import the database credentials
        $credentials = new Credentials();

        // Create the connection
        try {
            $dbh = new PDO("mysql:host=$credentials->servername;dbname=$credentials->serverdb", $credentials->serverusername, $credentials->serverpassword);
        } catch (PDOException $e) {
            print "Error connecting to the database.";
            die();
        }

        // Return the newly created connection
        return $dbh;
    }

    public function auditlog($context, $message, $priority = 0, $userid = NULL){

        // Declare an errors array
        $errors = [];

        // If a user is logged in, get their userid
        if ($userid == NULL) {

            $user = $this->getSessionUser($errors, TRUE);
            if ($user != NULL) {
                $userid = $user["userid"];
            }

        }

        $ipaddress = $_SERVER["REMOTE_ADDR"];

        if (is_array($message)){
            $message = implode( ",", $message);
        }

        $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/auditlog";
        $data = array(
          'context'=>$context,
          'message'=>$message,
          'ipaddress'=>$ipaddress,
          'userid'=>$userid,
        );
        $data_json = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === FALSE) {
          $errors[] = "An unexpected failure occurred contacting the web service.";
        } else {
          if($httpCode == 400) {

            // JSON was double-encoded, so it needs to be double decoded
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Bad input";
            }
          } else if($httpCode == 500) {
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Server error";
            }
          } else if($httpCode == 200) {
          }
        }

        curl_close($ch);

        if (sizeof($errors) == 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }

    protected function validateUsername($username, &$errors) {
        if (empty($username)) {
            $errors[] = "Missing username";
        } else if (strlen(trim($username)) < 3) {
            $errors[] = "Username must be at least 3 characters";
        } else if (strpos($username, "@")) {
            $errors[] = "Username may not contain an '@' sign";
        }
    }

    protected function validatePassword($password, &$errors) {
        if (empty($password)) {
            $errors[] = "Missing password";
        } else if (strlen(trim($password)) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
    }

    protected function validateEmail($email, &$errors) {
        if (empty($email)) {
            $errors[] = "Missing email";
        } else if (substr(strtolower(trim($email)), -20) != "@georgiasouthern.edu"
            && substr(strtolower(trim($email)), -13) != "@thackston.me") {
                $errors[] = "Not a Georgia Southern email address";
            }
    }

    public function register($username, $password, $email, $registrationcode, &$errors) {

          $this->auditlog("register", "attempt: $username, $email, $registrationcode");

          // Validate the user input
          $this->validateUsername($username, $errors);
          $this->validatePassword($password, $errors);
          $this->validateEmail($email, $errors);
          if (empty($registrationcode)) {
              $errors[] = "Missing registration code";
          }

          // Only try to insert the data into the database if there are no validation errors
          if (sizeof($errors) == 0) {

              $passwordhash = password_hash($password, PASSWORD_DEFAULT);

              // Create a new user ID
              $userid = bin2hex(random_bytes(16));
  			$url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/registeruser";
  			$data = array(
  				'userid'=>$userid,
  				'username'=>$username,
  				'passwordHash'=>$passwordhash,
  				'email'=>$email,
  				'registrationcode'=>$registrationcode
  			);
  			$data_json = json_encode($data);
  			$ch = curl_init();
  			curl_setopt($ch, CURLOPT_URL, $url);
  			curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
  			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
  			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
  			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  			$response  = curl_exec($ch);
  			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  			if ($response === FALSE) {
  				$errors[] = "An unexpected failure occurred contacting the web service.";
  			} else {
  				if($httpCode == 400) {

  					// JSON was double-encoded, so it needs to be double decoded
  					$errorsList = json_decode(json_decode($response))->errors;
  					foreach ($errorsList as $err) {
  						$errors[] = $err;
  					}
  					if (sizeof($errors) == 0) {
  						$errors[] = "Bad input";
  					}
  				} else if($httpCode == 500) {
  					$errorsList = json_decode(json_decode($response))->errors;
  					foreach ($errorsList as $err) {
  						$errors[] = $err;
  					}
  					if (sizeof($errors) == 0) {
  						$errors[] = "Server error";
  					}
  				} else if($httpCode == 200) {
  					$this->sendValidationEmail($userid, $email, $errors);
  				}
  			}

  			curl_close($ch);
          } else {
              $this->auditlog("register validation error", $errors);
          }

          // Return TRUE if there are no errors, otherwise return FALSE
          if (sizeof($errors) == 0){
              return TRUE;
          } else {
              return FALSE;
          }
      }


      protected function sendVerificationEmail($userid, $email, &$errors) {

          $this->auditlog("sendOTPEmail", "Sending code to $email");

          $validationid = rand(100000, 999999);

          $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/sendverificationemail";
          $data = array(
            'emailvalidationid'=>$validationid,
            'userid'=>$userid,
            'email'=>$email
          );
          $data_json = json_encode($data);
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $response  = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          if ($response === FALSE) {
            $errors[] = "An unexpected error occurred sending the otp email";
            $this->debug($stmt->errorInfo());
            $this->auditlog("login error", $stmt->errorInfo());
          } else {
            if($httpCode == 400) {

              // JSON was double-encoded, so it needs to be double decoded
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Bad input";
              }
            } else if($httpCode == 500) {
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Server error";
              }
            } else if($httpCode == 200) {
              $this->auditlog("sendOTPEmail", "Sending message to $email");

              // Send reset email
              $pageLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
              $pageLink = str_replace("login.php", "twofactor.php", $pageLink);
              $to      = $email;
              $subject = 'Login Request';
              $message = "A request has been made to login to your account at http://54.164.188.229/it5236/website. ".
                  "If you did not make this request, please ignore this message. No other action is necessary. ".
                  "To confirm your login, please click the following link: $pageLink?id=$validationid or copy and past this code '$validationid' into the OTP box";
              $headers = 'From: webmaster@russellthackston.me' . "\r\n" .
                  'Reply-To: webmaster@russellthackston.me' . "\r\n";

              mail($to, $subject, $message, $headers);

              $this->auditlog("sendOTPEmail", "Message sent to $email");
            }
          }

          curl_close($ch);

      }
      // Send an email to validate the address
      protected function sendValidationEmail($userid, $email, &$errors) {


          $this->auditlog("sendValidationEmail", "Sending message to $email");

          $validationid = bin2hex(random_bytes(16));

          $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/sendvalidationemail";
          $data = array(
            'emailvalidationid'=>$validationid,
            'userid'=>$userid,
            'email'=>$email
          );
          $data_json = json_encode($data);
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $response  = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          if ($response === FALSE) {
            $errors[] = "An unexpected error occurred sending the validation email";
            $this->debug($stmt->errorInfo());
            $this->auditlog("register error", $stmt->errorInfo());
          } else {
            if($httpCode == 400) {

              // JSON was double-encoded, so it needs to be double decoded
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Bad input";
              }
            } else if($httpCode == 500) {
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Server error";
              }
            } else if($httpCode == 200) {
              $this->auditlog("sendValidationEmail", "Sending message to $email");

              // Send reset email
              $pageLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
              $pageLink = str_replace("register.php", "login.php", $pageLink);
              $to      = $email;
              $subject = 'Confirm your email address';
              $message = "A request has been made to create an account at https://russellthackston.me for this email address. ".
                  "If you did not make this request, please ignore this message. No other action is necessary. ".
                  "To confirm this address, please click the following link: $pageLink?id=$validationid";
              $headers = 'From: webmaster@russellthackston.me' . "\r\n" .
                  'Reply-To: webmaster@russellthackston.me' . "\r\n";

              mail($to, $subject, $message, $headers);

              $this->auditlog("sendVerificationEmail", "Message sent to $email");
            }
          }

          curl_close($ch);

      }

      // Send an email to validate the address
      public function processEmailValidation($validationid, &$errors) {

          $success = FALSE;

          $this->auditlog("processEmailValidation", "Received: $validationid");

          $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/processemailvalidation";
          $data = array(
            'emailvalidationid'=>$validationid,
          );
          $data_json = json_encode($data);
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $response  = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          if ($response === FALSE) {
            $errors[] = "An unexpected error occurred processing your email validation request";
            $this->debug($stmt->errorInfo());
            $this->auditlog("processEmailValidation error", $stmt->errorInfo());
          } else {
            if($httpCode == 400) {

              // JSON was double-encoded, so it needs to be double decoded
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Bad input";
              }
            } else if($httpCode == 500) {
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Server error";
              }
            } else if($httpCode == 200) {
              $this->auditlog("processEmailValidation", "Email address validated: $validationid");
              $success = true;
            }
          }

          curl_close($ch);

          return $success;

      }

    // Send an email to validate the address
    public function processEmailVerification($validationid, &$errors) {

      $success = FALSE;
      $this->auditlog("processEmailValidation", "Received: $validationid");

      $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/processemailverification";
      $data = array(
        'emailvalidationid'=>$validationid
      );
      $data_json = json_encode($data);
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response  = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($response === FALSE) {
        $errors[] = "An unexpected error occurred processing your email validation request";
        $this->debug($stmt->errorInfo());
        $this->auditlog("processEmailValidation error", $stmt->errorInfo());
      } else {
        if($httpCode == 400) {

          // JSON was double-encoded, so it needs to be double decoded
          $errorsList = json_decode(json_decode($response))->errors;
          foreach ($errorsList as $err) {
            $errors[] = $err;
          }
          if (sizeof($errors) == 0) {
            $errors[] = "Bad input";
          }
        } else if($httpCode == 500) {
          $errorsList = json_decode(json_decode($response))->errors;
          foreach ($errorsList as $err) {
            $errors[] = $err;
          }
          if (sizeof($errors) == 0) {
            $errors[] = "Server error";
          }
        } else if($httpCode == 200) {
          $this->auditlog("processEmailValidation", "Email address validated: $validationid");
          $this->newSession(json_decode($response, true)[0]['userid'], $errors);
          $success = true;
        }
      }

      curl_close($ch);

      return $success;
    }

    // Creates a new session in the database for the specified user
    public function newSession($userid, &$errors, $registrationcode = NULL) {

        // Check for a valid userid
        if (empty($userid)) {
            $errors[] = "Missing userid";
            $this->auditlog("session", "missing userid");
        }

        // Only try to query the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {

            if ($registrationcode == NULL) {
                $regs = $this->getUserRegistrations($userid, $errors);
                $reg = $regs[0];
                $this->auditlog("session", "logging in user with first reg code $reg");
                $registrationcode = $regs[0];
            }

            // Create a new session ID
            $sessionid = bin2hex(random_bytes(25));

            $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/newsession";
            $data = array(
              'sessionid'=>$sessionid,
              'userid'=>$userid,
              'registrationcode'=>$registrationcode
            );
            $data_json = json_encode($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response  = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response === FALSE) {
              $errors[] = "An unexpected error occurred";
              $this->debug($stmt->errorInfo());
              $this->auditlog("new session error", $stmt->errorInfo());
              return NULL;
            } else {
              if($httpCode == 400) {

                // JSON was double-encoded, so it needs to be double decoded
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Bad input";
                }
              } else if($httpCode == 500) {
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Server error";
                }
              } else if($httpCode == 200) {
                // Store the session ID as a cookie in the browser
                setcookie('sessionid', $sessionid, time()+60*60*24*30);
                $this->auditlog("session", "new session id: $sessionid for user = $userid");

                // Return the session ID
                return $sessionid;
              }
            }

            curl_close($ch);

        }

    }

    public function getUserRegistrations($userid, &$errors) {

        // Assume an empty list of regs
        $regs = array();

        // Connect to the database
        $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/getuserregistrations";
        $data = array(
          'userid'=>$userid
        );
        $data_json = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === FALSE) {
          $errors[] = "An unexpected error occurred getting the regs list.";
          $this->debug($stmt->errorInfo());
          $this->auditlog("getUserRegistrations error", $stmt->errorInfo());
          return NULL;
        } else {
          if($httpCode == 400) {

            // JSON was double-encoded, so it needs to be double decoded
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Bad input";
            }
          } else if($httpCode == 500) {
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Server error";
            }
          } else if($httpCode == 200) {
            $rows = json_decode($response, true);
            array_push($regs,json_decode($response, true)[0]['registrationcode']);
            $this->auditlog("getUserRegistrations", "success");
            return $regs;
          }
        }

        curl_close($ch);

        return $regs;
    }

    // Updates a single user in the database and will return the $errors array listing any errors encountered
    public function updateUserPassword($userid, $password, &$errors) {

        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing userid";
        }
        $this->validatePassword($password, $errors);

        if(sizeof($errors) == 0) {

            $passwordhash = password_hash($password, PASSWORD_DEFAULT);

            $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/updateuserpassword";
            $data = array(
              'passwordhash'=>$passwordhash,
              'userid'=>$userid
            );
            $data_json = json_encode($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response  = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response === FALSE) {
              $errors[] = "An unexpected error occurred supdating the password.";
              $this->debug($stmt->errorInfo());
              $this->auditlog("updateUserPassword error", $stmt->errorInfo());
              return NULL;
            } else {
              if($httpCode == 400) {

                // JSON was double-encoded, so it needs to be double decoded
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Bad input";
                }
              } else if($httpCode == 500) {
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Server error";
                }
              } else if($httpCode == 200) {
                $this->auditlog("updateUserPassword", "success");
              }
            }

            curl_close($ch);

        } else {

            $this->auditlog("updateUserPassword validation error", $errors);

        }
        if (sizeof($errors) == 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }

    // Removes the specified password reset entry in the database, as well as any expired ones
    // Does not retrun errors, as the user should not be informed of these problems
    protected function clearPasswordResetRecords($passwordresetid) {

      $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/clearpasswordresetrecords";
      $data = array(
        'passwordresetid'=>$passwordresetid
      );
      $data_json = json_encode($data);
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response  = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if ($response === FALSE) {
        $errors[] = "An unexpected error occurred the password.";
        $this->debug($stmt->errorInfo());
        return NULL;
      } else {
        if($httpCode == 400) {

          // JSON was double-encoded, so it needs to be double decoded
          $errorsList = json_decode(json_decode($response))->errors;
          foreach ($errorsList as $err) {
            $errors[] = $err;
          }
          if (sizeof($errors) == 0) {
            $errors[] = "Bad input";
          }
        } else if($httpCode == 500) {
          $errorsList = json_decode(json_decode($response))->errors;
          foreach ($errorsList as $err) {
            $errors[] = $err;
          }
          if (sizeof($errors) == 0) {
            $errors[] = "Server error";
          }
        } else if($httpCode == 200) {
          $this->auditlog("ClearPasswords", "success");
        }
      }

      curl_close($ch);

    }

    // Retrieves an existing session from the database for the specified user
    public function getSessionUser(&$errors, $suppressLog=FALSE) {

        // Get the session id cookie from the browser
        $sessionid = NULL;
        $user = NULL;

        // Check for a valid session ID
        if (isset($_COOKIE['sessionid'])) {

            $sessionid = $_COOKIE['sessionid'];

            $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/getsessionuser?sessionid=" . $sessionid;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response  = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response === FALSE) {
              $errors[] = "An unexpected failure occurred contacting the web service.";
            } else {
              if($httpCode == 400) {

                // JSON was double-encoded, so it needs to be double decoded
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Bad input";
                }
              } else if($httpCode == 500) {
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Server error";
                }
              } else if($httpCode == 200) {
                $user = json_decode($response, true)[0];
                curl_close($ch);
                return $user;
              }
            }

            curl_close($ch);

            if (sizeof($errors) == 0){
                return TRUE;
            } else {
                return FALSE;
            }

        }

        return $user;
    }

    // Retrieves an existing session from the database for the specified user
    public function isAdmin(&$errors, $userid) {

        // Check for a valid user ID
        if (empty($userid)) {
            $errors[] = "Missing userid";
            return FALSE;
        }

        $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/isadmin";
        $data = array(
          'userid'=>$userid
        );
        $data_json = json_encode($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === FALSE) {
          $errors[] = "An unexpected error occurred";
          $this->debug($stmt->errorInfo());
          $this->auditlog("isadmin error", $stmt->errorInfo());

          return FALSE;
        } else {
          if($httpCode == 400) {

            // JSON was double-encoded, so it needs to be double decoded
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Bad input";
            }
          } else if($httpCode == 500) {
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Server error";
            }
          } else if($httpCode == 200) {
            $isadmin = json_decode($response, true)[0]['isadmin'];

            // Return the isAdmin flag
            return $isadmin == 1;
          }
        }

        curl_close($ch);

    }

    // Logs in an existing user and will return the $errors array listing any errors encountered
    public function login($username, $password, &$errors) {

        $this->debug("Login attempted");
        $this->auditlog("login", "attempt: $username, password length = ".strlen($password));

        // Validate the user input
        if (empty($username)) {
            $errors[] = "Missing username";
        }
        if (empty($password)) {
            $errors[] = "Missing password";
        }

        // Only try to query the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {

          $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/login";
          $data = array(
            'username'=>$username
          );
          $data_json = json_encode($data);
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $response  = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          if ($response === FALSE) {
            $errors[] = "An unexpected error occurred";
            $this->debug($stmt->errorInfo());
            $this->auditlog("login error", $stmt->errorInfo());

            return FALSE;
          } else {
            if($httpCode == 400) {

              // JSON was double-encoded, so it needs to be double decoded
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Bad input";
              }
            } else if($httpCode == 500) {
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Server error";
              }
            } else if($httpCode == 200) {
              $user = json_decode($response, true);

              if (!isset($user[0])) {
                $errors[] = "Bad username/password combination!";
                $this->auditlog("login", "bad username: $username");
              } else {
                $row = json_decode($response, true)[0];
                // Check the password
                if (!password_verify($password, $row['passwordhash'])) {

                    $errors[] = "Bad username/password combination";
                    $this->auditlog("login", "bad password: password length = ".strlen($password));

                } else if ($row['emailvalidated'] == 0) {

                    $errors[] = "Login error. Email not validated. Please check your inbox and/or spam folder.";

                } else {

                    // Create a new session for this user ID in the database
                    $userid = $row['userid'];
                    $email = $row['email'];
                    $this->sendVerificationEmail($userid, $email, $errors);
                    $this->auditlog("login", "success: $username, $userid");

                }
              }
            }
          }

          curl_close($ch);


        } else {
            $this->auditlog("login validation error", $errors);
        }


        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }

    // Logs out the current user based on session ID
    public function logout() {

        $sessionid = $_COOKIE['sessionid'];

        // Only try to query the data into the database if there are no validation errors
        if (!empty($sessionid)) {
            $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/logout";
      			$data = array(
      				'sessionid'=>$sessionid
      			);
      			$data_json = json_encode($data);
      			$ch = curl_init();
      			curl_setopt($ch, CURLOPT_URL, $url);
      			curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
      			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
      			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
      			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      			$response  = curl_exec($ch);
      			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      			if ($response === FALSE) {
      				$errors[] = "An unexpected failure occurred contacting the web service.";
      			} else {
      				if($httpCode == 400) {

      					// JSON was double-encoded, so it needs to be double decoded
      					$errorsList = json_decode(json_decode($response))->errors;
      					foreach ($errorsList as $err) {
      						$errors[] = $err;
      					}
      					if (sizeof($errors) == 0) {
      						$errors[] = "Bad input";
      					}
      				} else if($httpCode == 500) {
      					$errorsList = json_decode(json_decode($response))->errors;
      					foreach ($errorsList as $err) {
      						$errors[] = $err;
      					}
      					if (sizeof($errors) == 0) {
      						$errors[] = "Server error";
      					}
      				} else if($httpCode == 200) {
                setcookie('sessionid', '', time()-3600);
                $this->auditlog("logout", "successful: $sessionid");
      				}
      			}

      			curl_close($ch);
          } else {
              $this->auditlog("logout error", $errors);
          }

          if (sizeof($errors) == 0){
              return TRUE;
          } else {
              return FALSE;
          }

    }

    // Checks for logged in user and redirects to login if not found with "page=protected" indicator in URL.
    public function protectPage(&$errors, $isAdmin = FALSE) {

        // Get the user ID from the session record
        $user = $this->getSessionUser($errors);
        if ($user == NULL) {
            // Redirect the user to the login page
            $this->auditlog("protect page", "no user");
            header("Location: login.php?page=protected");
            exit();
        }
        $userid = $user["userid"];

        if(empty($userid)) {

            // Redirect the user to the login page
            $this->auditlog("protect page error", $user);
            header("Location: login.php?page=protected");
            exit();

        } else if ($isAdmin)  {

            // Get the isAdmin flag from the database
            $isAdminDB = $this->isAdmin($errors, $userid);

            if (!$isAdminDB) {

                // Redirect the user to the home page
                $this->auditlog("protect page", "not admin");
                header("Location: index.php?page=protectedAdmin");
                exit();

            }

        }

    }

    // Get a list of things from the database and will return the $errors array listing any errors encountered
    public function getThings(&$errors) {

        // Assume an empty list of things
        $things = array();

        // Get the user id from the session
        $user = $this->getSessionUser($errors);
        $registrationcode = $user["registrationcode"];

        $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/getthings?registrationcode=" . $registrationcode;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === FALSE) {
          $errors[] = "An unexpected error occurred.";
          $this->debug($stmt->errorInfo());
          $this->auditlog("getthings error", $stmt->errorInfo());
        } else {
          if($httpCode == 400) {

            // JSON was double-encoded, so it needs to be double decoded
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Bad input";
            }
          } else if($httpCode == 500) {
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Server error";
            }
          } else if($httpCode == 200) {
            $things = json_decode($response, true);
          }
        }

        curl_close($ch);

        // Return the list of things
        return $things;

    }

    // Get a single thing from the database and will return the $errors array listing any errors encountered
    public function getThing($thingid, &$errors) {

        // Assume no thing exists for this thing id
        $thing = NULL;

        // Check for a valid thing ID
        if (empty($thingid)){
            $errors[] = "Missing thing ID";
        }

        if (sizeof($errors) == 0){

            // Connect to the database
            $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/getthing?thingid=" . $thingid;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response  = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response === FALSE) {
              $errors[] = "An unexpected error occurred.";
              $this->debug($stmt->errorInfo());
              $this->auditlog("getthing error", $stmt->errorInfo());
            } else {
              if($httpCode == 400) {

                // JSON was double-encoded, so it needs to be double decoded
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Bad input";
                }
              } else if($httpCode == 500) {
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Server error";
                }
              } else if($httpCode == 200) {
                $thing = json_decode($response, true)[0];
              }
            }

            curl_close($ch);


        } else {
            $this->auditlog("getThing validation error", $errors);
        }

        // Return the thing
        return $thing;

    }

    // Get a list of comments from the database
    public function getComments($thingid, &$errors) {

        // Assume an empty list of comments
        $comments = array();



        // Check for a valid thing ID
        if (empty($thingid)) {

            // Add an appropriate error message to the list
            $errors[] = "Missing thing ID";
            $this->auditlog("getComments validation error", $errors);

        } else {

          $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/getcomments?thingid=" . $thingid;
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI'));
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $response  = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          if ($response === FALSE) {
            $errors[] = "An unexpected error occurred loading the comments.";
            $this->debug($stmt->errorInfo());
            $this->auditlog("getcomments error", $stmt->errorInfo());
          } else {
            if($httpCode == 400) {

              // JSON was double-encoded, so it needs to be double decoded
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Bad input";
              }
            } else if($httpCode == 500) {
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Server error";
              }
            } else if($httpCode == 200) {
              $comments = json_decode($response, true);
            }
          }

          curl_close($ch);
        }

        // Return the list of comments
        return $comments;

    }

    // Handles the saving of uploaded attachments and the creation of a corresponding record in the attachments table.
    public function saveAttachment($attachment, &$errors) {

        $attachmentid = NULL;

        // Check for an attachment
        if (isset($attachment) && isset($attachment['name']) && !empty($attachment['name'])) {

            // Get the list of valid attachment types and file extensions
            $attachmenttypes = $this->getAttachmentTypes($errors);

            // Construct an array containing only the 'extension' keys
            $extensions = array_column($attachmenttypes, 'extension');

            // Get the uploaded filename
            $filename = $attachment['name'];

            $dot = strrpos($filename, ".");

            // Make sure the file has an extension and the last character of the name is not a "."
            if ($dot !== FALSE && $dot != strlen($filename)) {

                // Check to see if the uploaded file has an allowed file extension
                $extension = strtolower(substr($filename, $dot + 1));
                if (!in_array($extension, $extensions)) {

                    // Not a valid file extension
                    $errors[] = "File does not have a valid file extension";
                    $this->auditlog("saveAttachment", "invalid file extension: $filename");

                }

            } else {

                // No file extension -- Disallow
                $errors[] = "File does not have a valid file extension";
                $this->auditlog("saveAttachment", "no file extension: $filename");

            }

            // Only attempt to add the attachment to the database if the file extension was good
            if (sizeof($errors) == 0) {

                // Create a new ID
                $attachmentid = bin2hex(random_bytes(16));

                $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/saveattachment";
          			$data = array(
          				'attachmentid'=>$attachmentid,
                  'filename'=>$filename
          			);
          			$data_json = json_encode($data);
          			$ch = curl_init();
          			curl_setopt($ch, CURLOPT_URL, $url);
          			curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
          			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          			$response  = curl_exec($ch);
          			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          			if ($response === FALSE) {
                  $errors[] = "An unexpected error occurred storing the attachment.";
                  $this->debug($stmt->errorInfo());
                  $this->auditlog("saveAttachment error", $stmt->errorInfo());
          			} else {
          				if($httpCode == 400) {

          					// JSON was double-encoded, so it needs to be double decoded
          					$errorsList = json_decode(json_decode($response))->errors;
          					foreach ($errorsList as $err) {
          						$errors[] = $err;
          					}
          					if (sizeof($errors) == 0) {
          						$errors[] = "Bad input";
          					}
                    curl_close($ch);
          				} else if($httpCode == 500) {
          					$errorsList = json_decode(json_decode($response))->errors;
          					foreach ($errorsList as $err) {
          						$errors[] = $err;
          					}
          					if (sizeof($errors) == 0) {
          						$errors[] = "Server error";
          					}
                    curl_close($ch);
          				} else if($httpCode == 200) {
                    move_uploaded_file($attachment['tmp_name'], getcwd() . '/attachments/' . $attachmentid . '-' . $attachment['name']);
                    $attachmentname = $attachment["name"];
                    $this->auditlog("saveAttachment", "success: $attachmentname");
                    curl_close($ch);
                    return $attachmentid;
          				}
          			}



            }

        }

        return $attachmentid;

    }

    // Adds a new thing to the database
    public function addThing($name, $attachment, &$errors) {

        // Get the user id from the session
        $user = $this->getSessionUser($errors);
        $userid = $user["userid"];
        $registrationcode = $user["registrationcode"];

        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing user ID. Not logged in?";
        }
        if (empty($name)) {
            $errors[] = "Missing thing name";
        }

        // Only try to insert the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {

            $attachmentid = $this->saveAttachment($attachment, $errors);

            // Only try to insert the data into the database if the attachment successfully saved
            if (sizeof($errors) == 0) {

                // Create a new ID
                $thingid = bin2hex(random_bytes(16));

                $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/addthing";
                $data = array(
                  'thingid'=>$thingid,
                  'thingname'=>$name,
                  'userid'=>$userid,
                  'attachmentid'=>$attachmentid,
                  'thingregistrationcode'=>$registrationcode
                );
                $data_json = json_encode($data);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response  = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($response === FALSE) {
                  $errors[] = "An unexpected error occurred adding the thing to the database.";
                  $this->debug($stmt->errorInfo());
                  $this->auditlog("addthing error", $stmt->errorInfo());
                } else {
                  if($httpCode == 400) {

                    // JSON was double-encoded, so it needs to be double decoded
                    $errorsList = json_decode(json_decode($response))->errors;
                    foreach ($errorsList as $err) {
                      $errors[] = $err;
                    }
                    if (sizeof($errors) == 0) {
                      $errors[] = "Bad input";
                    }
                    curl_close($ch);
                  } else if($httpCode == 500) {
                    $errorsList = json_decode(json_decode($response))->errors;
                    foreach ($errorsList as $err) {
                      $errors[] = $err;
                    }
                    if (sizeof($errors) == 0) {
                      $errors[] = "Server error";
                    }
                    curl_close($ch);
                  } else if($httpCode == 200) {
                    $this->auditlog("addthing", "success: $name, id = $thingid");
                    curl_close($ch);

                  }
                }

            }

        } else {
            $this->auditlog("addthing validation error", $errors);
        }

        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }

    // Adds a new comment to the database
    public function addComment($text, $thingid, $attachment, &$errors) {

        // Get the user id from the session
        $user = $this->getSessionUser($errors);
        $userid = $user["userid"];

        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing user ID. Not logged in?";
        }
        if (empty($thingid)) {
            $errors[] = "Missing thing ID";
        }
        if (empty($text)) {
            $errors[] = "Missing comment text";
        }

        // Only try to insert the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {


            $attachmentid = $this->saveAttachment($attachment, $errors);

            // Only try to insert the data into the database if the attachment successfully saved
            if (sizeof($errors) == 0) {

                // Create a new ID
                $commentid = bin2hex(random_bytes(16));

                $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/addcomment";
                $data = array(
                  'commentid'=>$commentid,
                  'commenttext'=>$text,
                  'commentuserid'=>$userid,
                  'commentthingid'=>$thingid,
                  'commentattachmentid'=>$attachmentid
                );
                $data_json = json_encode($data);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response  = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($response === FALSE) {
                  $errors[] = "An unexpected error occurred saving the comment to the database.";
                  $this->debug($stmt->errorInfo());
                  $this->auditlog("addcomment error", $stmt->errorInfo());
                } else {
                  if($httpCode == 400) {

                    // JSON was double-encoded, so it needs to be double decoded
                    $errorsList = json_decode(json_decode($response))->errors;
                    foreach ($errorsList as $err) {
                      $errors[] = $err;
                    }
                    if (sizeof($errors) == 0) {
                      $errors[] = "Bad input";
                    }
                    curl_close($ch);
                  } else if($httpCode == 500) {
                    $errorsList = json_decode(json_decode($response))->errors;
                    foreach ($errorsList as $err) {
                      $errors[] = $err;
                    }
                    if (sizeof($errors) == 0) {
                      $errors[] = "Server error";
                    }
                    curl_close($ch);
                  } else if($httpCode == 200) {
                    $this->auditlog("addcomment", "success: $commentid");
                    curl_close($ch);

                  }
                }

        } else {
            $this->auditlog("addcomment validation error", $errors);
        }
        }
        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }

    // Get a list of users from the database and will return the $errors array listing any errors encountered
    public function getUsers(&$errors) {

        // Assume an empty list of topics
        $users = array();

        $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/getusers";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === FALSE) {
          $errors[] = "An unexpected error occurred getting the user list.";
          $this->debug($stmt->errorInfo());
          $this->auditlog("getusers error", $stmt->errorInfo());
        } else {
          if($httpCode == 400) {

            // JSON was double-encoded, so it needs to be double decoded
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Bad input";
            }
            curl_close($ch);
          } else if($httpCode == 500) {
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Server error";
            }
            curl_close($ch);
          } else if($httpCode == 200) {
            $users = json_decode($response, true);
            $this->auditlog("getusers", "success");
            curl_close($ch);
            return $users;
          }
        }

        // Return the list of users
        return $users;

    }

    // Gets a single user from database and will return the $errors array listing any errors encountered
    public function getUser($userid, &$errors) {

        // Assume no user exists for this user id
        $user = NULL;

        // Validate the user input
        if (empty($userid)) {
            $errors[] = "Missing userid";
        }

        if(sizeof($errors)== 0) {

            // Get the user id from the session
            $user = $this->getSessionUser($errors);
            $loggedinuserid = $user["userid"];
            $isadmin = FALSE;

            // Check to see if the user really is logged in and really is an admin
            if ($loggedinuserid != NULL) {
                $isadmin = $this->isAdmin($errors, $loggedinuserid);
            }

            if (!$isadmin && $loggedinuserid != $userid) {

                $errors[] = "Cannot view other user";
                $this->auditlog("getuser", "attempt to view other user: $loggedinuserid");

            } else {

                // Only try to insert the data into the database if there are no validation errors
                if (sizeof($errors) == 0) {

                  $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/getuser?userid=" . $userid;
                  $ch = curl_init();
                  curl_setopt($ch, CURLOPT_URL, $url);
                  curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI'));
                  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                  $response  = curl_exec($ch);
                  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                  if ($response === FALSE) {
                    $errors[] = "An unexpected error occurred retrieving the specified user.";
                    $this->debug($stmt->errorInfo());
                    $this->auditlog("getuser error", $stmt->errorInfo());
                  } else {
                    if($httpCode == 400) {

                      // JSON was double-encoded, so it needs to be double decoded
                      $errorsList = json_decode(json_decode($response))->errors;
                      foreach ($errorsList as $err) {
                        $errors[] = $err;
                      }
                      if (sizeof($errors) == 0) {
                        $errors[] = "Bad input";
                      }
                      curl_close($ch);
                    } else if($httpCode == 500) {
                      $errorsList = json_decode(json_decode($response))->errors;
                      foreach ($errorsList as $err) {
                        $errors[] = $err;
                      }
                      if (sizeof($errors) == 0) {
                        $errors[] = "Server error";
                      }
                      curl_close($ch);
                    } else if($httpCode == 200) {
                      $user = json_decode($response, true)[0];
                      $this->auditlog("getusers", "success");
                      curl_close($ch);
                      return $user;
                    }
                  }

                } else {
                    $this->auditlog("getuser validation error", $errors);
                }
            }
        } else {
            $this->auditlog("getuser validation error", $errors);
        }

        // Return user if there are no errors, otherwise return NULL
        return $user;
    }


    // Updates a single user in the database and will return the $errors array listing any errors encountered
    public function updateUser($userid, $username, $email, $password, $isadminDB, &$errors) {

        // Assume no user exists for this user id
        $user = NULL;

        // Validate the user input
        if (empty($userid)) {

            $errors[] = "Missing userid";

        }

        if(sizeof($errors) == 0) {

            // Get the user id from the session
            $user = $this->getSessionUser($errors);
            $loggedinuserid = $user["userid"];
            $isadmin = FALSE;

            // Check to see if the user really is logged in and really is an admin
            if ($loggedinuserid != NULL) {
                $isadmin = $this->isAdmin($errors, $loggedinuserid);
            }

            if (!$isadmin && $loggedinuserid != $userid) {

                $errors[] = "Cannot edit other user";
                $this->auditlog("getuser", "attempt to update other user: $loggedinuserid");

            } else {

                // Validate the user input
                if (empty($userid)) {
                    $errors[] = "Missing userid";
                }
                if (empty($username)) {
                    $errors[] = "Missing username";
                }
                if (empty($email)) {
                    $errors[] = "Missing email;";
                }

                // Only try to update the data into the database if there are no validation errors
                if (sizeof($errors) == 0) {

                    $passwordhash = password_hash($password, PASSWORD_DEFAULT);
                    $adminFlag = ($isadminDB ? "1" : "0");
                    $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/updateuser";
                    $data = array(
                      'username'=>$username,
                      'email'=>$email,
                      'admin'=>$adminFlag,
                      'password'=>$passwordhash,
                      'userid'=>$userid
                    );
                    $data_json = json_encode($data);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response  = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($response === FALSE) {
                      $errors[] = "An unexpected error occurred saving the user profile. ";
                      $this->debug($stmt->errorInfo());
                      $this->auditlog("updateUser error", $stmt->errorInfo());
                    } else {
                      if($httpCode == 400) {

                        // JSON was double-encoded, so it needs to be double decoded
                        $errorsList = json_decode(json_decode($response))->errors;
                        foreach ($errorsList as $err) {
                          $errors[] = $err;
                        }
                        if (sizeof($errors) == 0) {
                          $errors[] = "Bad input";
                        }
                        curl_close($ch);
                      } else if($httpCode == 500) {
                        $errorsList = json_decode(json_decode($response))->errors;
                        foreach ($errorsList as $err) {
                          $errors[] = $err;
                        }
                        if (sizeof($errors) == 0) {
                          $errors[] = "Server error";
                        }
                        curl_close($ch);
                      } else if($httpCode == 200) {
                        $this->auditlog("updateUser", "success");
                        curl_close($ch);

                      }
                    }
                } else {
                    $this->auditlog("updateUser validation error", $errors);
                }
            }
        } else {
            $this->auditlog("updateUser validation error", $errors);
        }

        // Return TRUE if there are no errors, otherwise return FALSE
        if (sizeof($errors) == 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }

    // Validates a provided username or email address and sends a password reset email
    public function passwordReset($usernameOrEmail, &$errors) {

        // Check for a valid username/email
        if (empty($usernameOrEmail)) {
            $errors[] = "Missing username/email";
            $this->auditlog("session", "missing username");
        }

        // Only proceed if there are no validation errors
        if (sizeof($errors) == 0) {
          $passwordresetid = bin2hex(random_bytes(16));
          $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/passwordreset";
          $data = array(
            'username'=>$usernameOrEmail,
            'email'=>$usernameOrEmail,
            'passwordresetid'=>$passwordresetid
          );
          $data_json = json_encode($data);
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $url);
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
          curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $response  = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          if ($response === FALSE) {
            $this->auditlog("passwordReset error", $stmt->errorInfo());
            $errors[] = "An unexpected error occurred saving your request to the database.";
            $this->debug($stmt->errorInfo());
          } else {
            if($httpCode == 400) {

              // JSON was double-encoded, so it needs to be double decoded
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Bad input";
              }
              curl_close($ch);
            } else if($httpCode == 500) {
              $errorsList = json_decode(json_decode($response))->errors;
              foreach ($errorsList as $err) {
                $errors[] = $err;
              }
              if (sizeof($errors) == 0) {
                $errors[] = "Server error";
              }
              curl_close($ch);
            } else if($httpCode == 200) {
              $this->auditlog("passwordReset", "Sending message to $email");

              // Send reset email
              $pageLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
              $pageLink = str_replace("reset.php", "password.php", $pageLink);
              $to      = $response;
              $subject = 'Password reset';
              $message = "A password reset request for this account has been submitted at https://russellthackston.me. ".
                  "If you did not make this request, please ignore this message. No other action is necessary. ".
                  "To reset your password, please click the following link: $pageLink?id=$passwordresetid";
              $headers = 'From: webmaster@russellthackston.me' . "\r\n" .
                  'Reply-To: webmaster@russellthackston.me' . "\r\n";

              mail($to, $subject, $message, $headers);

              $this->auditlog("passwordReset", "Message sent to $email");
              curl_close($ch);

            }
          }

        }

    }

    function getFile($name){
        return file_get_contents($name);
    }

    // Get a list of users from the database and will return the $errors array listing any errors encountered
    public function getAttachmentTypes(&$errors) {

        // Assume an empty list of topics
        $types = array();

        $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/getattachmenttypes";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === FALSE) {
          $errors[] = "An unexpected error occurred getting the attachment types list.";
          $this->debug($stmt->errorInfo());
          $this->auditlog("getattachmenttypes error", $stmt->errorInfo());
        } else {
          if($httpCode == 400) {

            // JSON was double-encoded, so it needs to be double decoded
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Bad input";
            }
            curl_close($ch);
          } else if($httpCode == 500) {
            $errorsList = json_decode(json_decode($response))->errors;
            foreach ($errorsList as $err) {
              $errors[] = $err;
            }
            if (sizeof($errors) == 0) {
              $errors[] = "Server error";
            }
            curl_close($ch);
          } else if($httpCode == 200) {
            $types = json_decode($response, true);
            $this->auditlog("getattachmenttypes", "success");
            curl_close($ch);
            return $types;
          }
        }
        // Return the list of users
        return $types;

    }

    // Creates a new session in the database for the specified user
    public function newAttachmentType($name, $extension, &$errors) {

        $attachmenttypeid = NULL;

        // Check for a valid name
        if (empty($name)) {
            $errors[] = "Missing name";
        }
        // Check for a valid extension
        if (empty($extension)) {
            $errors[] = "Missing extension";
        }

        // Only try to query the data into the database if there are no validation errors
        if (sizeof($errors) == 0) {

            // Create a new session ID
            $attachmenttypeid = bin2hex(random_bytes(25));

            $url = "https://zcz3dwfpn5.execute-api.us-east-1.amazonaws.com/default/newattachmenttype";
            $data = array(
              'attachmenttypeid '=>$attachmenttypeid ,
              'name'=>$name,
              'extension'=>$extension
            );
            $data_json = json_encode($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-api-key: OZ80hhKCvG8ecUWDMTcpGaLAWDswZeMP31Axs9NI', 'Content-Type: application/json','Content-Length: ' . strlen($data_json)));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response  = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response === FALSE) {
              $errors[] = "An unexpected error occurred";
              $this->debug($stmt->errorInfo());
              $this->auditlog("newAttachmentType error", $stmt->errorInfo());
              return NULL;
            } else {
              if($httpCode == 400) {

                // JSON was double-encoded, so it needs to be double decoded
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Bad input";
                }
                curl_close($ch);
              } else if($httpCode == 500) {
                $errorsList = json_decode(json_decode($response))->errors;
                foreach ($errorsList as $err) {
                  $errors[] = $err;
                }
                if (sizeof($errors) == 0) {
                  $errors[] = "Server error";
                }
                curl_close($ch);
              } else if($httpCode == 200) {
                $this->auditlog("newAttachmentType error", $errors);
                curl_close($ch);
                return $attachmenttypeid;
              }
            }

        } else {

            $this->auditlog("newAttachmentType error", $errors);
            return NULL;

        }

        return $attachmenttypeid;
    }

}


?>
