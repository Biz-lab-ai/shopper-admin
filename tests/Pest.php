<?php

declare(strict_types=1);

use Shopper\Tests\StockTestCase;
use Shopper\Tests\TestCase;

pest()->extend(TestCase::class)->in('CustomerModelKeyTest.php', 'CustomerRoleListenerTest.php');
pest()->extend(StockTestCase::class)->in('Stock');

function recordingStockRunner(): Shopper\Tests\Stubs\RecordingStockTransactionRunner
{
    $runner = new Shopper\Tests\Stubs\RecordingStockTransactionRunner;

    app()->instance(Shopper\Core\Contracts\StockTransactionRunner::class, $runner);

    return $runner;
}
