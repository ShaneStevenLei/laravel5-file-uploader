<?php namespace ShaneStevenLei\FileUploader;

/*
 * This file is part of the php-file-uploader package.
 *
 * (c) ShaneStevenLei <shanestevenlei@outlook.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Illuminate\Support\Facades\Facade;

class FileUploaderFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'FileUploader';
    }
}
