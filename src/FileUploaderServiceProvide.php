<?php
/*
 * This file is part of the php-file-uploader package.
 *
 * (c) ShaneStevenLei <shanestevenlei@outlook.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ShaneStevenLei\FileUploader;

use Illuminate\Support\ServiceProvider;

class FileUploaderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config files
        $this->publishes([
            __DIR__ . '/config/resumable' => base_path('public/resumable'),
        ]);
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->blind('FileUploader', function ($app) {
            return new FileUploader();
        });
    }
}
