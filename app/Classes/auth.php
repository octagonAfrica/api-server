<?php

namespace App\Classes\Auth;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;
use Exception;

class Auth extends JWT {
    // private const JWT_KEY = "4fdb00b053be01b3875d8538e8c6062c"; //MD5 encode for OPASJWTSecret
       
    public function getCredentialsFromAuthHeader($auth_header) {
        /**
         * method decodes the value of a basic auth header 
         * and returns the decoded username (auth key/consumer key) 
         * and password (auth secret/consumer secret)
         **/
        
        //check if header is passed 
       
        if(!empty($auth_header)) {
            $auth_arr = explode(' ', $auth_header);
            $credentials = explode(':',base64_decode($auth_arr[1])); //$auth_arr[0] is the word 'Basic'
            
            $username = $credentials[0]; //API_KEY
            $password = $credentials[1]; //API_SECRET
            
            if (empty($username) || empty($password) ) {
                throw new Exception("Authentication header not properly formatted, should be: 'Basic base64_encode(API_KEY:API_SECRET)'");
            }
            
            return Array( 'username' => $username, 'password' => $password );
            
        } else {
            throw new Exception("Authentication header value was not set");
        }
    }
    
    public function generateToken($payload) {
        $key = env('JWT_KEY');
        $jwt = JWT::encode($payload, $key, 'HS256');
        return $this->$jwt;
    }
    
    public function decodeToken($token) {
        //JWT::$leeway = 200;
        $key = env('JWT_KEY');
        
        try{
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            // $decoded = $this->decode($token, self::JWT_KEY, array('HS256'));
            //$decoded is an object, below turns it into an associative array
            return $decoded_array = (array) $decoded;

        } catch(Throwable $e) {
            throw new Exception( "Invalid Authorization token: ". $e->getMessage() );
        }
    }
    
    
    
}
