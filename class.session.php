<?php
/*
    class.session.php
        This is the main script
*/


class MemberSession
{
    // system vars used in the script
    public $error;
    public $logged_in; 
    private $login_user;
    private $login_pass;
      
    public function __construct()
    {
        // start our session
        session_start();
                
        // Check session for verifcation
        $this->SessionCheck();
    }
    
    /*
        SessionCheck
         > Check if userID has a valid sessionID and IP
         -> Check if the row has valid timeout 
    */
    private function SessionCheck()
    {
        global $db;
        
        if(isset($_SESSION['userid']))
        {
            // Check session table for valid session ID & IP
            $checksession = $db->query("SELECT * FROM user_sessions WHERE user_id ='".$_SESSION['userid']."' AND session_id ='".session_id()."' AND ipv4 ='".$_SERVER['REMOTE_ADDR']."'");
            
            if($checksession->num_rows == 1)
            {
                // Valid session ID, user ID, and IP match in database
                
                // Get query data
                $checksession = $checksession->fetch_array(MYSQLI_ASSOC);
                                
                // Check the timeout + cookie
                if($checksession['timeout'] > time() && $_COOKIE[COOKIE_NAME] == $this->FingerPrint($_SESSION['userid']))
                {
                    // VALID USER
                    
                    // Update timeout
                    $this->UpdateSession(session_id());
                    
                    // We are logged in
                    $this->logged_in = true;
                }
                else
                {
                    // NOT VALID TIMEOUT (OR) COOKIE
                    
                    // Remove the session ID from timeout 
                    $db->query("DELETE FROM user_sessions WHERE session_id ='".session_id()."'");
                    
                    // We are not logged in
                    $this->logged_in = false;
 
                    // Script detected $_SESSION data, remove it due to timeout
                    $this->DestorySession(session_id());
                    
                    // Error report
                    $this->error = 'Invalid Timeout';
                }
            } else
            {
                // The Session ID / IP / User query did not return a result
                $this->error = 'Invalid Session';
            }
        } else
        {
            // No Session data is found 
            $this->error = 'No Session';
        }
    }
    
    
    /*
        Updates Session timeout in database
    */
    private function UpdateSession($sessionid)
    {
        global $db;
        
        // Timeout var
        $timeout = time() + LOGIN_TIMEOUT;
        
        // Update timeout in database
        $db->query("UPDATE user_sessions SET timeout ='".$timeout."' WHERE session_id ='".$sessionid."'");
        
        // Update timeout of cookie
        setcookie(COOKIE_NAME, $this->FingerPrint($_SESSION['userid']), $timeout);
    }

/*
        CheckUser
         > Create a login_attempt row for IP
         > Check the # of login attempts
         > Verify the input information
         > Query for input information
    */
    public function CheckUser($username,$password)
    {
        global $db;
        
        // Query for failed logins
        $login_attempts = $db->query("SELECT * FROM user_failed WHERE time BETWEEN '".date("G:i:s",strtotime("-1 hour"))."' and '".date("G:i:s",strtotime("+1 hour"))."' AND ip='".$_SERVER['REMOTE_ADDR']."' AND date='".date("Y-m-d")."'" );
        $login_attempts = $login_attempts->num_rows;
        
        // Input clean 
        $username = mysql_escape_string($username);
        $password = mysql_escape_string($password);
        
        // Input validation
        if(strlen($username) < 3)
        {
            $this->error = 'Username is too short';
            return false;
        }
        if(strlen($password) < 3)
        {
            $this->error = 'Password is too short';
            return false;
        }
        
        // Check number of login attempts
        if($login_attempts > LOGIN_ATTEMPTS)
        {
            $this->error = 'You have failed too many times. Try again in a hour.';
            return false;
        } 
        else
        {
            // SALT and MD5 password
            $password = PASSWORD_SALT . $password . PASSWORD_SALT;
            $password = md5($password);
            
            // Query for user details
            $checkuser = $db->query("SELECT * from user_accounts WHERE username='".$username."' AND password='".$password."'");
            
            // Check if there is a result
            if($checkuser->num_rows == 1)
            {
                // Return row data
                $checkuser = $checkuser->fetch_array(MYSQLI_ASSOC);
                // Log the user in
                $this->LoginUser($checkuser);  
            }
            else 
            {
                // Login failed error
                $login_attempts++;
                $this->error = "invalid login #$login_attempts";
                
                // Log the fail
                $failed_login = $db->query("INSERT INTO user_failed (ip, user, pass, time, date) VALUES ('".$_SERVER['REMOTE_ADDR']."', '".$username."', '".$password."', '".date("G:i:s")."', '".date("Y-m-d")."')");
                
                return false;
            }
         }        
    } 
    
    
    /*
        LoginUser
         > Create session row in database
         > Update the user account row with lastlogin, IP
    */
    private function LoginUser($user_data)
    {
        global $db;
        
        $id = $user_data['ID'];
        $this->logged_in = true;
        
        // Timeout var
        $timeout = time() + LOGIN_TIMEOUT;
        ;
        // Put our fingerprint in the cookie
        setcookie(COOKIE_NAME, $this->FingerPrint($id), $timeout);
               
        // Create a session row
        $db->query("INSERT INTO user_sessions (user_id, hidden, session_id, ipv4, timeout) VALUES ('".$id."','0','".session_id()."','".$_SERVER['REMOTE_ADDR']."','".$timeout."')") or die ($db->error);
        
        // Update the user account
        $db->query("UPDATE user_accounts SET loginip ='".$_SERVER['REMOTE_ADDR']."', lastlogin='".time()."' WHERE ID='".$id."'") or die ($db->error);
        
        // Pass the row data to RegisterSession
        $this->RegisterSession($user_data);
    }
       
    
    /*
        RegisterSession
        > Return SQL array data into $_SESSION
    */
    private function RegisterSession($user)
    {
        global $db;
        
        if($this->logged_in)
        {           
            // Register vars
            $_SESSION['userid']   = $user['ID'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];
        }   
    }
     
    
    /*
        Logout
    */
    public function DestorySession()
    {
        global $db;
        $id = session_id();
        
        // Remove session from database
        $db->query("DELETE FROM user_sessions WHERE session_id ='".$id."'");
        // Remove session vars
        session_unset();
        // Destory session
        session_destroy();
        // Remove cookie
        setcookie(COOKIE_NAME, '', time() - LOGIN_TIMEOUT);
        
        $this->logged_in = false;    
    }
    
    
    /*
        Session Stats for the user
            This is not used in script. 
    */
    public function ActiveSession($var)
    {
        global $db;
        
        // What type of session are we checking
        
        if($var == "timeout")
        {   
            $time = time();
            $timeouts = $db->query("SELECT * FROM user_sessions WHERE timeout <'".$time."' AND user_id ='".$this->logged_userid."'");
            return $timeouts->num_rows;      
        }
        
        
        if($var == "active")
        {
            $time = time();
            $active = $db->query("SELECT * FROM user_sessions WHERE timeout >'".$time."' AND user_id ='".$this->logged_userid."'");
            return $active->num_rows;     
        }
        
        if($var == "user")
        {
            $user= $db->query("SELECT * FROM user_sessions WHERE user_id ='".$this->logged_userid."'");
            return $user->num_rows;     
        }
    }
    
    private function FingerPrint($id)
    {
        // Create a user fingerprint
        $fingerprint = md5($id . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
        return $fingerprint; 
    }
}

?>