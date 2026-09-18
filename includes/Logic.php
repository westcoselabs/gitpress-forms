<?php
namespace GitPress\Forms;

final class Logic
{
    public static function matches(?array $condition, array $values): bool
    {
        if (empty($condition['rules'])) { return true; }
        $results = [];
        foreach ($condition['rules'] as $rule) {
            $raw = $values[$rule['field']] ?? '';
            $strings = is_array($raw) ? array_map(static fn ($v) => is_scalar($v) ? (string) $v : '', $raw) : [(string) $raw];
            $value = implode(',', $strings); $expected = (string) ($rule['value'] ?? '');
            $results[] = match ($rule['operator']) {
                'eq' => in_array($expected, $strings, true), 'neq' => !in_array($expected, $strings, true),
                'contains' => str_contains(strtolower($value), strtolower($expected)), 'empty' => $value === '', 'not_empty' => $value !== '',
                'gt' => $value !== '' && is_numeric($value) && (float) $value > (float) $expected,
                'gte' => $value !== '' && is_numeric($value) && (float) $value >= (float) $expected,
                'lt' => $value !== '' && is_numeric($value) && (float) $value < (float) $expected,
                'lte' => $value !== '' && is_numeric($value) && (float) $value <= (float) $expected,
                default => false,
            };
        }
        return ($condition['mode'] ?? 'all') === 'any' ? in_array(true, $results, true) : !in_array(false, $results, true);
    }
    public static function calculate(string $expression, array $values, bool $syntaxOnly = false): float
    {
        if (strlen($expression) > 1000) { throw new \InvalidArgumentException('Formula is too long.'); }
        $source = preg_replace_callback('/\{([a-zA-Z][\w]*)\}/', static function ($m) use ($values) {
            $value = $values[$m[1]] ?? 0;
            if ($value === '') { $value = 0; }
            if (!is_numeric($value) || !is_finite((float) $value)) { throw new \InvalidArgumentException('Invalid numeric value.'); }
            return '(' . strtolower((string) (float) $value) . ')';
        }, $expression);
        $source = preg_replace('/\s+/', '', $source);
        preg_match_all('/(?:\d*\.\d+|\d+\.?\d*)(?:e[+-]?\d+)?|[()+\-*\/%^,]|[a-zA-Z]+/', $source, $matches);
        $tokens = $matches[0];
        if (implode('', $tokens) !== $source || count($tokens) > 300) { throw new \InvalidArgumentException('Invalid formula.'); }
        $i = 0;
        $atom = function () use (&$atom, &$add, &$i, $tokens): float {
            $t = $tokens[$i++] ?? null;
            if ($t === '+') { return $atom(); } if ($t === '-') { return -$atom(); }
            if ($t === '(') { $n = $add(); if (($tokens[$i++] ?? '') !== ')') { throw new \InvalidArgumentException('Missing closing parenthesis.'); } return $n; }
            if (in_array($t, ['min', 'max', 'round', 'abs', 'sum'], true)) {
                if (($tokens[$i++] ?? '') !== '(') { throw new \InvalidArgumentException('Expected function arguments.'); }
                $args = [$add()]; while (($tokens[$i] ?? '') === ',') { $i++; $args[] = $add(); }
                if (($tokens[$i++] ?? '') !== ')') { throw new \InvalidArgumentException('Missing closing parenthesis.'); }
                return match ($t) { 'min' => min($args), 'max' => max($args), 'sum' => array_sum($args), 'abs' => abs($args[0]), 'round' => round($args[0], (int) max(-10, min(10, $args[1] ?? 0)), PHP_ROUND_HALF_UP) };
            }
            if ($t === null || !is_numeric($t)) { throw new \InvalidArgumentException('Expected number.'); }
            return (float) $t;
        };
        $power = function () use (&$power, $atom, &$i, $tokens, $syntaxOnly): float { $n = $atom(); if (($tokens[$i] ?? '') === '^') { $i++; $exponent = $power(); return $syntaxOnly ? 1 : $n ** $exponent; } return $n; };
        $multiply = function () use ($power, &$i, $tokens, $syntaxOnly): float {
            $n = $power();
            while (in_array($tokens[$i] ?? '', ['*', '/', '%'], true)) { $op = $tokens[$i++]; $b = $power(); if ($syntaxOnly) { $n = 1; continue; } if ($b == 0 && $op !== '*') { throw new \InvalidArgumentException('Division by zero.'); } $n = match ($op) { '*' => $n * $b, '/' => $n / $b, '%' => fmod($n, $b) }; }
            return $n;
        };
        $add = function () use ($multiply, &$i, $tokens): float { $n = $multiply(); while (in_array($tokens[$i] ?? '', ['+', '-'], true)) { $op = $tokens[$i++]; $b = $multiply(); $n = $op === '+' ? $n + $b : $n - $b; } return $n; };
        $result = $add();
        if ($i !== count($tokens) || !is_finite($result)) { throw new \InvalidArgumentException('Invalid formula result.'); }
        return $result;
    }
}
