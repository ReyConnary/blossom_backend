<?php
// app/Database/Migrations/2026-11-10-000002_CreateRefreshTokens.php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRefreshTokens extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'personnel_id'  => ['type' => 'INT', 'constraint' => 11],
            'token'         => ['type' => 'VARCHAR', 'constraint' => 255], // sha256 hash
            'expires_at'    => ['type' => 'DATETIME'],
            'created_at'    => ['type' => 'DATETIME'],
            'revoked'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('token');
        $this->forge->createTable('refresh_tokens');
    }

    public function down()
    {
        $this->forge->dropTable('refresh_tokens');
    }
}