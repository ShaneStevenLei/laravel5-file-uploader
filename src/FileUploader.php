<?php
namespace ShaneStevenLei\FileUploader;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use \Exception;

define('UPLOAD_CHUNK_FILE_EXIST', 0);
define('UPLOAD_CHUNK_FILE_FAILURE', 1);
define('UPLOAD_CHUNK_FILE_SUCCESS', 2);
define('UPLOAD_FILE_EXIST', 3);
define('UPLOAD_FILE_SAME_NAME_DIFFERENT_CONTENT', 4);
define('UPLOAD_FILE_FAILURE', 5);
define('UPLOAD_FILE_SUCCESS', 6);

class FileUploader
{

    private $disk;

    private $diskPath;

    private $saveFile;

    private $chunkFile;

    public function save($diskName, $destination, $uploadFile = array())
    {
        $this->init($diskName, $destination, $uploadFile);
        if ($this->checkSameNameFile()) {
            if ($this->checkUploadFileMd5()) {
                return UPLOAD_FILE_EXIST;
            }
            return UPLOAD_FILE_SAME_NAME_DIFFERENT_CONTENT;
        }
        if ($this->checkChunk()) {
            return UPLOAD_CHUNK_FILE_EXIST;
        }
        if (!$this->moveFile()) {
            // Move the upload file to the target directory
            return UPLOAD_CHUNK_FILE_FAILURE;
        }
        // Get all chunks
        $chunkfiles = $this->disk->files($this->chunkFile['targetPath']);
        // check the file upload finished
        if (count($chunkfiles) * $this->chunkFile['fileSize'] >=
            ($this->saveFile['fileSize'] - $this->chunkFile['fileSize'] + 1)) {
            if (!$this->createFileFromChunks($chunkfiles)) {
                return UPLOAD_FILE_FAILURE;
            }
            Log::info('-------------------------------------------------------');
            Log::info(__CLASS__ . ': save ' . $this->saveFile['fileRealPath'] . ' successfully!');
            Log::info('-------------------------------------------------------');
            return UPLOAD_FILE_SUCCESS;
        }
        return UPLOAD_CHUNK_FILE_SUCCESS;
    }

    private function init($diskName, $destination, $uploadFile = array())
    {
        $this->disk     = Storage::disk($diskName);
        $this->diskPath = $this->disk->getDriver()->getAdapter()->getPathPrefix();

        $this->saveFile['fileSize']     = $uploadFile['fileSize'];
        $this->saveFile['fileName']     = $uploadFile['fileName'];
        $this->saveFile['fileMd5']      = $uploadFile['md5'];
        $this->saveFile['fileChunks']   = $uploadFile['totalChunks'];
        $this->saveFile['filePath']     = $destination . DIRECTORY_SEPARATOR . $this->saveFile['fileName'];
        $this->saveFile['fileRealPath'] = $this->diskPath . $this->saveFile['filePath'];
        $this->saveFile['targetPath']   = $destination;

        $this->chunkFile['file']                 = $uploadFile['chunkFile'];
        $this->chunkFile['fileId']               = $uploadFile['chunkNumber'];
        $this->chunkFile['fileName']             = $uploadFile['fileName'] . '.part' . $this->chunkFile['fileId'];
        $this->chunkFile['fileSize']             = $uploadFile['chunkSize'];
        $this->chunkFile['targetPath']           = $destination . DIRECTORY_SEPARATOR . $this->saveFile['fileMd5'] . '_TMP';
        $this->chunkFile['targetRealPath']       = $this->diskPath . $this->chunkFile['targetPath'];
        $this->chunkFile['filePath']             = $this->chunkFile['targetPath'] . DIRECTORY_SEPARATOR . $this->chunkFile['fileName'];
        $this->chunkFile['fileRealPath']         = $this->diskPath . $this->chunkFile['filePath'];
        $this->chunkFile['completeFilePath']     = $this->chunkFile['targetPath'] . DIRECTORY_SEPARATOR . $this->saveFile['fileName'];
        $this->chunkFile['completeFileRealPath'] = $this->diskPath . $this->chunkFile['completeFilePath'];
    }

    /**
     * Check the chunk whether exist
     *
     * @param  string     $diskName
     * @param  string     $destination
     * @param  array      $uploadFile
     * @return bool       if exist return true, else return false.
     */
    private function checkChunk()
    {
        if ($this->disk->exists($this->chunkFile['filePath'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check the upload file whether exist in the storage used by its md5
     *
     * @return bool    if exist return true, else return false.
     */
    private function checkUploadFileMd5()
    {
        if ($this->saveFile['fileMd5'] == md5_file($this->saveFile['fileRealPath'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check upload file whether exist
     * @method uploadFileExist
     * @param  string  $diskName
     * @param  string  $destination
     * @param  string  $fileName
     * @return bool    if exist return true, else return false.
     */
    private function checkSameNameFile()
    {
        return $this->disk->exists($this->saveFile['filePath']);
    }

    /**
     * Move the upload file to the temp directory
     *
     * @return bool  if move successully return true, else return false.
     */
    private function moveFile()
    {
        try {
            if (!$this->disk->exists($this->chunkFile['targetPath'])) {
                $this->disk->makeDirectory($this->chunkFile['targetPath'], 0644, true, true);
            }
            $this->chunkFile['file']->move($this->chunkFile['targetRealPath'], $this->chunkFile['fileName']);
        } catch (Exception $exception) {
            Log::error('-------------------------------------------------------');
            Log::error(__CLASS__ . ': save ' . $this->chunkFile['fileRealPath'] . ' unsuccessfully! ' . PHP_EOL . $exception->getMessage());
            Log::error('-------------------------------------------------------');
            return false;
        }
        return true;
    }

    /**
     * Create the target file from the chunks file
     *
     * @param  array     $chunkfiles
     * @return bool      if create successfully return true, else return false.
     */
    private function createFileFromChunks($chunkfiles)
    {
        try {
            // Open file
            $fp = fopen($this->chunkFile['completeFileRealPath'], 'w');
            // Write file
            for ($i = 1; $i <= count($chunkfiles); $i++) {
                fwrite($fp, $this->disk->get($this->chunkFile['targetPath'] . DIRECTORY_SEPARATOR . $this->saveFile['fileName'] . '.part' . $i));
            }
            // Close file
            fclose($fp);
            $this->disk->move($this->chunkFile['completeFilePath'], $this->saveFile['filePath']);
            $this->disk->move($this->chunkFile['targetPath'], $this->chunkFile['targetPath'] . '_UNUSED');
            $this->disk->deleteDirectory($this->chunkFile['targetPath'] . '_UNUSED');
        } catch (Exception $exception) {
            if ($this->disk->exists($this->chunkFile['filePath'])) {
                $this->disk->delete($this->chunkFile['filePath']);
            }
            Log::error('-------------------------------------------------------');
            Log::error(__CLASS__ . ': create ' . $this->saveFile['fileRealPath'] . ' unsuccessfully!' . PHP_EOL . $exception->getMessage());
            Log::error('-------------------------------------------------------');
            return false;
        }
        return true;
    }
}
