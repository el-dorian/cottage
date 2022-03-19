<?php


namespace app\models;


use app\models\Cottage;
use app\models\DOMHandler;
use app\models\ExceptionWithStatus;
use DateTime;
use DOMElement;
use Exception;
use yii\base\Model;
use function GuzzleHttp\Psr7\str;

class PowerCounters extends Model
{

    const SCENARIO_PARSE = 'parse';

    public $file;

    public function scenarios(): array
    {
        return [
            self::SCENARIO_PARSE => ['file'],
        ];
    }

    public function rules(): array
    {
        return [
            [['file'], 'file', 'skipOnEmpty' => false, 'maxFiles' => 1],
            [['file'], 'required', 'on' => self::SCENARIO_PARSE],
        ];
    }

    function mySort($a, $b)
    {
        if($a['cottage'] === $b['cottage']){
            return 0;
        }
        if($a['cottage'] > $b['cottage']){
            return 1;
        }
        return -1;
    }

    public function parseIndications(): string
    {
        $content = '<table class="table table-condensed table-striped sort-numerical"><thead><tr><th class="sort-numerical">Участок</th><th>Старые показания</th><th>Новые показания</th><th>Разница</th><th>Время снятия</th><th>Дата старых показаний</th></tr></thead>';
        $powerItems = [];
        if ($this->validate()) {
            $file = $this->file->tempName;
            $data = file_get_contents($file);
            $textBegin = stripos($data, "<tbody>");
            $clearData =  substr($data, $textBegin + 7, strlen($data) - 43 - $textBegin);
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<root>' . $clearData . '</root>';
            file_put_contents('power.xml', $xml);
            $loaded = file_get_contents('power.xml');
            $dom = new DOMHandler($loaded);
            // пройдусь по участкам и вынесу показания в таблицу
            $indications = $dom->query('/root/tr');
            $values = iterator_to_array($indications);
            /** @var DOMElement $value */
            foreach ($values as $value) {
                $childs = $value->getElementsByTagName("td");
                if($childs->length === 8){
                    $cottageNumber = (int)$childs->item(1)->textContent;
                    $data = (double) $childs->item(6)->textContent;
                    $time = $childs->item(7)->textContent;
                    $cottageInfo = Cottage::getCottageByLiteral($cottageNumber);
                    if($cottageInfo !== null){
                        $lastFilled = PowerHandler::getLastFilled($cottageInfo);
                        if($lastFilled !== null){
                            $lastData = $cottageInfo->currentPowerData;
                            if($data >= $lastData){
                                $difference = $data - $lastData;
                                $powerItems[(string)$cottageNumber] = ['cottage' => $cottageNumber,'oldData' => $lastData, 'newData' => $data, 'indicationTime' => $time, 'difference' => $difference, 'oldIndicationTime' => TimeHandler::getDatetimeFromTimestamp($lastFilled->searchTimestamp)];
                            }
                            else{
                                $powerItems[(string)$cottageNumber] = ['cottage' => $cottageNumber,'oldData' => $lastData, 'newData' => $data, 'indicationTime' => $time, 'difference' => '<b class="text-danger">Новые показания меньше старых!</b>', 'oldIndicationTime' => TimeHandler::getDatetimeFromTimestamp($lastFilled->searchTimestamp)];
                            }
                        }
                    }
                }
            }
            if(!empty($powerItems)){
                //usort($powerItems, array('app\models\PowerCounters','mySort'));
                foreach ($powerItems as $id => $powerItem) {
                    $content .= "<tr><td><a target='_blank' href='/show-cottage/$id'>$id</a></td><td>{$powerItem['oldData']}</td><td>{$powerItem['newData']}</td><td>{$powerItem['difference']}</td><td>{$powerItem['indicationTime']}</td><td>{$powerItem['oldIndicationTime']}</td></tr>";
                }
            }
            $content .= "</table>";
            return $content;
        }
            throw new ExceptionWithStatus('Не могу обработать файл, проверьте, что он правильный', 2);
    }
}