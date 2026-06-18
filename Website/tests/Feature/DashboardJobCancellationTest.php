<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\ToolJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardJobCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_snapshot_is_attached_to_the_actual_root_element(): void
    {
        $user = User::factory()->create([
            'whatsapp_number' => '+201000000009',
            'is_verified' => true,
        ]);

        $html = Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->html();

        $this->assertMatchesRegularExpression('/^\s*<div\s+wire:snapshot=/', $html);
        $this->assertFalse(str_starts_with(
            file_get_contents(resource_path('views/livewire/dashboard.blade.php')),
            "\xEF\xBB\xBF"
        ));
    }

    public function test_every_dashboard_tool_can_be_selected(): void
    {
        $user = User::factory()->create([
            'whatsapp_number' => '+201000000000',
            'is_verified' => true,
        ]);

        foreach ([
            'mindmap-generator',
            'audio',
            'video-animation',
            'powerpoint-generator',
            'video-explainer',
            'lecture',
            'quiz-generator',
        ] as $tool) {
            Livewire::actingAs($user)
                ->test(Dashboard::class)
                ->call('selectTool', $tool)
                ->assertSet('selectedTool', $tool)
                ->assertSet('step', 2);
        }
    }

    public function test_dashboard_resumes_and_cancels_owned_job(): void
    {
        $user = User::factory()->create([
            'whatsapp_number' => '+201000000001',
            'is_verified' => true,
        ]);
        $job = ToolJob::create([
            'user_id' => $user->id,
            'tool_type' => 'video-explainer',
            'status' => 'queued',
            'params' => ['topic' => 'Artificial intelligence'],
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSet('isProcessing', true)
            ->assertSet('pollingJobId', $job->id)
            ->assertSet('processingStage', 'Waiting for a queue worker…')
            ->call('cancelJob')
            ->assertSet('isProcessing', false)
            ->assertSet('pollingJobId', null)
            ->assertSet('step', 1)
            ->assertSee('The job was cancelled successfully.');

        $this->assertDatabaseHas('tool_jobs', [
            'id' => $job->id,
            'user_id' => $user->id,
            'status' => ToolJob::STATUS_CANCELLED,
            'error_message' => 'Cancelled by the user.',
        ]);
    }

    public function test_dashboard_does_not_resume_stale_job(): void
    {
        $user = User::factory()->create([
            'whatsapp_number' => '+201000000002',
            'is_verified' => true,
        ]);
        $job = ToolJob::create([
            'user_id' => $user->id,
            'tool_type' => 'video-explainer',
            'status' => 'running',
        ]);
        ToolJob::whereKey($job->id)->update([
            'updated_at' => now()->subMinutes(45),
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSet('isProcessing', false)
            ->assertSet('pollingJobId', null);

        $this->assertDatabaseHas('tool_jobs', [
            'id' => $job->id,
            'status' => 'failed',
            'error_message' => 'The background worker stopped responding before this job completed.',
        ]);
    }
}
