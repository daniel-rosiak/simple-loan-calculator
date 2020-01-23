<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Managarm\App\Model\LoanCalculator;
use Managarm\App\Model\LoanCalculatorInterface;

final class LoanCalculatorTest extends TestCase
{

    public function testConstruct(): void
    {
        $this->assertInstanceOf(
            LoanCalculatorInterface::class,
            new LoanCalculator()
        );
    }

    public function testCanTakeLoan(): void
    {
        $loanCalculator = new LoanCalculator();
        $loan = $loanCalculator->takeLoan(1000.00, 500.00);

        $this->assertInternalType('array', $loan);
        $this->assertEquals(2, count($loan));
        $this->assertEquals(
            [
                [
                    'day' => 0,
                    'amount' => 1000,
                    'type' => 'loan'
                ],
                [
                    'day' => 0,
                    'amount' => 500,
                    'type' => 'commision'
                ]
            ],
            $loan
        );
    }

    public function testCannotTakeLoanOnInvalidDay(): void
    {
        $this->expectException(\Exception::class);

        $loanCalculator = new LoanCalculator();
        $loanCalculator->takeLoan(1000.00, 500.00, -1);
    }

    public function testCanAddCredit(): void
    {
        $loanCalculator = new LoanCalculator();
        $credit = $loanCalculator->addCredit(1000.00, 0);

        $this->assertInternalType('array', $credit);
        $this->assertEquals(
            [
                'day' => 0,
                'amount' => 1000,
                'type' => 'credit'
            ],
            $credit
        );
    }

    public function testCannotAddCreditOnInvalidDay(): void
    {
        $this->expectException(\Exception::class);

        $loanCalculator = new LoanCalculator();
        $loanCalculator->addCredit(1000.00, -3);
    }

    public function testInitBalance(): void
    {
        $loanCalculator = new LoanCalculator();
        $balance = $loanCalculator->checkBalance(0);

        $this->assertEquals(
            0,
            $balance
        );
    }

    public function testInitBalanceAfterFewDays(): void
    {
        $loanCalculator = new LoanCalculator();
        $balance = $loanCalculator->checkBalance(3);

        $this->assertEquals(
            0,
            $balance
        );
    }

    public function testInitLoanHistory(): void
    {
        $loanCalculator = new LoanCalculator();
        $history = $loanCalculator->getLoanHistory();

        $this->assertInternalType('array', $history);
        $this->assertEquals(1, count($history));
        $this->assertEquals(
            [
                [
                    'day' => 0,
                    'amount' => 0,
                    'type' => 'init'
                ]
            ],
            $history
        );
    }

    public function testBalanceNextDayAfterLoan(): void
    {
        $loanCalculator = new LoanCalculator(0.45);
        $loanCalculator->takeLoan(1000.00, 500.00);
        $balance = $loanCalculator->checkBalance(1);

        $this->assertEquals(
            (1000 + 500 + (0.45 * 1)),
            $balance
        );
    }

    public function testBalanceFewDaysAfterLoan(): void
    {
        $loanCalculator = new LoanCalculator(0.45);
        $loanCalculator->takeLoan(1000.00, 500.00, 3);
        $balance = $loanCalculator->checkBalance(6);

        $this->assertEquals(
            (1000 + 500 + (0.45 * 3)),
            $balance
        );
    }

    public function testBalanceAfterLoanPartiallyPaidDayAfter(): void
    {
        $loanCalculator = new LoanCalculator(0.45);
        $loanCalculator->takeLoan(1000.00, 500.00);
        $loanCalculator->addCredit(200.00, 1);
        $balance = $loanCalculator->checkBalance(1);

        $this->assertEquals(
            (1000 + 500 + (0.45 * 1)) - 200,
            $balance
        );
    }

    public function testBalanceAfterLoanIsFullyPaid(): void
    {
        $loanCalculator = new LoanCalculator(0.45);
        $loanCalculator->takeLoan(1000.00, 500.00,1);
        $loanCalculator->addCredit(1500.45, 2);
        $balance = $loanCalculator->checkBalance(4);

        $this->assertEquals(
            (1000 + 500 + (0.45 * 1)) - 1500.45,
            $balance
        );
    }

