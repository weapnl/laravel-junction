<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_temporary_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->index()->constrained(app(config('auth.providers.users.model'))->getTable())->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media_temporary_uploads');
    }
};
