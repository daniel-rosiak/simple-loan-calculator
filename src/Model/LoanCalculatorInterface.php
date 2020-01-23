<?php
declare(strict_types=1);

namespace Managarm\App\Model;

/**
 * Interface LoanCalculatorInterface
 * @package Managarm\App\Model
 */
interface LoanCalculatorInterface
{

    /**
     * @param int $day
     * @return float
     */
    public function checkBalance(int $day): float;

    /**
     * @param float $loan
     * @param float $commision
     * @param int $day
     * @return array
     */
    public function takeLoan(float $loan, float $commision, int $day): array;

    /**
     * @param float $credit
     * @param int $day
     * @return array
     */
    public function addCredit(float $credit, int $day): array;

    /**
     * @return array
     */
    public function getLoanHistory(): array;

}
