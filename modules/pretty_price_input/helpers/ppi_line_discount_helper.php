<?php

if (! function_exists('ppi_decimal_places')) {
    function ppi_decimal_places()
    {
        return function_exists('get_decimal_places') ? get_decimal_places() : 2;
    }
}

if (! function_exists('ppi_round_money')) {
    function ppi_round_money($value)
    {
        return round((float) $value, ppi_decimal_places());
    }
}

if (! function_exists('ppi_normalize_line_discount')) {
    function ppi_normalize_line_discount($discount)
    {
        $type = isset($discount['type']) ? $discount['type'] : 'percent';
        if ($type !== 'percent' && $type !== 'amount') {
            $type = 'percent';
        }

        $taxMode = isset($discount['tax_mode']) ? $discount['tax_mode'] : 'before_tax';
        if ($taxMode !== 'before_tax' && $taxMode !== 'after_tax') {
            $taxMode = 'before_tax';
        }

        $percent = isset($discount['percent']) ? (float) $discount['percent'] : 0.0;
        $amount  = isset($discount['amount']) ? (float) $discount['amount'] : 0.0;

        if ($percent < 0) {
            $percent = 0;
        } elseif ($percent > 100) {
            $percent = 100;
        }

        if ($amount < 0) {
            $amount = 0;
        }

        return [
            'type'     => $type,
            'percent'  => $percent,
            'amount'   => $amount,
            'tax_mode' => $taxMode,
        ];
    }
}

if (! function_exists('ppi_calculate_line_discount')) {
    function ppi_calculate_line_discount($item, $discount = null)
    {
        $discount = ppi_normalize_line_discount($discount ?: []);

        $quantity      = isset($item['qty']) && $item['qty'] !== '' ? (float) $item['qty'] : 1.0;
        $rate          = isset($item['rate']) && $item['rate'] !== '' ? (float) $item['rate'] : 0.0;
        $grossSubtotal = $quantity * $rate;
        $taxesTotal    = 0.0;

        if (! empty($item['taxes']) && is_array($item['taxes'])) {
            foreach ($item['taxes'] as $tax) {
                $taxRate = isset($tax['taxrate']) ? (float) $tax['taxrate'] : 0.0;
                $taxesTotal += ($grossSubtotal / 100) * $taxRate;
            }
        }

        $beforeTaxDiscount = 0.0;
        $afterTaxDiscount  = 0.0;
        $base              = $discount['tax_mode'] === 'after_tax' ? $grossSubtotal + $taxesTotal : $grossSubtotal;

        if ($discount['type'] === 'percent') {
            $discountValue = $base * ($discount['percent'] / 100);
        } else {
            $discountValue = min($discount['amount'], $base);
        }

        if ($discount['tax_mode'] === 'after_tax') {
            $afterTaxDiscount = $discountValue;
        } else {
            $beforeTaxDiscount = $discountValue;
        }

        $netSubtotal = max(0, $grossSubtotal - $beforeTaxDiscount);

        return [
            'gross_subtotal'      => ppi_round_money($grossSubtotal),
            'before_tax_discount' => ppi_round_money($beforeTaxDiscount),
            'after_tax_discount'  => ppi_round_money($afterTaxDiscount),
            'net_subtotal'        => ppi_round_money($netSubtotal),
        ];
    }
}
