<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskDeadlineSubmissionTest extends TestCase
{
    public function test_user_cannot_submit_after_deadline(): void
    {
        $user = User::create([
            'name' => 'Dika',
            'email' => 'dika.deadline.' . uniqid() . '@test.com',
            'password' => bcrypt('12345678'),
            'status' => 'aktif',
        ]);

        $task = Task::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'title' => 'Tugas terlambat',
            'description' => 'Harus diblokir jika deadline lewat',
            'status' => 'belum',
            'due_date' => '2026-08-01',
        ]);

        $this->actingAs($user)
            ->postJson('/api/tasks/' . $task->id . '/submit', [
                'submission_note' => 'telat',
                'attachments' => [UploadedFile::fake()->image('late.png', 200, 200)],
            ])
            ->assertStatus(422)
            ->assertJson(['message' => 'Deadline tugas sudah lewat, tidak bisa mengirim tugas lagi.']);
    }

    public function test_admin_assessment_list_excludes_landing_users(): void
    {
        Role::firstOrCreate(['name' => 'hr-admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'admin-landing', 'guard_name' => 'api']);

        $admin = User::create([
            'name' => 'Admin HR',
            'email' => 'admin.hr.' . uniqid() . '@test.com',
            'password' => bcrypt('12345678'),
            'status' => 'aktif',
        ]);
        $admin->assignRole('hr-admin');

        $landing = User::create([
            'name' => 'Admin Landing',
            'email' => 'admin.landing.' . uniqid() . '@test.com',
            'password' => bcrypt('12345678'),
            'status' => 'aktif',
        ]);
        $landing->assignRole('admin-landing');

        $intern = User::create([
            'name' => 'Intern Satu',
            'email' => 'intern.satu.' . uniqid() . '@test.com',
            'password' => bcrypt('12345678'),
            'status' => 'aktif',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-01-10',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/assessments?date=2025-01-10')
            ->assertOk()
            ->assertJsonFragment(['email' => $intern->email])
            ->assertJsonMissing(['email' => $landing->email]);
    }

    public function test_unfinished_deadline_or_no_deadline_task_reduces_score_and_reverts_when_done(): void
    {
        $user = User::create([
            'name' => 'Nia Assessment',
            'email' => 'nia.assessment.' . uniqid() . '@test.com',
            'password' => bcrypt('12345678'),
            'status' => 'aktif',
            'tanggal_mulai' => '2025-01-01',
            'tanggal_selesai' => '2025-01-10',
        ]);

        $expiredTask = Task::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'title' => 'Tugas lewat deadline',
            'description' => 'Harus memotong nilai bila belum selesai',
            'status' => 'belum',
            'due_date' => '2025-01-05',
            'created_at' => Carbon::parse('2025-01-01 08:00:00'),
            'updated_at' => Carbon::parse('2025-01-01 08:00:00'),
        ]);

        $noDeadlineTask = Task::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'title' => 'Tugas tanpa deadline',
            'description' => 'Harus memotong nilai bila tidak dikerjakan',
            'status' => 'belum',
            'created_at' => Carbon::parse('2025-01-02 08:00:00'),
            'updated_at' => Carbon::parse('2025-01-02 08:00:00'),
        ]);

        $this->travelTo(Carbon::parse('2025-01-10 12:00:00'));

        $response = $this->actingAs($user)->getJson('/api/assessments/me');

        $response->assertOk()
            ->assertJsonPath('data.system_score', 0)
            ->assertJsonPath('data.score_breakdown.task_deduction', 100);

        $expiredTask->update(['status' => 'selesai', 'submitted_at' => Carbon::parse('2025-01-10 09:00:00')]);
        $noDeadlineTask->update(['status' => 'selesai', 'submitted_at' => Carbon::parse('2025-01-10 09:30:00')]);

        $responseAfterDone = $this->actingAs($user)->getJson('/api/assessments/me');

        $responseAfterDone->assertOk()
            ->assertJsonPath('data.system_score', 100)
            ->assertJsonPath('data.score_breakdown.task_deduction', 0);
    }
}
