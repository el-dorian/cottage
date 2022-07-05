<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\Cottage;
use app\models\database\TelegramHandler;
use app\models\Telegram;
use app\models\Utils;
use app\models\utils\FileUtils;
use app\priv\Info;
use DateTime;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ConsoleController extends Controller
{
    /**
     * Актуализация данных об участках
     * @return int Exit code
     * @throws \Exception
     */
    public function actionRefreshMainData(): int
    {

        if (FileUtils::isUpdateInProgress()) {
            echo "try later\n";
            return ExitCode::OK;
        }
        FileUtils::setUpdateInProgress();
        $existedCottages = Cottage::getRegister();
        echo "start refresh\n";
        Utils::reFillFastInfo($existedCottages);
        echo "done\n";
        FileUtils::setUpdateFinished();
        return ExitCode::OK;
    }

    public function actionTelegramSendDebug($message): void
    {
        $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/telebot_err_' . time() . '.log';
        file_put_contents($file, $message);
        $errorCounter = 0;
        while (true) {
            if($errorCounter > 5){
                break;
            }
            try {
                // проверю, есть ли учётные записи для отправки данных
                $subscribers = TelegramHandler::findAll(['log_level' => 1]);
                if (!empty($subscribers)) {
                    $token = Info::TG_BOT_TOKEN;
                    /** @var BotApi|Client $bot */
                    $bot = new Client($token);
                    foreach ($subscribers as $subscriber) {
                        $bot->sendMessage($subscriber->person_id, 'test');
                    }
                }
                break;
            } catch (InvalidArgumentException | Exception $e) {
                $errorCounter++;
            }
        }
    }

    public function actionDoBackup(): void
    {
        $command = Info::MY_SQL_PATH . 'dump --user=' . Yii::$app->db->username . ' --password=' . Yii::$app->db->password . ' ' . Info::DB_NAME . ' --skip-add-locks > ' . Info::BACKUP_PATH . '/db.sql';
        exec($command);
        $date = new DateTime();
        $d = $date->format('Y-m-d H:i:s');
        Telegram::sendDebugFile(Info::BACKUP_PATH . '/db.sql', "Резервная копия базы данных СНТ Облепиха {$d}.sql", 'application/sql');
    }
}
