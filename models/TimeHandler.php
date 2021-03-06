<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 24.09.2018
 * Time: 11:54
 */

namespace app\models;

use DateTime;
use Exception;
use yii\base\InvalidArgumentException;
use yii\base\Model;

setlocale(LC_ALL,  'ru_RU.UTF-8', 'Rus');
date_default_timezone_set('Europe/Moscow');

class TimeHandler extends Model
{

    public static array $months = ['Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня', 'Июля', 'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря'];
    public static array $monthNames = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];


    /**
     * Возвращает дату и время из временной метки
     * @param int $timestamp
     * @return string
     */
    public static function timestampToDateTime(int $timestamp): string
    {
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $answer = '';
        $day = $date->format('d');
        $answer .= $day;
        $month = mb_strtolower(self::$months[$date->format('m') - 1]);
        $answer .= ' ' . $month . ' ';
        $answer .= $date->format('Y') . ' года.';
        $answer .= $date->format(' H:i:s');
        return $answer;
    }

    // ============================ ПЕРЕВОД МЕТКИ ВРЕМЕНИ В ДЕНЬ НЕДЕЛИ ==> "1539590835" <== "понедельник"  ================================================

    // ============================= ПОЛУЧЕНИЕ ТЕКУЩЕЙ ДАТЫ  <== "понедельник. Октябрь, 15, 2018"  ===================================================
    public static function getCurrentMonth()
    {
        return GrammarHandler::convertToUTF(strftime('%A. %B, %d, %Y ', time()));
    }

    public static function getCurrentDay()
    {
        return GrammarHandler::convertToUTF(strftime('%Y-%m-%d', strtotime(date('Y-m-d'))));
    }

    // ========================== ПРЕДЫДУЩИЙ МЕСЯЦ <== "Сентябрь 2018 года"  ==========================================================================
    public static function getPreviousMonth()
    {
        return GrammarHandler::convertToUTF(strftime('%B %Y', strtotime(date('Y-m') . ' -1 month')) . ' года');
    }

    // =========================== ДАТА И ВРЕМЯ ПО МЕТКЕ ВРЕМЕНИ  ==> "1539590835" <== "Октябрь, 15 2018 ; 11:07"   =============================================
    public static function getDatetimeFromTimestamp($timestamp): string
    {
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $answer = '';
        $day = $date->format('d');
        $answer .= $day;
        $month = mb_strtolower(self::$months[$date->format('m') - 1]);
        $answer .= ' ' . $month . ' ';
        $answer .= $date->format('Y') . ' года.';
        return $answer;
    }

    // =========================== ДАТА ПО МЕТКЕ ВРЕМЕНИ  ==> "1539590835" <== "Октябрь, 15 2018"   =============================================
    public static function getDateFromTimestamp($timestamp)
    {
        return GrammarHandler::convertToUTF(strftime('%d-%m-%Y', $timestamp));
    }

    // ========================== ПРЕДЫДУЩИЙ МЕСЯЦ <== "2018-09"   ======================
    public static function getTwoMonthAgo()
    {
        return GrammarHandler::convertToUTF(strftime('%Y-%m', strtotime(date('Y-m') . ' -2 month')));
    }

    // ========================== ПРЕДЫДУЩИЙ МЕСЯЦ <== "2018-09"   ======================
    public static function getPreviousShortMonth()
    {
        return GrammarHandler::convertToUTF(strftime('%Y-%m', strtotime(date('Y-m') . ' -1 month')));
    }

    // ========================== ТЕКУЩИЙ МЕСЯЦ  <== "2018-10"   ======================
    public static function getCurrentShortMonth()
    {
        return GrammarHandler::convertToUTF(strftime('%Y-%m', strtotime(date('Y-m'))));
    }

    // ========================== ТЕКУЩИЙ КВАРТАЛ <== "2018-4"   ======================
    public static function getCurrentQuarter()
    {
        $year = strftime('%Y', strtotime(date('Y-m')));
        $quarter = self::quarterFromMonth(strftime('%m', strtotime(date('Y-m'))));
        return GrammarHandler::convertToUTF("$year-$quarter");
    }
    // ====================== ПОЛНОЕ НАИМЕНОВАНИЕ КВАРТАЛА ИЗ УКОРОЧЕННОГО ==> "2018-4" <== "4 квартал 2018 года"   =================================
    public static function getFullFromShortQuarter($quarter): string
    {
        // считаю разницу между введённым значением и текущим кварталом
        $match = null;
        preg_match('/^(\d{4})\W*([1-4])$/', $quarter, $match);
        return "$match[2] квартал $match[1] года";
    }

    // ====================== ПОЛНОЕ НАИМЕНОВАНИЕ МЕСЯЦА ИЗ УКОРОЧЕННОГО ==> "2018-10" <== "Октябрь 2018"   =================================
    public static function getFullFromShotMonth($shortMonth)
    {
        return strftime('%B %Y', DateTime::createFromFormat('Y-m-d', $shortMonth . '-10')->getTimestamp());
    }

    // ========================== КВАРТАЛ ИЗ МЕСЯЦА ==> "10" <== "4"   ======================
    public static function quarterFromMonth($month)
    {
        switch ($month) {
            case 1:
            case 2:
            case 3:
                return 1;
            case 4:
            case 5:
            case 6:
                return 2;
            case 7:
            case 8:
            case 9:
                return 3;
            case 10:
            case 11:
            case 12:
                return 4;
        }
        return false;
    }


    // =========================================== ТЕКУЩИЙ ГОД <== "2018"   ==============================================

    /**
     * @return string
     */
    public static function getThisYear(): string
    {
        return date('Y');
    }

    // ======================== РАЗНИЦА МЕЖДУ ВВЕДЁННЫМ И ТЕКУЩИМ КВАРТАЛОМ ==>"2018-1" <== "3", ==>"2019-1" <== "-1"   ======================
    public static function checkQuarterDifference($s, $f = false)
    {
        // считаю разницу между введённым значением и текущим кварталом
        $start = self::isQuarter($s);
        if ($f) {
            $finish = self::isQuarter($f);
        } else {
            $finish = self::isQuarter(self::getCurrentQuarter());
        }
        if ($start['year'] === $finish['year']) {
            // если оплачен этот год- проверяю, если квартал меньше текущего- получаю разницу вычитанием
            return $start['quarter'] - $finish['quarter'];
        }
        // проверю, в какую сторону считать
        if ($start['full'] <= $finish['full']) {
            // если неоплачено за несколько лет- считаю разницу между годами. За все больше одного беру по 4 квартала, плюс кварталы в этом году, плюс кварталы в крайнем году неоплаты
            $difference = $start['year'] - $finish['year'];
            // возвращаю сумму кватралов в этом году и неоплаченных кварталов прошлого года
            return $start['quarter'] + (4 - $finish['quarter']) + (($difference - 1) * 4);
        }
        $difference = $start['year'] - $finish['year'];
        return ((4 - $finish['quarter']) + $start['quarter'] + (($difference - 1) * 4));
    }

    // ======================== РАЗНИЦА МЕЖДУ ВВЕДЁННЫМ И ТЕКУЩИМ МЕСЯЦЕМ ==>"2018-1" <== "3", ==>"2019-1" <== "-1"   ======================

    /**
     * @param $month string
     * @param bool $endMonth
     * @return int
     */
    public static function checkMonthDifference($month, $endMonth = false): int
    {
        // считаю разницу между введённым значением и текущим кварталом
        $info = self::isMonth($month);
        if ($endMonth) {
            $endMonthInfo = self::isMonth($endMonth);
        } else {
            $endMonthInfo = self::isMonth(self::getCurrentShortMonth());
        }
        if ($endMonthInfo['year'] === $info['year']) {
            // если оплачен этот год- проверяю, если месяц меньше текущего- получаю разницу вычитанием
            return $endMonthInfo['month'] - $info['month'];
        }
        // проверю, в какую сторону считать
        if ($info['full'] <= $endMonthInfo['full']) {
            // если неоплачено за несколько лет- считаю разницу между годами. За все больше одного беру по 4 квартала, плюс кварталы в этом году, плюс кварталы в крайнем году неоплаты
            $difference = $endMonthInfo['year'] - $info['year'];
            // возвращаю сумму кватралов в этом году и неоплаченных кварталов прошлого года
            return $endMonthInfo['month'] + (12 - $info['month']) + (($difference - 1) * 12);
        }
        $difference = $info['year'] - $endMonthInfo['year'];
        return -((12 - $endMonthInfo['month']) + $info['month'] + (($difference - 1) * 12));
    }

    // ======================== ПОЛУЧЕНИЕ МЕТКИ ВРЕМЕНИ НАЧАЛА КВАРТАЛА ==>"2018-4" <== "1538427600" ======================
    public static function getQuarterTimestamp($q)
    {
        // получу отметку времения 2 числа первого месяца данного года - второго, чтобы исключить поправку на часовой пояс
        $match = null;
        preg_match('/^(\d{4})\W*([1-4])$/', $q, $match);
        $quarter = $match[2] * 3 - 2;
        return strtotime("2-$quarter-$match[1]");
    }

    // ======================== ПОЛУЧЕНИЕ МЕТКИ ВРЕМЕНИ НАЧАЛА КВАРТАЛА ==>"2018-4" <== "1538427600" ======================
    public static function getMonthTimestamp($month)
    {
        // получу отметку времения 2 числа первого месяца данного года - второго, чтобы исключить поправку на часовой пояс
        $match = null;
        preg_match('/^(\d{4})\W*(\d{2})$/', $month, $match);
        return strtotime("2-$match[2]-$match[1]");
    }

    // ======================== ВЕРНУ СЛЕДУЮЩИЙ КВАРТАЛ ==>"2018-4" <== "2019-1" ======================
    public static function getNextQuarter($quarter): string
    {
        $match = null;
        preg_match('/^(\d{4})\W*([1-4])$/', $quarter, $match);
        if ($match[2] > 3) {
            return $match[1] + 1 . '-1';
        }
        return $match[1] . '-' . ($match[2] + 1);
    }

    /**
     * ВОЗВРАЩАЕТ МАССИВ СО СПИСКОМ КВАРТАЛОВ ОТ ТЕКУЩЕГО ДО ПЕРЕДАННОГО
     * @param $firstCountedQuarter <p>Квартал, от которого будет вестись отсчёт(исключая переданный)</p>
     * @return array <p>Массив кварталов в формате 2020-1 => ['quarterNumber' => 1, 'year' => 2020]</p>
     */
    public static function getQuarterList($firstCountedQuarter): array
    {
        if (is_string($firstCountedQuarter)) {
            $start = self::isQuarter($firstCountedQuarter);
            $end = self::isQuarter(self::getCurrentQuarter());
        } elseif (is_array($firstCountedQuarter)) {
            $start = self::isQuarter($firstCountedQuarter['start']);
            $end = self::isQuarter($firstCountedQuarter['finish']);
        } else {
            throw new InvalidArgumentException('Неверный параметр даты');
        }
        // составлю массив кварталов
        $no_payed = [];
        $count = self::checkQuarterDifference($start['full'], $end['full']);
        if ($count === 0) {
            return [];
        }
        if ($count > 0) {
            $quarter = $end['quarter'];
            $year = $end['year'];
        } else {
            $quarter = $start['quarter'];
            $year = $start['year'];
            $count = abs($count);
        }
        while ($count > 0) {
            if ($quarter === 4) {
                $quarter = 1;
                ++$year;
            } else {
                ++$quarter;
            }
            $no_payed[$year . '-' . $quarter] = ['quarterNumber' => $quarter, 'year' => $year];
            --$count;
        }
        return $no_payed;
    }

    /**
     * Получаю список месяцев между двумя датами
     * @param $month string
     * @param $endMonth string
     * @return array|null
     */
    public static function getMonthsList($month, $endMonth = ''): ?array
    {
        // составлю массив месяцев
        $no_payed = null;
        $count = self::checkMonthDifference($month, $endMonth);
        if ($count) {
            $month = self::isMonth($month)['full'];
            $match = null;
            preg_match('/^(\d{4})\W*(\d{2})$/', $month, $match);
            [, $year, $startMonth] = $match;
            if ($count > 0) {
                while ($count > 0) {
                    if ($startMonth === '12' || $startMonth === 12) {
                        $startMonth = '01';
                        ++$year;
                    } else {
                        ++$startMonth;
                        if ($startMonth < 10) {
                            $startMonth = '0' . $startMonth;
                        }
                    }
                    $no_payed[$year . '-' . $startMonth] = ['monthNumber' => $startMonth, 'year' => $year];
                    --$count;
                }
            } else if ($count < 0) {
                --$startMonth;
                while ($count < 0) {
                    if ($startMonth < 10) {
                        $startMonth = '0' . $startMonth;
                    }
                    $no_payed[$year . '-' . $startMonth] = ['monthNumber' => $startMonth, 'year' => $year];
                    if ($startMonth === '01' || $startMonth === 1 || $startMonth === '1') {
                        $startMonth = '12';
                        --$year;
                    } else {
                        --$startMonth;
                    }
                    ++$count;
                }
                $no_payed = array_reverse($no_payed);
            }
            return $no_payed;
        }
        return null;
    }

    // =============== ВОЗВРАЩАЕТ КВАРТАЛ СО СДВИГОМ ОТ ТЕКУЩЕГО ==>"5" <== "2019-4" ======================

    /**
     * @param $shift string|int
     * @param bool $start
     * @return string
     */
    public static function getQuarterShift($shift, $start = false): string
    {
        $shift = (int)$shift;
        if (is_int($shift)) {
            $match = null;
            if ($start) {
                $s = self::isQuarter($start);
            } else {
                $s = self::isQuarter(self::getCurrentQuarter());
            }

            if ($shift > 0) {
                while ($shift > 0) {
                    if ($s['quarter'] === 4) {
                        $s['quarter'] = 1;
                        ++$s['year'];
                    } else {
                        ++$s['quarter'];
                    }
                    --$shift;
                }
            } elseif ($shift < 0) {
                while ($shift < 0) {
                    if ($s['quarter'] === 1) {
                        $s['quarter'] = 4;
                        --$s['year'];
                    } else {
                        --$s['quarter'];
                    }
                    ++$shift;
                }
            }
            return "{$s['year']}-{$s['quarter']}";
        }
        throw new InvalidArgumentException("Значение сдвига \"$shift\" не является числом");
    }

    /**
     * @param $quarter string
     * @return array
     */
    public static function isQuarter($quarter): array
    {
        $match = null;
        if (preg_match('/^\s*(\d{4})\W*([1-4])\s*$/', $quarter, $match) && $match[1] > 0 && $match[2] < 5 && self::isYear($match[1])) {
            return ['full' => "$match[1]-$match[2]", 'year' => (int)$match[1], 'quarter' => (int)$match[2]];
        }
        throw new InvalidArgumentException("Значение \"$quarter\" не является кварталом");
    }

    /**
     * @param $month string
     * @return array
     */
    public static function isMonth($month): array
    {
        $match = null;
        if (preg_match('/^(\d{4})\W*([0-1]?\d)$/', $month, $match) && $match[2] > 0 && $match[2] < 13 && self::isYear($match[1])) {
            if ($match[2] < 10) {
                $match[2] = '0' . (int)$match[2];
            }
            return ['full' => "$match[1]-$match[2]", 'year' => $match[1], 'month' => $match[2]];
        }
        throw new InvalidArgumentException("Значение \"$month\" не является месяцем");
    }

    /**
     * @param $day string
     * @return array
     */
    public static function isDay($day): array
    {
        $match = null;
        if (preg_match('/^(\d{4})\W*([0-1]?\d)\W*([0-3]?\d)$/', $day, $match) && $match[2] > 0 && $match[2] < 13 && $match[3] > 0 && self::isYear($match[1])) {
            if ($match[2] < 10) {
                $match[2] = '0' . (int)$match[2];
            }
            $number = cal_days_in_month(CAL_GREGORIAN, $match[2], $match[1]);
            if ($match[3] <= $number) {
                if ($match[3] < 10) {
                    $match[3] = '0' . (int)$match[3];
                }
                return ['full' => "$match[1]-$match[2]-$match[3]", 'year' => $match[1], 'month' => $match[2], 'day' => $match[3]];
            }
        }
        throw new InvalidArgumentException("Значение \"$day\" не является днём года");
    }

    /**
     * @param $year string|int
     * @return int
     */
    public static function isYear($year): int
    {
        $year = (int)$year;
        if ($year > 1900 && $year < 3000) {
            return $year;
        }
        throw new InvalidArgumentException("Значение \"$year\" не является годом");
    }

    /**
     * @param $lastMonth string
     * @return string
     */
    public static function getNextMonth($lastMonth): string
    {
        $info = self::isMonth($lastMonth);
        if ($info['month'] === 12) {
            $info['month'] = '01';
            ++$info['year'];
        } else {
            ++$info['month'];
            if ($info['month'] < 10) {
                $info['month'] = '0' . $info['month'];
            }
        }
        return "{$info['year']}-{$info['month']}";
    }

    /**
     * @param $nextMonth string
     * @return bool|string
     */
    public static function getPrevMonth($nextMonth)
    {
        $info = self::isMonth($nextMonth);
        if ($info['month'] === '01') {
            $info['month'] = 12;
            --$info['year'];
        } else {
            --$info['month'];
            if ($info['month'] < 10) {
                $info['month'] = '0' . $info['month'];
            }
        }
        return "{$info['year']}-{$info['month']}";
    }

    /**
     * @param $month string
     * @return array
     */
    public static function getMonthStartAndFinish($month): array
    {
        $info = self::isMonth($month);
        $result = [];
        // получу дату начала месяца
        $result['start'] = strtotime("1-{$info['month']}-{$info['year']}");
        $result['end'] = strtotime("1-{$info['month']}-{$info['year']} + 1 month") - 1;
        return $result;
    }

    /**
     * @param $day string
     * @return array
     */
    public static function getDayStartAndFinish($day): array
    {
        $info = self::isDay($day);
        $result = [];
        // получу дату начала месяца
        $result['start'] = strtotime("{$info['day']}-{$info['month']}-{$info['year']}");
        $result['end'] = strtotime("{$info['day']}-{$info['month']}-{$info['year']} + 1 day") - 1;
        return $result;
    }

    /**
     * @param $year int|string
     * @return array
     */
    public static function getYearStartAndFinish($year): array
    {
        self::isYear($year);
        $result = [];
        // получу дату начала месяца
        $result['start'] = strtotime("1-1-$year");
        $result['end'] = strtotime("1-1-{$year} + 1 year") - 1;
        return $result;
    }

    public static function getTimestampFromBank(string $pay_date, string $pay_time): int
    {
        $date = DateTime::createFromFormat('j-m-Y H-i-s', "$pay_date $pay_time");
        return $date->getTimestamp();
    }

    public static function getCustomTimestamp(string $pay_date, string $payTime = null): int
    {
        $dates = explode('-', $pay_date);
        $date = new DateTime();
        if (!empty($payTime)) {
            $times = explode('-', $payTime);
            $date->setDate($dates[2], $dates[1], $dates[0]);
            $date->setTime($times[0], $times[1], $times[2]);
            return $date->getTimestamp();
        }
        $date->setDate($dates[2], $dates[1], $dates[0]);
        $timestamp = $date->getTimestamp();
        if ($timestamp > 0) {
            return $timestamp;
        }
        $date->setDate($dates[0], $dates[1], $dates[2]);
        return $date->getTimestamp();
    }

    public static function getPowerDueDate(): string
    {
        $search = ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
        $replace = ['Января', 'Февраля', 'Мара', 'Апреля', 'Мая', 'Июня', 'Июля', 'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря'];

        $now = new DateTime();
        $dayOfMonth = $now->format("d");
        if ((int)$dayOfMonth > 10) {
            $date = self::getFullFromShotMonth(self::getNextMonth(self::getCurrentShortMonth()));
        } else {
            $date = self::getFullFromShotMonth(self::getCurrentShortMonth());
        }
        $neededDate = str_replace($search, $replace, $date);
        return "10 " . mb_strtolower($neededDate);
    }

    public static function checkOverdueQuarter($quarter): bool
    {
        // получу первый месяц квартала
        $explodedQuarter = explode('-', $quarter);
        $month = $explodedQuarter[1] * 3 - 2;
        $date = DateTime::createFromFormat('j-m-Y H-i-s', "1-{$month}-{$explodedQuarter[0]} 12-00-00");
        $date->modify('+1 month');
        $date->modify('-1 day');
        $timestamp = $date->getTimestamp();
        $nowTimestamp = self::getCurrentTimestamp();
        return $timestamp < $nowTimestamp;
    }

    public static function getPayUpQuarter($quarter)
    {
        // получу первый месяц квартала
        $explodedQuarter = explode('-', $quarter);
        $month = $explodedQuarter[1] * 3 - 2;
        $date = DateTime::createFromFormat('j-m-Y H-i-s', "1-{$month}-{$explodedQuarter[0]} 12-00-00");
        $date->modify('+1 month');
        $date->modify('-1 day');
        $timestamp = $date->getTimestamp();
        return self::getDatetimeFromTimestamp($timestamp);
    }

    public static function getPayUpQuarterTimestamp($quarter): int
    {
        // получу первый месяц квартала
        $explodedQuarter = explode('-', $quarter);
        $month = $explodedQuarter[1] * 3 - 2;
        $date = DateTime::createFromFormat('j-m-Y H-i-s', "1-{$month}-{$explodedQuarter[0]} 12-00-00");
        $date->modify('+1 month');
        $date->modify('-1 day');
        return $date->getTimestamp();
    }

    private static function getCurrentTimestamp()
    {
        return time();
    }

    public static function getPayUpMonth($month): int
    {
        $date = DateTime::createFromFormat('Y-m-j H-i-s', "{$month}-10 12-00-00");
        $date->modify('+1 month');
        return $date->getTimestamp();
    }

    /**
     * Считаю количество дней, прошедших между двумя датами
     * @param int $firstDate дата для отсчёта
     * @param int|null $secondDate вторая дата для отсчёта, если назначена, период считается как разница
     * между первой и второй датами, иначе: как разница между первой датой и текущим днём
     * @return int
     * @throws Exception
     */
    public static function checkDayDifference(int $firstDate, int $secondDate = null): int
    {
        // если часы не равны
        // получу дату из временной метки
        $date = new DateTime();
        $date->setTimestamp($firstDate);
        $date->setTime(23,59,59);
        $nowDatetime = new DateTime();
        if($secondDate !== null)
            $nowDatetime->setTimestamp($secondDate);
        $interval = $date->diff($nowDatetime);
        $diff = $interval->format('%R%a');
        if ($diff > 0) {
            return $diff;
        }
        return 0;
    }

    public static function getPrevQuarter(string $quarter): string
    {
        $info = self::isQuarter($quarter);
        if ($info['quarter'] === 1) {
            $info['quarter'] = 4;
            --$info['year'];
        } else {
            --$info['quarter'];
        }
        return "{$info['year']}-{$info['quarter']}";
    }

    public static function dateInputDateFromTimestamp(int $timestamp)
    {
        return GrammarHandler::convertToUTF(strftime('%Y-%m-%d', $timestamp));
    }

    public static function quarterFromYearMonth(string $yearMonth): string
    {
        // получу год и месяц
        $date = explode('-', $yearMonth);
        return $date[0] . '-' . self::quarterFromMonth($date[1]);
    }

    public static function getYearFromTimestamp(string $timestamp)
    {
        return GrammarHandler::convertToUTF(strftime('%Y', $timestamp));
    }

    public static function getShortMonthFromTimestamp($timestamp)
    {
        return GrammarHandler::convertToUTF(strftime('%Y-%m', $timestamp));
    }

    public static function getQuartersList(string $firstQuarter, string $lastQuarter): array
    {
        $start = self::isQuarter($firstQuarter);
        $finish = self::isQuarter($lastQuarter);
        $current = $start['full'];
        $list = [];
        do {
            $list[] = $current;
            $current = self::getNextQuarter($current);
        } while ($current <= $finish['full']);
        return $list;
    }

    public static function getYearsList(int $firstYear, string $lastYear): array
    {
        $list[] = $firstYear;
        if ($firstYear !== $lastYear) {
            $current = ++$firstYear;
            while ($current <= $lastYear) {
                $list[] = $current;
                ++$current;
            }
        }
        return $list;
    }

    public static function isLastDay(): bool
    {
        // нас интересует первый месяц квартала
        $date = new DateTime();
        $month = (int)$date->format('m');
        if($month % 3 === 1){
            $currentDay = (int)$date->format('d');
            $date->modify('last day of this month');
            $lastDay = (int)$date->format('d');
            if($currentDay === $lastDay || $currentDay + 1 === $lastDay){
                // сегодня день х
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $date
     * @return string
     * @throws Exception
     */
    public static function getQuarterFromTimestamp(int $date): string
    {
        $changeDate = new DateTime();
        $changeDate->setTimestamp($date);
        $month = $changeDate->format('m');
        return $changeDate->format('Y') . '-' . self::quarterFromMonth($month);
    }

    public static function explodeIntervalByDays(array $interval): array
    {
        // получу начало и конец дня, в который входит данный интервал
        $firstDay = self::dateInputDateFromTimestamp($interval['start']);
        $lastDay = self::dateInputDateFromTimestamp($interval['finish']);
        $startDayInfo = self::getDayStartAndFinish($firstDay);
        if($firstDay === $lastDay){
            return [$firstDay => $startDayInfo];
        }
        // составлю список из всех дней, которые входят в период
        $days = [];
        $days[] = $firstDay;
        $d = $firstDay;
        while (true){
            $nextDay = self::getNextDay($d);
            $days[] = $nextDay;
            if($nextDay !== $lastDay){
                $d = $nextDay;
            }
            else{
                break;
            }
        }
        $answer = [];
        if(!empty($days)){
            foreach ($days as $day) {
                $answer[$day] = self::getDayStartAndFinish($day);
            }
        }
        return $answer;
    }

    /**
     * @param string $day
     * @return string
     */
    private static function getNextDay(string $day): string
    {
        $info = self::isDay($day);
        $nextDayTimestamp = strtotime("{$info['day']}-{$info['month']}-{$info['year']}") + 60*60*24;
        return self::dateInputDateFromTimestamp($nextDayTimestamp);
    }

}