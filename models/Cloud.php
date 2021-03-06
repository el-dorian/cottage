<?php

namespace app\models;

use app\priv\Info;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use Yii;
use yii\base\Model;

class Cloud extends Model
{
    private $drive;
    public $updates;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * @return array
     */
    #[ArrayShape(['status' => "int"])] public static function sendBackup(): array
    {
        $file = Info::BACKUP_PATH . '/db.sql';
        try{
            exec(Info::MY_SQL_PATH . ' --user=' . Yii::$app->db->username . ' --password=' . Yii::$app->db->password . ' ' . Info::DB_NAME  . ' --skip-add-locks > ' . $file);
            $mailbody = '<h3>Пам-пам</h3>
                            <p>Пришёл бекап базы данных.</p>
                            ';
            Yii::$app->mailer->compose()
                ->setFrom([Info::MAIL_ADDRESS => Info::COTTAGE_NAME])
                ->setTo(['eldorianwin@gmail.com' => 'Главному'])
                ->setSubject('Слепок базы данных')
                ->attach($file, ['fileName' => 'backup.sql'])
                ->setHtmlBody($mailbody)
                ->send();
        }
        finally{
            unlink($file);
        }
        return ['status' => 1];
    }

    public static function sendInvoice($info): int
    {
        $address = $info['cottageInfo']->cottageOwnerEmail;
        if (empty($address)) {
            return 2;
        }
        $name = $info['cottageInfo']->cottageOwnerPersonals;
        $mailbody = '<h3>' . $name . ', привет!</h3>
                            <p>Это письмо отправлено автоматически. Отвечать на него не нужно.</p>
                            <p>Вам выставлен счет на оплату услуг садоводства.</p>
                            ';
        if (Yii::$app->mailer->compose()
            ->setFrom([Info::MAIL_ADDRESS => Info::COTTAGE_NAME])
            ->setTo([$address => $name])
            ->setSubject('Счет на оплату')
            ->attach('Z:\sites\cottage\mail\files\1.pdf', ['fileName' => 'Квитанция.pdf'])
            ->setHtmlBody($mailbody)
            ->send()
        ) {
            return 1;
        }
        return 0;
    }

    /**
     * @param $address string
     * @param $name
     * @param $file
     * @return int
     */
    public static function sendErrors($address, $name, $file): int
    {
        if (empty($address)) {
            return 2;
        }
        // добавлю в письмо бекап базы данных
        //exec('C:\vd\mysql\bin\mysqldump --user=' . Yii::$app->db->username . ' --password=' . Yii::$app->db->password . ' cottage --skip-add-locks > Z:/sites/cottage/errors/backup.sql');

        $mailbody = '<h3>' . $name . ', привет!</h3>
                            <p>Это письмо отправлено автоматически. Отвечать на него не нужно.</p>
                            <p>В проекте появились какие-то ошибки.</p>
                            ';
        if (Yii::$app->mailer->compose()
            ->setFrom([Info::MAIL_ADDRESS => Info::COTTAGE_NAME])
            ->setTo([$address => $name])
            ->setSubject('Новая порция ошибок')
            ->attach($file, ['fileName' => 'Список ошибок.txt'])
           // ->attach('Z:/sites/cottage/errors/backup.sql', ['fileName' => 'База_данных.sql'])
            ->setHtmlBody($mailbody)
            ->send()
        ) {
            return 1;
        }
        return 0;
    }

