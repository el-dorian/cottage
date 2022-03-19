<?php


namespace app\models\utils;


use app\models\Telegram;
use app\priv\Info;
use Yii;
use yii\base\Model;
use yii\db\Exception;

class DbRestore extends Model
{
    public $file;

    public function rules(): array
    {
        return [
            [['file'], 'file', 'skipOnEmpty' => false],
            [['file'], 'required'],
        ];
    }

    public function restore(): void
    {
        $file = $this->file->tempName;
        $fileName = Info::BACKUP_PATH . '/db.sql';
        file_put_contents($fileName, file_get_contents($file));
        $cmd  = Info::MY_SQL_PATH . ' --user=' . Yii::$app->db->username . ' --password=' . Yii::$app->db->password . ' ' . Info::DB_NAME . ' < ' . $fileName;
        exec($cmd);
        Yii::$app->session->addFlash('success', 'База данных восстановлена!');
    }
}