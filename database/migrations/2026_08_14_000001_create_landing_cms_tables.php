<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Client Logos ──
        Schema::create('landing_client_logos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short')->nullable();
            $table->string('logo')->nullable(); // path storage, boleh kosong -> fallback badge teks
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Navbar Menu ──
        Schema::create('landing_menu', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Services ──
        Schema::create('landing_services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Statistics ──
        Schema::create('landing_statistics', function (Blueprint $table) {
            $table->id();
            $table->string('icon');
            $table->string('statistic');
            $table->string('label');
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Team Members ──
        Schema::create('landing_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position');
            $table->string('image')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Testimonials ──
        Schema::create('landing_testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position')->nullable();
            $table->string('photo')->nullable();
            $table->text('message');
            $table->enum('placement', ['beranda', 'services'])->default('beranda');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Contact Messages (inbound, read-only utk admin) ──
        Schema::create('landing_contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // ── Footer: company info (singleton) ──
        Schema::create('landing_footer_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('copyright')->nullable();
            $table->timestamps();
        });

        // ── Footer: social links ──
        Schema::create('landing_footer_socials', function (Blueprint $table) {
            $table->id();
            $table->string('platform');
            $table->string('url');
            $table->timestamps();
        });

        // ── Landing Content (singleton — hero, contact hero, ceo quote, dst) ──
        Schema::create('landing_content', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('hero_title')->nullable();
            $table->text('hero_desc')->nullable();
            $table->string('cta_primary_label')->nullable();
            $table->string('cta_primary_url')->nullable();
            $table->string('cta_secondary_label')->nullable();
            $table->string('cta_secondary_url')->nullable();
            $table->string('proof_text')->nullable();
            $table->string('contact_hero_title')->nullable();
            $table->string('contact_hero_subtitle')->nullable();
            $table->string('contact_maps_url')->nullable();
            $table->string('projects_page_label')->nullable();
            $table->string('projects_page_title')->nullable();
            $table->string('projects_page_subtitle')->nullable();
            $table->string('ceo_name')->nullable();
            $table->string('ceo_position')->nullable();
            $table->text('ceo_comment')->nullable();
            $table->string('ceo_photo')->nullable();
            $table->timestamps();
        });

        // ── Projects ──
        Schema::create('landing_projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('category')->nullable(); // web / mobile
            $table->string('url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_projects');
        Schema::dropIfExists('landing_content');
        Schema::dropIfExists('landing_footer_socials');
        Schema::dropIfExists('landing_footer_settings');
        Schema::dropIfExists('landing_contact_messages');
        Schema::dropIfExists('landing_testimonials');
        Schema::dropIfExists('landing_teams');
        Schema::dropIfExists('landing_statistics');
        Schema::dropIfExists('landing_services');
        Schema::dropIfExists('landing_menu');
        Schema::dropIfExists('landing_client_logos');
    }
};
