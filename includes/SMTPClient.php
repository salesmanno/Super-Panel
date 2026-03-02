<?php
class SMTPClient {
    private $socket;
    private $host;
    private $port;
    private $username;
    private $password;
    private $timeout = 30;
    private $debug = false;
    
    public function __construct($host, $port, $username, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }
    
    public function send($from, $fromName, $to, $subject, $body) {
        try {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            $protocol = ($this->port == 465) ? 'ssl://' : 'tcp://';
            $this->socket = @stream_socket_client(
                $protocol . $this->host . ':' . $this->port,
                $errno,
                $errstr,
                $this->timeout,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            if (!$this->socket) {
                throw new Exception("Kunne ikke koble til SMTP-server: $errstr ($errno)");
            }
            
            stream_set_timeout($this->socket, $this->timeout);
            
            $this->getResponse();
            
            $this->sendCommand("EHLO " . gethostname(), 250);
            
            if ($this->port == 587) {
                $this->sendCommand("STARTTLS", 220);
                stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->sendCommand("EHLO " . gethostname(), 250);
            }
            
            $this->sendCommand("AUTH LOGIN", 334);
            $this->sendCommand(base64_encode($this->username), 334);
            $this->sendCommand(base64_encode($this->password), 235);
            
            $this->sendCommand("MAIL FROM:<$from>", 250);
            $this->sendCommand("RCPT TO:<$to>", 250);
            $this->sendCommand("DATA", 354);
            
            $msgId = '<' . uniqid('', true) . '@salesman.no>';
            $headers = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "Message-ID: $msgId\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: quoted-printable\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            $headers .= "X-Mailer: Salesman.no Mailer\r\n";
            $headers .= "\r\n";
            
            $message = $headers . $body . "\r\n.";
            $this->sendCommand($message, 250);
            
            $this->sendCommand("QUIT", 221);
            
            fclose($this->socket);
            return ['success' => true];
            
        } catch (Exception $e) {
            if ($this->socket) {
                @fclose($this->socket);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function sendCommand($command, $expectedCode) {
        fwrite($this->socket, $command . "\r\n");
        $response = $this->getResponse();
        $code = (int)substr($response, 0, 3);
        
        if ($code !== $expectedCode) {
            throw new Exception("SMTP-feil: Forventet $expectedCode, fikk: $response");
        }
        
        return $response;
    }
    
    private function getResponse() {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return trim($response);
    }
}
