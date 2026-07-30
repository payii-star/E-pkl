    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::table('transaction_items', function (Blueprint $table) {
                // Tambahkan kolom baru setelah kolom 'product_id'
                $table->foreignId('product_variant_id')
                    ->after('product_id')
                    ->constrained('product_variants')
                    ->onDelete('cascade');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::table('transaction_items', function (Blueprint $table) {
                // Hapus foreign key constraint sebelum menghapus kolom
                $table->dropForeign(['product_variant_id']);
                $table->dropColumn('product_variant_id');
            });
        }
    };