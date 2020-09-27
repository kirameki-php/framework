<?php

use Kirameki\Database\Migration\Migration;
use Kirameki\Database\Schema\Builders\CreateTableBuilder;

class CreateUser extends Migration
{
    public function up(): void
    {
        $this->on('userdata')->createTable('User')->tap(function(CreateTableBuilder $t) {
            $t->uuid('id')->primaryKey()->notNull();
            $t->string('name', 100)->default('Anonymous');
        });
    }
}
