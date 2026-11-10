<?php
// app/Config/JWT.php
namespace Config;

use CodeIgniter\Config\BaseConfig;

class JWT extends BaseConfig
{
    // Generate a long random string once, store it in .env, never commit it
    public string $secretKey = ''; // loaded below from env

    public function __construct()
    {
        parent::__construct();
        $this->secretKey = getenv('JWT_SECRET_KEY') ?: '';
    }
}