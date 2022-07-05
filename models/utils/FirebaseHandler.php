<?php /** @noinspection PhpMultipleClassDeclarationsInspection */


namespace app\models\utils;


use app\models\CashHandler;
use app\models\database\Accruals_target;
use app\models\database\FirebaseDeviceBinding;
use app\models\database\Notifications;
use app\models\interfaces\CottageInterface;
use app\models\interfaces\TransactionsInterface;
use app\models\Table_payment_bills;
use app\models\Table_power_months;
use app\models\Table_tariffs_target;
use app\models\Table_transactions;
use app\models\Telegram;
use app\models\TimeHandler;
use app\priv\Info;
use Exception;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use Throwable;

class FirebaseHandler
{
    public function sendNewBillNotification(Table_payment_bills $bill): void
    {
        $messageTitle = 'Выставлен новый счёт';
        $messageText = "Счёт №$bill->id на сумму $bill->totalSumm руб. Просмотрите подробности в приложении, в разделе \"Счета\"";
        // add notification to table
        $notification = new Notifications([
            'cottage_id' => $bill->cottageNumber,
            'title' => $messageTitle,
            'message' => $messageText,
            'type' => 'new_bill',
            'time_of_creation' => time()
        ]);
        $notification->save();
        $clients = FirebaseDeviceBinding::findAll(['cottage_number' => $bill->cottageNumber]);
        if (!empty($clients)) {
            $server_key = Info::FIREBASE_SERVER_KEY;
            $client = new Client();
            $client->setApiKey($server_key);
            $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
            $message = new Message();
            $message->setPriority('high');
            foreach ($clients as $clientItem) {
                $message->addRecipient(new Device($clientItem->token));
            }
            $message->setData([
                    'notification' => 'new_bill',
                    'title' => $messageTitle,
                    'text' => $messageText,
                    'bill_id' => $bill->id,
                    'notification_id' => $notification->id
                ]
            );
            $this->sendMessage($client, $message, $clients);
        }
    }

    public function sendNewTransactionConfirmedNotification(TransactionsInterface $transaction): void
    {
        $messageTitle = 'Оплата зарегистрирована';
        $messageText = "Зарегистрирована оплата на сумму $transaction->transactionSumm руб. по счёту №$transaction->billId. Спасибо!";
        // add notification to table
        $notification = new Notifications([
            'cottage_id' => $transaction->cottageNumber,
            'title' => $messageTitle,
            'message' => $messageText,
            'type' => 'pay_confirm',
            'time_of_creation' => time()
        ]);
        $notification->save();
        $clients = FirebaseDeviceBinding::findAll(['cottage_number' => $transaction->cottageNumber]);
        if (!empty($clients)) {
            $server_key = Info::FIREBASE_SERVER_KEY;
            $client = new Client();
            $client->setApiKey($server_key);
            $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
            $message = new Message();
            $message->setPriority('high');
            foreach ($clients as $clientItem) {
                $message->addRecipient(new Device($clientItem->token));
            }
            $message->setData([
                    'notification' => 'transaction_confirm',
                    'title' => $messageTitle,
                    'text' => $messageText,
                    'bill_id' => $transaction->billId,
                    'notification_id' => $notification->id
                ]
            );
            $this->sendMessage($client, $message, $clients);
        }
    }

    private function clearInactiveContacts($result, $clients): void
    {
        $json = $result->getBody()->getContents();
        if (!empty($json)) {
            try {
                $encoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                $results = $encoded['results'];
                foreach ($results as $key => $resultItem) {
                    if (!empty($resultItem['error']) && $resultItem['error'] === 'NotRegistered') {
                        $target = $clients[$key];
                        if ($target !== null) {
                            Telegram::sendDebug('delete unregistered token');
                            $target->delete();
                        }
                    }
                }
            } catch (Exception) {
            }
        }
    }

