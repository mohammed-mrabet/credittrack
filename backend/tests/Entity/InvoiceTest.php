<?php

namespace App\Tests\Entity;

use App\Entity\Client;
use App\Entity\Invoice;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client('Atlas Distribution SARL');
    }

    private function invoice(string $issue = '2026-10-01', string $due = '2026-10-31', string $amount = '1000'): Invoice
    {
        return new Invoice('FAC-2026-0001', $this->client, $amount, new \DateTimeImmutable($issue), new \DateTimeImmutable($due));
    }

    public function testUnpaidBeforeDueDate(): void
    {
        $invoice = $this->invoice();

        self::assertSame(Invoice::STATUS_UNPAID, $invoice->getStatus(new \DateTimeImmutable('2026-10-15')));
        self::assertSame(0, $invoice->getDaysOverdue(new \DateTimeImmutable('2026-10-15')));
    }

    public function testDueDateItselfIsNotOverdue(): void
    {
        $invoice = $this->invoice();

        self::assertSame(Invoice::STATUS_UNPAID, $invoice->getStatus(new \DateTimeImmutable('2026-10-31 18:30')));
    }

    public function testOverdueAfterDueDate(): void
    {
        $invoice = $this->invoice();
        $today = new \DateTimeImmutable('2026-11-10');

        self::assertSame(Invoice::STATUS_OVERDUE, $invoice->getStatus($today));
        self::assertSame(10, $invoice->getDaysOverdue($today));
    }

    public function testPaidInvoiceIsNeverOverdue(): void
    {
        $invoice = $this->invoice();
        $invoice->markAsPaid(new \DateTimeImmutable('2026-11-20'));

        self::assertSame(Invoice::STATUS_PAID, $invoice->getStatus(new \DateTimeImmutable('2026-12-31')));
        self::assertSame(0, $invoice->getDaysOverdue(new \DateTimeImmutable('2026-12-31')));
    }

    public function testCannotPayTwice(): void
    {
        $invoice = $this->invoice();
        $invoice->markAsPaid();

        $this->expectException(\LogicException::class);
        $invoice->markAsPaid();
    }

    public function testAmountIsNormalizedToTwoDecimals(): void
    {
        self::assertSame('1500.50', $this->invoice(amount: '1500.5')->getAmount());
    }

    public function testRejectsNegativeAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->invoice(amount: '-10');
    }

    public function testRejectsDueDateBeforeIssueDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->invoice(issue: '2026-10-31', due: '2026-10-01');
    }
}
