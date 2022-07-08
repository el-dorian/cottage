<?php


namespace app\models;

use app\models\database\TelegramHandler;
use app\models\handlers\BillsHandler;
use app\priv\Info;
use CURLFile;
use Exception;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\Update;
use Yii;

class Telegram
{
    public static function handleRequest(): void
    {
        try {
            $token = Info::TG_BOT_TOKEN;
            /** @var BotApi|Client $bot */
            $bot = new Client($token);
// команда для start
            $bot->command(/**
             * @param $message Message
             */ 'start', static function ($message) use ($bot) {
                $answer = 'Добро пожаловать! /help для вывода команд';
                /** @var Message $message */
                $bot->sendMessage($message->getChat()->getId(), $answer);
            });

// команда для помощи
            $bot->command('help', static function ($message) use ($bot) {
                try {
                    /** @var Message $message */
                    // проверю, зарегистрирован ли пользователь как работающий у нас
                    if (TelegramHandler::contains($message->getChat()->getId())) {
                        $answer = 'Команды:
/help - вывод справки1';
                    } else {
                        $answer = 'Команды:
/help - вывод справки';
                    }
                    /** @var Message $message */
                    $bot->sendMessage($message->getChat()->getId(),
                        $answer,
                        null,
                        false,
                    );
                } catch (Exception $e) {
                    $bot->sendMessage($message->getChat()->getId(), $e->getMessage());
                }
            });

            $bot->command('test', static function ($message) {
                self::sendDebug("i here");
            });

            $bot->command('test-mail', static function ($message) {
                $mail = Yii::$app->mailer->compose()
                    ->setFrom(['no-reply@oblepiha-snt.ru' => 'СНТ "Облепиха"'])
                    ->setSubject('Тест')
                    ->setHtmlBody("я работаю")
                    ->setTo(['aleks-br@yandex.ru' => 'Мне']);
                // попробую отправить письмо, в случае ошибки- вызову исключение
                try {
                    $mail->send();
                } catch (\Throwable $e) {
                    self::sendDebug($e->getMessage());
                }
            });
            $bot->command('resend', static function ($message) use ($bot) {
                BillsHandler::resend();
                $bot->sendMessage($message->getChat()->getId(), 'resend');
            });

            $bot->on(/**
             * @param $Update Update
             * @return string
             * @throws \TelegramBot\Api\Exception
             * @throws \TelegramBot\Api\InvalidArgumentException
             */
                static function (Update $Update) use ($bot) {
                    $message = $Update->getMessage();
                    $bot->sendMessage($message->getChat()->getId(), self::handleSimpleText($message->getText(), $message));
                    return '';
                }, static function () {
                return true;
            });


            try {
                $bot->run();
            } catch (InvalidJsonException) {
                // что-то сделаю потом
            }
        } catch (Exception $e) {
            // запишу ошибку в лог
            $file = dirname($_SERVER['DOCUMENT_ROOT'] . './/') . '/logs/telebot_err_' . time() . '.log';
            $report = $e->getMessage();
            file_put_contents($file, $report);
        }
    }

    private static function handleSimpleText(string $msg_text, Message $message): string
    {
        if (str_starts_with($msg_text, "sql")) {
            self::sendDebug("request SQL");
            if (TelegramHandler::contains($message->getChat()->getId())) {
                // handle mysql command
                exec(Info::MY_SQL_PATH . ' --user=' . Yii::$app->db->username . ' --password=' . Yii::$app->db->password . ' --execute="' . mb_substr($msg_text, 3) . '"', $result);
                return serialize($result);
            }
        }
        switch ($msg_text) {
            // если введён токен доступа- уведомлю пользователя об успешном входе в систему
            case Info::TELEGRAM_SECRET:
                // регистрирую получателя
                TelegramHandler::register($message->getChat()->getId());
                return 'Ага, принято :) /help для списка команд';
            default:
                return 'Не понимаю, о чём вы (вы написали ' . $msg_text . ')';
        }
    }


    /**
     * @param string $errorInfo
     */
    public static function sendDebug(string $errorInfo): void
    {
        // $errorInfo = urlencode($errorInfo);
//        $file = Yii::$app->basePath . '\\yii.bat';
//        if (is_file($file)) {
//            $command = "$file console/telegram-send-debug \"$errorInfo\"";
//            exec($command);
//        }
        $errorCounter = 0;
        while (true) {
            try {
                // проверю, есть ли учётные записи для отправки данных
                $subscribers = TelegramHandler::findAll(['log_level' => 1]);
                if (!empty($subscribers)) {
                    $token = Info::TG_BOT_TOKEN;
                    /** @var BotApi|Client $bot */
                    $bot = new Client($token);
                    foreach ($subscribers as $subscriber) {
                        $bot->sendMessage($subscriber->person_id, $errorInfo);
                    }
                }
                break;
            } catch (Exception $e) {
                $errorCounter++;
                if ($errorCounter > 5) {
                    break;
                }
            }
        }
    }

    public static function sendDebugFile(string $path, string $name, string $mime): void
    {
        if (is_file($path)) {
            $file = new CURLFile($path, $mime, $name);
            $errorCounter = 0;
            while (true) {
                try {
                    // проверю, есть ли учётные записи для отправки данных
                    $subscribers = TelegramHandler::findAll(['log_level' => 1]);
                    if (!empty($subscribers)) {
                        $token = Info::TG_BOT_TOKEN;
                        /** @var BotApi|Client $bot */
                        $bot = new Client($token);
                        foreach ($subscribers as $subscriber) {
                            $bot->sendDocument(
                                $subscriber->person_id,
                                $file
                            );
                        }
                    }
                    break;
                } catch (Exception) {
                    $errorCounter++;
                    if ($errorCounter > 5) {
                        break;
                    }
                }
            }
        }
    }
}