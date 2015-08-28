<?php namespace ShaneStevenLei\FileUploader;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileUploader
{
    public function save($destination, Request $request)
    {
        $this->destination = $destination;
        $this->request     = isset($request) ? $request : Request::capture();
        Log::info($this->destination);
    }
}
