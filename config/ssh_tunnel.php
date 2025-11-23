<?php

/**
 * SSH Tunnel Handler for Domeneshop MySQL Connection
 * Creates secure SSH tunnel from Replit to Domeneshop server
 */

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\RSA;

class SSHTunnel {
    private static $tunnel = null;
    private static $ssh = null;
    
    public static function initialize() {
        // Only initialize once
        if (self::$tunnel !== null) {
            return true;
        }
        
        try {
            // Get credentials from environment
            $sshHost = getenv('SSH_HOST');
            $sshUser = getenv('SSH_USER');
            $sshPassword = getenv('SSH_PASSWORD');
            $sshKeyPath = getenv('SSH_KEY_PATH');
            
            // Validate required credentials
            if (!$sshHost || !$sshUser) {
                error_log("SSH Tunnel: Missing SSH_HOST or SSH_USER");
                return false;
            }
            
            // Connect via SSH
            self::$ssh = new SSH2($sshHost);
            
            // Authenticate with password or key
            $authenticated = false;
            
            if ($sshKeyPath && file_exists($sshKeyPath)) {
                // Try key-based authentication
                $privateKey = RSA::load(file_get_contents($sshKeyPath));
                $authenticated = self::$ssh->login($sshUser, $privateKey);
                error_log("SSH Tunnel: Authenticated with key at $sshKeyPath");
            } elseif ($sshPassword) {
                // Try password authentication
                $authenticated = self::$ssh->login($sshUser, $sshPassword);
                error_log("SSH Tunnel: Authenticated with password");
            } else {
                error_log("SSH Tunnel: No authentication method provided (no password or key)");
                return false;
            }
            
            if (!$authenticated) {
                error_log("SSH Tunnel: Authentication failed for user $sshUser on $sshHost");
                self::$ssh = null;
                return false;
            }
            
            error_log("SSH Tunnel: Successfully connected to $sshHost");
            self::$tunnel = true;
            return true;
            
        } catch (Exception $e) {
            error_log("SSH Tunnel Exception: " . $e->getMessage());
            self::$ssh = null;
            self::$tunnel = false;
            return false;
        }
    }
    
    public static function isConnected() {
        return self::$tunnel === true;
    }
    
    public static function close() {
        if (self::$ssh !== null) {
            self::$ssh->disconnect();
            self::$ssh = null;
            self::$tunnel = null;
        }
    }
}

// Register shutdown hook to close tunnel
register_shutdown_function([SSHTunnel::class, 'close']);
