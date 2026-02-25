<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSettingsTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_settings', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
			$table->string('ui_primary')->default('blue');
			$table->string('ui_neutral')->default('slate');
			$table->string('ui_dark_mode')->default('light');
			$table->string('language')->default('en');
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
		Schema::dropIfExists('user_settings');
	}
}