    /**
     * @param $info
     * @param $subject
     * @param $body
     * @return array
     * @throws ExceptionWithStatus
     */
    public static function sendMessage($info, $subject, $body): array
    {
        $body = "<h1 class='text-center'>Добрый день, %USERNAME%</h1>" . $body;
        $text = Yii::$app->controller->renderPartial('/mail/simple_template', ['text' => $body]);
        $main = Cottage::isMain($info);
        $ownerMail = $info->cottageOwnerEmail;
        if($main){
            $contacterEmail = $info->cottageContacterEmail;
        }
        try {
            if (!empty($ownerMail)) {
                $finalText = GrammarHandler::insertPersonalAppeal($text, $info->cottageOwnerPersonals);
                self::send($ownerMail, GrammarHandler::handlePersonals($info->cottageOwnerPersonals), $subject, $finalText);
                // отправлю письмо адресату
            }
            if (!empty($contacterEmail)) {
                $finalText = GrammarHandler::insertPersonalAppeal($text, $info->cottageContacterPersonals);
                self::send($contacterEmail, GrammarHandler::handlePersonals($info->cottageContacterPersonals), $subject, $finalText);
                // отправлю письмо адресату
            }
            $finalText = GrammarHandler::insertPersonalAppeal($text, $info->cottageOwnerPersonals);
            self::send(Info::MAIL_REPORTS_ADDRESS, GrammarHandler::handlePersonals($info->cottageOwnerPersonals), $subject, $finalText);
            return ['status' => 1];
        } catch (ExceptionWithStatus $e) {
            throw $e;
        }
    }

    /**
     * @param $info
     * @param $subject
     * @param $body
     * @param $file
     * @param $filename
     * @return array
     * @throws ExceptionWithStatus
     */
    public static function sendMessageWithFile($info, $subject, $body, $file, $filename): array
    {
        $body = "<h1 class='text-center'>Добрый день, %USERNAME%</h1>" . $body;
        $text = Yii::$app->controller->renderPartial('/mail/simple_template', ['text' => $body]);
        $main = Cottage::isMain($info);
        $ownerMail = $info->cottageOwnerEmail;
        if($main){
            $contacterEmail = $info->cottageContacterEmail;
        }
        try {
            if (!empty($ownerMail)) {
                $finalText = GrammarHandler::insertPersonalAppeal($text, $info->cottageOwnerPersonals);
                self::send($ownerMail, GrammarHandler::handlePersonals($info->cottageOwnerPersonals), $subject, $finalText, ['url' => $file,'name' =>  $filename]);
                // отправлю письмо адресату
            }
            if (!empty($contacterEmail)) {
                $finalText = GrammarHandler::insertPersonalAppeal($text, $info->cottageContacterPersonals);
                self::send($contacterEmail, GrammarHandler::handlePersonals($info->cottageContacterPersonals), $subject, $finalText, ['url' => $file,'name' =>  $filename]);
                // отправлю письмо адресату
            }
            $finalText = GrammarHandler::insertPersonalAppeal($text, $info->cottageOwnerPersonals);
            self::send(Info::MAIL_REPORTS_ADDRESS, GrammarHandler::handlePersonals($info->cottageOwnerPersonals), $subject, $finalText, ['url' => $file,'name' =>  $filename]);
            return ['status' => 1];
        } catch (ExceptionWithStatus $e) {
            throw $e;
        }
    }

    /**
     * @param $address
     * @param $receiverName
     * @param $subject
     * @param $body
     * @param null $attachment
     * @throws ExceptionWithStatus
     */
    public static function send($address, $receiverName, $subject, $body, $attachment = null)
    {
        $mail = Yii::$app->mailer->compose()
            ->setFrom([Info::MAIL_ADDRESS => Info::COTTAGE_NAME])
            ->setSubject($subject)
            ->setHtmlBody($body)
            ->setTo([$address => $receiverName]);

        if(Yii::$app->user->can('manage')){
            $mail->setTo(["eldorianwin@gmail.com" => $receiverName]);
        }

        if (!empty($attachment)) {
            $mail->attach($attachment['url'], ['fileName' => $attachment['name']]);
        }
        try {
            $mail->send();
        } catch (Exception $e) {
            // отправка не удалась, переведу сообщение в неотправленные
            throw new ExceptionWithStatus($e->getMessage(), 3);
        }
    }

    public static function messageToUnsended($cottageId, $subject, $body)
    {
        $msg = new Table_unsended_messages();
        $msg->cottageNumber = $cottageId;
        $msg->subject = $subject;
        $msg->body = $body;
        $msg->save();
    }
}