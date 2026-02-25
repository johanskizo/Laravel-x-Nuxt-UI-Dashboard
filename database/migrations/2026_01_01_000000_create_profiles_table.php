<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfilesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('profiles', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
			$table->string('photo')->nullable();
			$table->string('identity_number', 50);
			$table->string('full_name');
			$table->date('birth_date')->nullable();
			$table->enum('gender', ['male', 'female', 'other'])->nullable();
			$table->string('phone_number', 20)->nullable();
			$table->text('address')->nullable();
			$table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->onDelete('no action');
			$table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnUpdate()->onDelete('no action');
			$table->foreignId('deleted_by')->nullable()->constrained('users')->cascadeOnUpdate()->onDelete('no action');
			$table->timestamps();
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('profiles');
	}
}
