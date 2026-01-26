<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite doesn't support modifying enums directly
        // We need to recreate the table with the new enum values
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=off');

            DB::statement('
                CREATE TABLE users_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    role VARCHAR(255) CHECK(role IN (\'editor\', \'admin\', \'api\')) DEFAULT \'editor\',
                    email_verified_at TIMESTAMP NULL,
                    password VARCHAR(255) NOT NULL,
                    remember_token VARCHAR(100) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');

            DB::statement('INSERT INTO users_new SELECT * FROM users');
            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_new RENAME TO users');

            DB::statement('PRAGMA foreign_keys=on');
        } else {
            // MySQL/MariaDB
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('editor', 'admin', 'api') DEFAULT 'editor'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=off');

            DB::statement('
                CREATE TABLE users_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    role VARCHAR(255) CHECK(role IN (\'editor\', \'admin\')) DEFAULT \'editor\',
                    email_verified_at TIMESTAMP NULL,
                    password VARCHAR(255) NOT NULL,
                    remember_token VARCHAR(100) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');

            // Only copy users that don't have the 'api' role
            DB::statement("INSERT INTO users_new SELECT * FROM users WHERE role != 'api'");
            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_new RENAME TO users');

            DB::statement('PRAGMA foreign_keys=on');
        } else {
            // MySQL/MariaDB - first update any 'api' users to 'editor'
            DB::statement("UPDATE users SET role = 'editor' WHERE role = 'api'");
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('editor', 'admin') DEFAULT 'editor'");
        }
    }
};
