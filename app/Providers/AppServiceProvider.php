<?php

namespace App\Providers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('database.default') === 'sqlite') {
            $database = config('database.connections.sqlite.database');
            if ($database === ':memory:' || str_contains((string) $database, ':memory:')) {
                Log::warning('Database is SQLite in-memory: data will not persist between requests. Set DB_DATABASE to a file path (e.g. database/database.sqlite) in .env');
            }
        }

        Storage::extend('azure', function ($app, $config) {
            $client = BlobRestProxy::createBlobService(
                $config['connection_string']
            );
            $adapter = new AzureBlobStorageAdapter(
                $client,
                $config['container'],
                $config['prefix'] ?? ''
            );
            return new FilesystemAdapter(
                new Filesystem($adapter),
                $adapter,
                $config
            );
        });
    }
}
