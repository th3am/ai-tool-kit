<?php

namespace App\Livewire;

use App\Models\ToolJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use WithFileUploads;

    public string $activeTab = 'overview';

    public ?TemporaryUploadedFile $avatarUpload = null;

    public ?TemporaryUploadedFile $coverUpload = null;

    public function updatedAvatarUpload(): void
    {
        $this->validate([
            'avatarUpload' => 'image|max:2048',
        ]);

        $user = Auth::user();
        $this->deletePublicUpload($user->avatar);

        $path = $this->avatarUpload->store('avatars', 'public');
        $user->forceFill([
            'avatar' => asset('storage/'.$path),
        ])->save();

        $this->reset('avatarUpload');
        $this->dispatch('profile-updated');
    }

    public function updatedCoverUpload(): void
    {
        $this->validate([
            'coverUpload' => 'image|max:4096',
        ]);

        $user = Auth::user();
        $this->deletePublicUpload($user->profile_cover);

        $path = $this->coverUpload->store('profile-covers', 'public');
        $user->forceFill([
            'profile_cover' => asset('storage/'.$path),
        ])->save();

        $this->reset('coverUpload');
        $this->dispatch('profile-updated');
    }

    public function removeAvatar(): void
    {
        $user = Auth::user();
        $this->deletePublicUpload($user->avatar);

        $user->forceFill([
            'avatar' => null,
        ])->save();

        $this->dispatch('profile-updated');
    }

    public function removeCover(): void
    {
        $user = Auth::user();
        $this->deletePublicUpload($user->profile_cover);

        $user->forceFill([
            'profile_cover' => null,
        ])->save();

        $this->dispatch('profile-updated');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['overview', 'recent'], true) ? $tab : 'overview';
    }

    public function render()
    {
        $user = Auth::user()->fresh(['plan']);

        $recentJobs = ToolJob::where('user_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        $jobsCount = ToolJob::where('user_id', $user->id)->count();
        $lastJob = ToolJob::where('user_id', $user->id)->latest()->first();
        $filesUploaded = ToolJob::where('user_id', $user->id)
            ->latest()
            ->take(200)
            ->get()
            ->filter(fn (ToolJob $job) => $this->jobHasUploadedFile($job))
            ->count();

        return view('livewire.profile', [
            'user' => $user,
            'initials' => $this->initials($user->name),
            'recentJobs' => $recentJobs,
            'jobsCount' => $jobsCount,
            'filesUploaded' => $filesUploaded,
            'lastActivity' => $lastJob?->updated_at?->diffForHumans() ?? 'No activity yet',
        ])->layout('components.layouts.app');
    }

    private function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);

        if (! $parts) {
            return 'AD';
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : mb_substr($parts[0], 1, 1);

        return mb_strtoupper($first.$last) ?: 'AD';
    }

    private function jobHasUploadedFile(ToolJob $job): bool
    {
        $params = $job->params ?? [];

        foreach (['uploaded_file', 'uploadedFile', 'file_path', 'document_path', 'source_file'] as $key) {
            if (! empty($params[$key])) {
                return true;
            }
        }

        return false;
    }

    private function deletePublicUpload(?string $url): void
    {
        if (! $url || ! str_contains($url, '/storage/')) {
            return;
        }

        $path = ltrim(strstr($url, '/storage/'), '/');
        $path = preg_replace('#^storage/#', '', $path);

        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
