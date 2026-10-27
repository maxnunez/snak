<?php

declare(strict_types=1);

/**
 * Currency helper for Bolivianos (Bs)
 */

if (! function_exists('format_bs')) {
    /**
     * Format a number as Bolivianos (Bs)
     *
     * @param float|int|string|null $amount
     * @param int $decimals
     * @return string
     */
    function format_bs($amount, int $decimals = 2): string
    {
        if ($amount === null || $amount === '') {
            return 'Bs 0,00';
        }

        $amount = (float) $amount;

        // Use NumberFormatter for proper locale formatting (es_BO)
        $fmt = new NumberFormatter('es_BO', NumberFormatter::CURRENCY);
        $fmt->setSymbol(NumberFormatter::CURRENCY_SYMBOL, 'Bs ');
        
        return $fmt->formatCurrency($amount, 'BOB');
    }
}

if (! function_exists('format_bs_short')) {
    /**
     * Format a number as Bolivianos (Bs) without symbol
     *
     * @param float|int|string|null $amount
     * @param int $decimals
     * @return string
     */
    function format_bs_short($amount, int $decimals = 2): string
    {
        if ($amount === null || $amount === '') {
            return '0,00';
        }

        $amount = (float) $amount;

        $fmt = new NumberFormatter('es_BO', NumberFormatter::DECIMAL);
        $fmt->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
        $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
        
        return $fmt->format($amount);
    }
}

if (! function_exists('parse_bs')) {
    /**
     * Parse a Bolivianos string back to float
     *
     * @param string $formatted
     * @return float
     */
    function parse_bs(string $formatted): float
    {
        // Remove "Bs ", spaces, and normalize decimal separator
        $clean = str_replace(['Bs ', 'Bs', ' ', '.', ','], ['', '', '', '', '.'], $formatted);
        return (float) $clean;
    }
}