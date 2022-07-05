<?php

namespace app\models\mass_bill;

class MassBillEntity
{
    public string $type;
    public string $period;
    public string $sum;
    public string $cottage;

    /**
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->type = $params['type'];
        $this->period = $params['period'];
        $this->sum = $params['sum'];
        $this->cottage = $params['cottage'];
    }
}