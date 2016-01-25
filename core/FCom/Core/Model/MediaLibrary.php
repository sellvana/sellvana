<?php defined('BUCKYBALL_ROOT_DIR') || die();

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
        'skip'       => ['id', 'create_at', 'update_at'],
        'unique_key' => ['folder', 'subfolder', 'file_name']
    ];
    public function onAfterLoad()
    {
        parent::onAfterLoad();

        $size = $this->file_size;
        if ($size / (1024 * 1024) > 1) {
            $size = round($size / (1024 * 1024), 2) . ' MB';
        } else if ($size / 1024 > 1) {
            $size = round($size / 1024, 2) . ' KB';
        } else {
            $size = $size . ' Bytes';
        }
        $this->file_size = $size;

        return $this;
    }

    public function onAfterDelete()
    {
        parent::onAfterDelete();
        //delete file
        $file = $this->FCom_Core_Main->dir($this->folder) .'/'.$this->file_name;
        if (file_exists($file)) {
            @unlink($file);
        }

        return $this;
    }

    public function onAfterCreate()
    {
        parent::onAfterCreate();

        if (!$this->BDebug->is(BDebug::MODE_IMPORT)) {
            return $this;
        }

        $link = $this->getData('downloadLink');
        //TODO: in future need check to media type.
        if (!$link) {
            throw new PDOException($this->BLocale->_('Model does not have link to file'));
        }

        /** @var BFile $file */
        $file = $this->BFile->load($link);
        $file->save($this->get('file_name'), $this->FCom_Core_Main->dir($this->folder));
        $fileInfo = $file->getFileInfo();
        unset($file);

        $this->set('file_size', $fileInfo['file_size']);
        $this->setData('downloadLink', false);

        return $this;
    }
}
