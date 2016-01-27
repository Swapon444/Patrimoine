<?php




use \model\Users as Users;

class Connection extends Controller
{

    function Connection()
    {
        parent::Controller();
    }

    // Afficher la page de connexion pour se connecter au site
    function render()
    {
        if(isset($_COOKIE["userToken"]))
        {
            header(CONNECTION_HEADER . '/login');
        }
        else
        {
            if(isset($_SESSION["id"]))
            {
                switch ($_SESSION["role"]) 
                {
                    case ROLE_SYSADMIN:
                        header(SYSADMIN_HEADER); 
                        break;
                    case ROLE_FAMOWNER:
                        header(MOD_HEADER);
                        break;
                    case ROLE_MOD:
                        header(MOD_HEADER);
                        break;
                    case ROLE_USER:
                        header(OBJECTS_HEADER);
                        break;
                }
            }
            $this->renderTemplate(file_get_contents(CONNECTION_PAGE));
        }
    }

    function login()
    {
        if((!empty($_POST["txtPassword"]) and !empty($_POST["txtUserName"])) or isset($_COOKIE["userToken"]))
        {
            // Appel à la base de données pour valider les données de connexion
            if(!isset($_COOKIE["userToken"]))
            {
                $userId = Users::getUserIdByName(strtolower($_POST["txtUserName"]));
            }
            else
            {
                $userId = Users::getUserIdByToken($_COOKIE["userToken"]);
            }
                
            if($userId != -1 )
            {
                // Le nom d'utilisateur existe
                $user = Users::getUser($userId);
                $tokenCode = "";
                if(isset($_COOKIE["userToken"]))
                {
                    $token = Users::getTokenByUserId($userId);
                    if(Users::getTokenEndDate($token) > time())
                    {
                        $tokenCode = $token;
                        
                    }
                    else
                    {
                        Users::deleteCookieToken($userId);
                        $tokenCode = "none"; 
                    }
                }
            
                if ((!empty($_POST["txtPassword"]) and crypt($_POST["txtPassword"], $user["UserSalt"]) == $user["UserHash"]) or ( isset($_COOKIE["userToken"]) and $_COOKIE["userToken"] == $tokenCode))
                {
                
                    // Mot de passe correct
                    if(isset($_SESSION["path"]))
                    {
                        $path = $_SESSION["path"];
                        $_SESSION["path"] = "";
                        header('Location:' . $path);
                    }
                    else
                    {
                        if(!Users::isUserAdmin($userId))
                        {
                            if(Users::isUserActivated($userId))
                            {
                            
                                self::setSessionAndCookie($userId);
                                if($_SESSION["role"] == ROLE_FAMOWNER or $_SESSION["role"] == ROLE_MOD)
                                    header(MOD_HEADER);
                                else
                                    //usager normal
                                    header(OBJECTS_HEADER);
                            }
                            else
                            {
                                $_SESSION = Array();
                                $data = array
                                (
                                   "Inactivated" => true
                                );
                                $this->renderTemplate(file_get_contents(CONNECTION_PAGE), $data);
                            }
                        }
                        else
                        {
                            // L'utilisateur est un administrateur système
                            self::setSessionAndCookie($userId);
                            header(SYSADMIN_HEADER); 
                        }
                    }
                }
                else
                {
                    // Mot de passe incorrect
                    $data = array
                    (
                        "Error" => true
                        
                    );
                    $this->renderTemplate(file_get_contents(CONNECTION_PAGE), $data);
                }
            }
            else
            {
                // Mot de passe incorrect
                $data = array
                (
                    "Error" => true
                );
                $this->renderTemplate(file_get_contents(CONNECTION_PAGE), $data);
            }
        }
        else
        {
            // Mot de passe incorrect
            $data = array
            (
                "Error" => true
            );
            $this->renderTemplate(file_get_contents(CONNECTION_PAGE), $data); 
        }
    }
    
    function setSessionAndCookie($_userId)
    {
        //se souvenir de moi
        $rememberMe = isset($_POST["chkConnection"])?$_POST["chkConnection"]:"" ;
        $_SESSION["id"] = $_userId;
        $_SESSION["role"] = (Users::isUserAdmin($_userId) ? ROLE_SYSADMIN : (Users::isUserFamilyOwner($_userId) ? ROLE_FAMOWNER : (Users::isUserMod($_userId)? ROLE_MOD : ROLE_USER)));
        if(!empty($rememberMe))
        {
            $tokenCode = self::generateCode(32);
            Users::deleteCookieToken($_userId);
            Users::setCookieToken($_userId, $tokenCode);
            setcookie("userToken",$tokenCode,time() + 86400*7, "/");               
        }
    }
    
    function logout()
    {
        //Détruire le cookie de l'utilisateur en cours
        setcookie("userToken","",time() - 42000, "/");
        //Détruire toutes les variables de session
        $_SESSION = Array();
        //Détruire les cookies de session
        if (ini_get("session.use_cookies")) 
        {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,$params["path"], $params["domain"],$params["secure"], $params["httponly"]);
        }
        //Détruire la session
        session_destroy();
        
        $data = array
        (
            "Disconnection" => true
        );
        $this->renderTemplate(file_get_contents(CONNECTION_PAGE), $data);
    }
    
    function registration()
    {
        $data = array
        (
            "Registration" => true
        );
        $this->renderTemplate(file_get_contents(CONNECTION_PAGE), $data);
    }
    
    function forgot()
    {
        $userId = Users::getUserIdByName($_POST["txtUserNameForgot"]);
        if( $userId != -1)
        {
            $code = self::generateCode(16);
            Users::addCode($userId, $code);
			$headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

			$subject = "Réinitialisation de votre mot de passe";
			
            $data = array(
                'passwordResetLink' => SERVER_ABSOLUTE_PATH ."/passwordreset",
                'code' => $code,
				'img' => PUBLIC_ABSOLUTE_PATH . "/assets/favicons/android-icon-192x192.png"
            );
            $mustache = new Mustache_Engine();
            mail($_POST["txtUserNameForgot"],$subject , $mustache->render(file_get_contents('public/html/mailtemplate.html'), $data), $headers . "From: " . SENDING_EMAIL);
        }

        $data = array
        (
            "Forgot" => true
        );
        $this->renderTemplate(file_get_contents(CONNECTION_PAGE), $data);
    }

    static function generateCode($_nb)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $result = "";
        for ($i = 0; $i < $_nb; $i++) {
            $result .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $result;
    }
    
}