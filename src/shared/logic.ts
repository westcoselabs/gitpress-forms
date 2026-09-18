import type { Rule } from './types';
export function matches(rule: Rule | undefined, values: Record<string, unknown>): boolean {
  if (!rule?.rules.length) return true;
  const result = rule.rules.map(r => {
    const raw = values[r.field] ?? '';
    const strings = Array.isArray(raw) ? raw.map(String) : [String(raw)];
    const value = strings.join(',');
    switch (r.operator) {
      case 'eq': return strings.includes(String(r.value));
      case 'neq': return !strings.includes(String(r.value));
      case 'contains': return value.toLowerCase().includes(String(r.value).toLowerCase());
      case 'empty': return value === '';
      case 'not_empty': return value !== '';
      case 'gt': return value !== '' && Number(value) > Number(r.value);
      case 'gte': return value !== '' && Number(value) >= Number(r.value);
      case 'lt': return value !== '' && Number(value) < Number(r.value);
      case 'lte': return value !== '' && Number(value) <= Number(r.value);
      default: return false;
    }
  });
  return rule.mode === 'any' ? result.some(Boolean) : result.every(Boolean);
}
// Deliberately a small arithmetic grammar, never JavaScript evaluation.
export function calculate(expression: string, values: Record<string, unknown>): number {
  if (expression.length > 1000) throw new Error('Formula is too long');
  const source = expression.replace(/\{([a-zA-Z][\w]*)\}/g, (_, key: string) => {
    const n = Number(values[key] || 0); if (!Number.isFinite(n)) throw new Error('Invalid numeric value'); return `(${n})`;
  }).replace(/\s+/g, '');
  const tokens = source.match(/(?:\d*\.\d+|\d+\.?\d*)(?:e[+-]?\d+)?|[()+\-*/%^,]|[a-zA-Z]+/g) || [];
  if (tokens.join('') !== source || tokens.length > 300) throw new Error('Invalid formula');
  let index = 0;
  const peek = () => tokens[index];
  function atom(): number {
    const t = tokens[index++];
    if (t === '+') return atom(); if (t === '-') return -atom();
    if (t === '(') { const n = add(); if (tokens[index++] !== ')') throw new Error('Missing closing parenthesis'); return n; }
    if (['min', 'max', 'round', 'abs', 'sum'].includes(t)) {
      if (tokens[index++] !== '(') throw new Error('Expected function arguments');
      const args = [add()]; while (peek() === ',') { index++; args.push(add()); }
      if (tokens[index++] !== ')') throw new Error('Missing closing parenthesis');
      if (t === 'min') return Math.min(...args); if (t === 'max') return Math.max(...args); if (t === 'sum') return args.reduce((a, b) => a + b, 0); if (t === 'abs') return Math.abs(args[0]);
      const places = Math.max(-10, Math.min(10, args[1] || 0)); const factor = 10 ** places; const scaled = args[0] * factor; return Math.sign(scaled) * Math.round(Math.abs(scaled) + Number.EPSILON * Math.max(1, Math.abs(scaled))) / factor;
    }
    if (t === undefined || !/^(?:\d*\.\d+|\d+\.?\d*)(?:e[+-]?\d+)?$/.test(t)) throw new Error('Expected number');
    return Number(t);
  }
  function power(): number { const n = atom(); if (peek() === '^') { index++; return n ** power(); } return n; }
  function multiply(): number { let n = power(); while (['*', '/', '%'].includes(peek())) { const op = tokens[index++], b = power(); if ((op === '/' || op === '%') && b === 0) throw new Error('Division by zero'); n = op === '*' ? n * b : op === '/' ? n / b : n % b; } return n; }
  function add(): number { let n = multiply(); while (['+', '-'].includes(peek())) { const op = tokens[index++], b = multiply(); n = op === '+' ? n + b : n - b; } return n; }
  const result = add(); if (index !== tokens.length || !Number.isFinite(result)) throw new Error('Invalid formula result'); return result;
}
