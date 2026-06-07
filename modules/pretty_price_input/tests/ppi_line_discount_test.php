<?php

require_once __DIR__ . '/../helpers/ppi_line_discount_helper.php';

function assert_same_float($expected, $actual, $message)
{
    if (abs($expected - $actual) > 0.0001) {
        fwrite(STDERR, $message . ' Expected ' . $expected . ', got ' . $actual . PHP_EOL);
        exit(1);
    }
}

$line = ppi_calculate_line_discount([
    'qty'  => 1,
    'rate' => 1500000,
], [
    'type'     => 'percent',
    'percent'  => 100,
    'amount'   => 0,
    'tax_mode' => 'before_tax',
]);

assert_same_float(1500000, $line['gross_subtotal'], 'Gross subtotal mismatch.');
assert_same_float(1500000, $line['before_tax_discount'], 'Before-tax discount mismatch.');
assert_same_float(0, $line['net_subtotal'], 'Net subtotal mismatch.');

$line = ppi_calculate_line_discount([
    'qty'  => 12,
    'rate' => 80000,
], [
    'type'     => 'amount',
    'percent'  => 0,
    'amount'   => 100000,
    'tax_mode' => 'before_tax',
]);

assert_same_float(960000, $line['gross_subtotal'], 'Second gross subtotal mismatch.');
assert_same_float(100000, $line['before_tax_discount'], 'Fixed discount mismatch.');
assert_same_float(860000, $line['net_subtotal'], 'Second net subtotal mismatch.');

echo 'ppi_line_discount_test passed' . PHP_EOL;
