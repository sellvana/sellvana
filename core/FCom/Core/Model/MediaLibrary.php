<?php

/**
 * Class FCom_Core_Model_MediaLibrary
 * @property int $id
 * @property string $folder
 * @property string $subfolder
 * @property string $file_name
 * @property string $file_size
 * @property string $data_serialized
 * @property string $create_at
 * @property string $update_at
 * @property FCom_Core_Main $FCom_Core_Main
 * @property BLocale $BLocale
 * @property BFile $BFile
 */
class FCom_Core_Model_MediaLibrary extends FCom_Core_Model_Abstract
{
    protected static $_table = 'fcom_media_library';
    protected static $_origClass = __CLASS__;
    protected static $_importExportProfile = [
        'skip'        => ['id', 'create_at', 'update_at', 'file_size'],
        'unique_key'  => ['folder', 'subfolder', 'file_name'],
        'custom_data' => true
    ];
    public function onAfterLoad()
    {
        parent::onAfterLoad();

        $size = $this->file_size;
        $this->file_size = $this->BUtil->convertFileSize($size);
        return $this;
    }

    public function onAfterDelete()
    {
        parent::onAfterDelete();
        //delete file
        $file = $this->FCom_Core_Main->dir($this->folder) .'/'.$this->file_name;
        $this->BUtil->deleteFileSafely($file);

        return $this;
    }

    public function onBeforeSave()
    {
        parent::onBeforeSave();
        if (!$this->BDebug->is(BDebug::MODE_IMPORT)) {
            return $this;
        }

        $link = $this->getData('import_download_link');

        if ($link === null){
            throw new PDOException($this->_('Model does not have link to file for download it'));
        }

        $this->file_size = 0;

        if (!empty($this->file_name)) {
            /** @var BFile $file */
            #try {
                $file = $this->BFile->load($link);
                $dir = $this->FCom_Core_Main->dir($this->folder);
                if (!$this->BUtil->isPathWithinRoot($dir . '/' . $this->file_name, ['@media_dir', '@random_dir'])) {
                    throw new BException('Invalid file path: ' . $dir . '/' . $file);
                }
                $file->save($this->file_name, $dir);
                $fileInfo = $file->getFileInfo();

                //TODO: in future need check to media type.
                //if (strpos($file['file_mime_type'], 'image/') === 0) {}

                $this->file_size = $fileInfo['file_size'];

                unset($file);
            #} catch (BException $e) {
                //TODO: what to do, when we can't get file?
            #}
        }

        return $this;
    }
}
