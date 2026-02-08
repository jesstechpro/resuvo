<?php

namespace App\Services;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ResumeStorage
{
    public function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(config('filesystems.resumes_disk', 'resumes'));
    }

    public function storeOriginal(User $user, UploadedFile $file): Resume
    {
        $this->ensureDirectoryExists('originals/' . $user->id);

        $path = $file->store('originals/' . $user->id, [
            'disk' => config('filesystems.resumes_disk', 'resumes'),
        ]);

        return $user->resumes()->create([
            'type' => Resume::TYPE_ORIGINAL,
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
        ]);
    }

    public function storeGenerated(User $user, int $jobDescriptionId, string $content, ?string $filename = null): Resume
    {
        $dir = 'generated/' . $user->id;
        $this->ensureDirectoryExists($dir);

        $filename = $filename ?? config('resume.generated_filename', 'resume.html');
        $path = $dir . '/' . uniqid() . '_' . $filename;
        $this->disk()->put($path, $content);

        return $user->resumes()->create([
            'job_description_id' => $jobDescriptionId,
            'type' => Resume::TYPE_GENERATED,
            'path' => $path,
            'filename' => $filename,
        ]);
    }

    private function ensureDirectoryExists(string $relativePath): void
    {
        $root = $this->resumesRoot();
        $fullPath = $root . '/' . $relativePath;
        if (! is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
    }

    private function resumesRoot(): string
    {
        $disk = config('filesystems.resumes_disk', 'resumes');

        return config("filesystems.disks.{$disk}.root", storage_path('app/resumes'));
    }

    public function getContents(Resume $resume): string
    {
        return $this->disk()->get($resume->path);
    }

    public function exists(Resume $resume): bool
    {
        return $this->disk()->exists($resume->path);
    }

    public function delete(Resume $resume): bool
    {
        if ($this->exists($resume)) {
            $this->disk()->delete($resume->path);
        }
        return $resume->delete();
    }
}