    public function testBalanceNextDayAfterLoanAndCreditWronglySetted(): void
    {
        $loanCalculator = new LoanCalculator(0.45);
        $loanCalculator->takeLoan(1000.00, 500.00, 2);
        $loanCalculator->addCredit(200.00, 1);
        $balance = $loanCalculator->checkBalance(3);

        $this->assertEquals(
            (1000 + 500 + (0.45 * 1)) - 200,
            $balance
        );
    }

    public function testLoanHistoryAfterLoan(): void
    {
        $loanCalculator = new LoanCalculator(0.45);
        $loanCalculator->takeLoan(400.00, 20.00);
        $history = $loanCalculator->getLoanHistory();

        $this->assertInternalType('array', $history);
        $this->assertEquals(3, count($history));
        $this->assertEquals(
            [
                [
                    'day' => 0,
                    'amount' => 0,
                    'type' => 'init'
                ],
                [
                    'day' => 0,
                    'amount' => 400,
                    'type' => 'loan'
                ],
                [
                    'day' => 0,
                    'amount' => 20,
                    'type' => 'commision'
                ]
            ],
            $history
        );
    }

    public function testLoanHistoryAfterLoanPartiallyPaidDayAfter(): void
    {
        $loanCalculator = new LoanCalculator(0.45);
        $loanCalculator->takeLoan(400.00, 20.00);
        $loanCalculator->addCredit(200.00, 1);
        $history = $loanCalculator->getLoanHistory();

        $this->assertInternalType('array', $history);
        $this->assertEquals(8, count($history));
        $this->assertEquals(
            [
                [
                    'day' => 0,
                    'amount' => 0,
                    'type' => 'init'
                ],
                [
                    'day' => 0,
                    'amount' => 400,
                    'type' => 'loan'
                ],
                [
                    'day' => 0,
                    'amount' => 20,
                    'type' => 'commision'
                ],
                [
                    'day' => 1,
                    'amount' => 0.45,
                    'type' => 'interest'
                ],
                [
                    'day' => 1,
                    'amount' => 200,
                    'type' => 'credit'
                ],
                [
                    'day' => 1,
                    'amount' => 0.45,
                    'type' => 'settlement',
                    'settledElement' => 3
                ],
                [
                    'day' => 1,
                    'amount' => 20,
                    'type' => 'settlement',
                    'settledElement' => 2
                ],
                [
                    'day' => 1,
                    'amount' => 179.55,
                    'type' => 'settlement',
                    'settledElement' => 1
                ]
            ],
            $history
        );
    }

    public function testLoanHistoryAfterLoanIsFullyPaidTwoDaysAfter(): void
    {
        $loanCalculator = new LoanCalculator(0.30);
        $loanCalculator->takeLoan(400.00, 20.00);
        $loanCalculator->addCredit(420.60, 2);
        $history = $loanCalculator->getLoanHistory();

        $this->assertInternalType('array', $history);
        $this->assertEquals(10, count($history));
        $this->assertEquals(
            [
                [
                    'day' => 0,
                    'amount' => 0,
                    'type' => 'init'
                ],
                [
                    'day' => 0,
                    'amount' => 400,
                    'type' => 'loan'
                ],
                [
                    'day' => 0,
                    'amount' => 20,
                    'type' => 'commision'
                ],
                [
                    'day' => 1,
                    'amount' => 0.30,
                    'type' => 'interest'
                ],
                [
                    'day' => 2,
                    'amount' => 0.30,
                    'type' => 'interest'
                ],
                [
                    'day' => 2,
                    'amount' => 420.60,
                    'type' => 'credit'
                ],
                [
                    'day' => 2,
                    'amount' => 0.30,
                    'type' => 'settlement',
                    'settledElement' => 3
                ],
                [
                    'day' => 2,
                    'amount' => 0.30,
                    'type' => 'settlement',
                    'settledElement' => 4
                ],
                [
                    'day' => 2,
                    'amount' => 20,
                    'type' => 'settlement',
                    'settledElement' => 2
                ],
                [
                    'day' => 2,
                    'amount' => 400,
                    'type' => 'settlement',
                    'settledElement' => 1
                ]
            ],
            $history
        );
    }
}
