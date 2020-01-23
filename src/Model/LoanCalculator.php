<?php
declare(strict_types = 1);

namespace Managarm\App\Model;

use Managarm\App\Model\LoanCalculatorInterface;

/**
 * Class LoanCalculator
 * @package Managarm\App\Model
 */
class LoanCalculator implements LoanCalculatorInterface
{

    const DEBIT_LOAN = 'loan';
    const DEBIT_COMMISION = 'commision';
    const DEBIT_INTREST = 'interest';

    const CREDIT = 'credit';

    const SETTLEMENT = 'settlement';

    /**
     * @var float
     */
    private $interest;

    /**
     * //@TODO: history should be in other class + should have had an repository
     * @var array
     */
    private $history;

    /**
     * @var array
     */
    private static $debits = [
        self::DEBIT_INTREST,
        self::DEBIT_COMMISION,
        self::DEBIT_LOAN
    ];

    /**
     * @var array
     */
    private static $credits = [
        self::CREDIT
    ];


    /**
     * LoanCalculator constructor.
     * @param float $interest
     */
    public function __construct(float $interest = 0.45)
    {
        $this->history = [];
        $this->interest = $interest;

        $this->addToHistory(0, 'init', 0);
    }

    /**
     * @param int $day
     * @return int
     * @throws \Exception
     */
    public function checkBalance(int $day = 0): float
    {
        $this->checkDay($day);
        $this->calculateIntrestsTillDay($day);

        return $this->calculateBalanceOnDay($day);
    }

    /**
     * @param float $loan
     * @param float $commision
     * @param int $day
     * @return array
     * @throws \Exception
     */
    public function takeLoan(float $loan, float $commision, int $day = 0): array
    {
        $this->checkDay($day);
        $this->calculateIntrestsTillDay($day);

        $historyElements = [];
        $historyElements[] = $this->addToHistory($loan, self::DEBIT_LOAN, $day);
        $historyElements[] = $this->addToHistory($commision, self::DEBIT_COMMISION, $day);

        return $historyElements;
    }

    /**
     * @param float $amount
     * @param int $day
     * @return array
     * @throws \Exception
     */
    public function addCredit(float $amount, int $day): array
    {
        $this->checkDay($day);
        $this->calculateIntrestsTillDay($day);
        $credit = $this->addToHistory($amount, self::CREDIT, $day);
        $this->processSettlements($amount, $day);

        return $credit;
    }

    /**
     * @return array
     */
    public function getLoanHistory(): array
    {
        return $this->history;
    }

    /**
     * @param float $amount
     * @param string $type
     * @param int $day
     * @param int $settledElement
     * @return array
     */
    protected function addToHistory(float $amount, string $type, int $day, int $settledElement = 0): array
    {
        //@TODO History element should be an object
        $historyElement = [
            'day' => $day,
            'amount' => $amount,
            'type' => $type,
        ];

        if($settledElement > 0) {
            $historyElement['settledElement'] = $settledElement;
        }

        $this->history[] = $historyElement;
        return $historyElement;
    }

    /**
     * @param int $day
     * @throws \Exception
     */
    private function calculateIntrestsTillDay(int $day): void
    {
        $startElement = $this->findLastElementInHistoryByType(self::DEBIT_INTREST);
        if (!$startElement) {
            $startElement = $this->findLastElementInHistoryByType(self::DEBIT_LOAN);
        }
        if(!isset($startElement)) {
            throw new \Exception('Logic exception');
        }

        if (($day - $startElement['day']) > 0) {
            for ($i = $startElement['day'] + 1; $i <= $day; $i++) {
                if($this->calculateBalanceOnDay($i) > 0) {
                    $this->addToHistory($this->interest, self::DEBIT_INTREST, $i);
                }
            }
        }
    }

    /**
     * @param string $type
     * @return mixed
     */
    private function findLastElementInHistoryByType(string $type)
    {
        foreach (array_reverse($this->history) as $history) {
            if ($history['type'] == $type) {
                return $history;
            }
        }
        return false;
    }

    /**
     * @param $a
     * @param $b
     * @return int
     */
    private function orderDebits($a, $b): int
    {
        if ($a['type'] == $b['type']) return 0;
        if ($a['type'] == self::DEBIT_INTREST && $b['type'] == self::DEBIT_INTREST) return 0;
        if ($a['type'] == self::DEBIT_INTREST && $b['type'] == self::DEBIT_COMMISION) return -1;
        if ($a['type'] == self::DEBIT_INTREST && $b['type'] == self::DEBIT_LOAN) return -1;
        if ($a['type'] == self::DEBIT_COMMISION && $b['type'] == self::DEBIT_LOAN) return -1;
        return 1;
    }

    /**
     * @param float $amount
     * @param int $day
     * @return array
     */
    private function processSettlements(float $amount, int $day): array
    {
        $elementsToSettle = $this->findHistoryElementsToSettle();

        $historyElements = [];
        foreach ($elementsToSettle as $index => $element) {
            if ($amount > 0) {
                $amount = $amount - $element['amount'];
                $historyElements[] = $this->addToHistory($amount > 0 ? $element['amount'] : ($element['amount'] + $amount), self::SETTLEMENT, $day, $index);
            }
        }

        return $historyElements;
    }

    /**
     * @param array $settledData
     * @param int $elementIndex
     * @return int
     */
    private function calculateAlreadySettledAmount(array $settledData, int $elementIndex): float
    {
        $amount = 0;
        foreach ($settledData as $historyElement) {
            if ($historyElement['settledElement'] == $elementIndex) {
                $amount += $historyElement['amount'];
            }
        }

        return $amount;
    }

    /**
     * @param int $day
     * @return int
     */
    private function calculateBalanceOnDay(int $day): float
    {
        $balance = 0;
        foreach ($this->history as $historyElement) {
            if ($historyElement['day'] > $day) {
                return $balance;
            }
            if (in_array($historyElement['type'], self::$credits)) {
                $balance -= $historyElement['amount'];
            } else if (in_array($historyElement['type'], self::$debits)) {
                $balance += $historyElement['amount'];
            }
        }
        return $balance;
    }

    /**
     * @return array
     */
    private function findHistoryElementsToSettle(): array
    {
        $elementsSettled = [];
        foreach ($this->history as $historyElement) {
            if ($historyElement['type'] == self::SETTLEMENT) {
                $elementsSettled[$historyElement['settledElement']] = $historyElement;
            }
        }

        $elementsToSettle = [];
        foreach ($this->history as $index => $historyElement) {
            if (in_array($historyElement['type'], self::$debits)) {

                if (isset($elementsSettled[$index])) {
                    $amountSettled = $this->calculateAlreadySettledAmount($elementsSettled, $index);
                    if ($amountSettled != $historyElement['amount']) {
                        $elementsToSettle[$index] = $historyElement;
                        $elementsToSettle[$index]['amount'] = $historyElement['amount'] - $amountSettled;
                    }
                } else {
                    $elementsToSettle[$index] = $historyElement;
                }
            }
        }

        uasort($elementsToSettle, array($this, 'orderDebits'));

        return $elementsToSettle;
    }

    /**
     * @param int $day
     * @throws \Exception
     */
    private function checkDay(int $day): void
    {
        if($day < 0) {
            throw new \Exception('Logic exception');
        }
    }

}