    /**
     * @param \sngrl\PhpFirebaseCloudMessaging\Client $client
     * @param \sngrl\PhpFirebaseCloudMessaging\Message $message
     * @param array $clients
     */
    public function sendMessage(Client $client, Message $message, array $clients): void
    {
        $result = $client->send($message);
        $json = $result->getBody()->getContents();
        if (!empty($json)) {
            try {
                $encoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                $results = $encoded['results'];
                foreach ($results as $key => $resultItem) {
                    if (!empty($resultItem['error']) && $resultItem['error'] === 'NotRegistered') {
                        $target = $clients[$key];
                        if ($target !== null) {
                            $target->delete();
                        }
                    }
                }
            } catch (Throwable) {

            }
        }
    }

    public function notifyMonthPowerUseRegistered(Table_power_months $newData): void
    {
        $server_key = Info::FIREBASE_SERVER_KEY;
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
        $messageTitle = 'Месячное потребление электроэнергии';
        $messageText = sprintf(
            "Потребление электроэнергии за %s\nПотрачено за месяц: %s кВт\nСтоимость за месяц: %s р.\nСтарые показания: %s кВт\nНовые показания: %s кВт",
            TimeHandler::getFullFromShotMonth($newData->month),
            $newData->difference,
            CashHandler::toJsRubles($newData->totalPay),
            $newData->oldPowerData,
            $newData->newPowerData
        );
        // add notification to table
        $notification = new Notifications([
            'cottage_id' => $newData->cottageNumber,
            'title' => $messageTitle,
            'message' => $messageText,
            'type' => 'month_power_use',
            'time_of_creation' => time()
        ]);
        $notification->save();

        $clients = FirebaseDeviceBinding::findAll(['cottage_number' => $newData->cottageNumber]);
        if (!empty($clients)) {
            $message = new Message();
            $message->setPriority('high');
            foreach ($clients as $clientItem) {
                $message->addRecipient(new Device($clientItem->token));
            }
            // count spend by month
            $message->setData([
                'action' => 'monthPowerUseInfo',
                'spend' => $newData->difference,
                'monthCost' => $newData->totalPay,
                'month' => $newData->month,
                'previousData' => $newData->oldPowerData,
                'newData' => $newData->newPowerData,
                'notification_id' => $notification->id
            ]);
            $result = $client->send($message);
            $this->clearInactiveContacts($result, $clients);
        }
    }

    public function notifyNewTargetAccrual(CottageInterface $cottage, Accruals_target $accrual, Table_tariffs_target $newTariff): void
    {
        $server_key = Info::FIREBASE_SERVER_KEY;
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
        $messageTitle = 'Начисление целевого взноса';
        $messageText = sprintf(
            "Начислен новый целевой взнос за %s год.\nОплата с участка: %s р.\nОплата с сотки: %s р.\nВсего к оплате: %s р.\nНазначение платежа: %s",
            $accrual->year,
            $accrual->fixed_part,
            $accrual->square_part,
            $accrual->countAmount(),
            $newTariff->description
        );
        // add notification to table
        $notification = new Notifications([
            'cottage_id' => $cottage->getCottageNumber(),
            'title' => $messageTitle,
            'message' => $messageText,
            'type' => 'new_target_accrual',
            'time_of_creation' => time()
        ]);
        $notification->save();

        $clients = FirebaseDeviceBinding::findAll(['cottage_number' => $cottage->getCottageNumber()]);
        if (!empty($clients)) {
            $message = new Message();
            $message->setPriority('high');
            foreach ($clients as $clientItem) {
                $message->addRecipient(new Device($clientItem->token));
            }
            // count spend by month
            $message->setData([
                'action' => 'newTargetAccrual',
                'year' => $accrual->year,
                'fixed' => $accrual->fixed_part,
                'float' => $accrual->square_part,
                'total' => $accrual->countAmount(),
                'description' => $newTariff->description,
                'notification_id' => $notification->id
            ]);
            $result = $client->send($message);
            $this->clearInactiveContacts($result, $clients);
        }
    }
}