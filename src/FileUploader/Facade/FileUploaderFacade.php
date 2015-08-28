<?php
/*
 * This file is part of the php-file-uploader package.
 *
 * (c) ShaneStevenLei <shanestevenlei@outlook.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ShaneStevenLei\FileUploader\Facade;

use Illuminate\Support\Facades\Facade;

class FileUploaderFacade extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'FileUploader';
    }
}
