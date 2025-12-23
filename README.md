# SISO Math CAS

**PHP implementation - Symbolic mathematics using the SISO framework**

[![Paper](https://img.shields.io/badge/Paper-PDF-blue)](https://siso-framework.org/downloads/siso-paper.pdf)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## Overview

Complete computer algebra system (CAS) demonstrating the SISO (Stream In, Stream Out) framework. Implements symbolic computation, algebraic manipulation, calculus, and equation solving.

**Paper:** [SISO: A Pure Functional Framework for Rapid AI-Assisted Software Development](https://siso-framework.org/siso-paper.pdf)  
**Website:** [siso-framework.org](https://siso-framework.org)

## Features

### Arithmetic Operations
- Addition, subtraction, multiplication, division
- Exponentiation, nth roots, square roots
- Absolute value, floor, ceiling
- Modulo, factorial

### Algebraic Operations
- Polynomial arithmetic
- Expression simplification
- FOIL (binomial expansion)
- Distribution
- Factoring (including quadratic formulas)
- Equation solving

### Calculus
- **Derivatives**: Power rule, product rule, chain rule
- **Integration**: Polynomial integration, definite integrals
- **Optimization**: Critical points, extrema finding

## Quick Start

```php
<?php
require_once 'autoload.php';

use StreamGate\Stream;
use StreamGate\AlgebraicEvent;
use StreamGate\Gates\DerivativeGate;

$stream = new Stream();
$stream->registerGate(new DerivativeGate());

$stream->emit(new AlgebraicEvent('d/dx(x^3+2x^2+x)', 'derivative'));
$stream->process();

echo $stream->getResult()->data; // Output: 3x^2+4x+1
```

## Installation

```bash
# Clone the repository
git clone https://github.com/siso-ai/siso-math-cas.git
cd siso-math-cas

# Install dependencies (optional - for testing)
composer install
```

## Requirements

- **PHP 8.1 or higher**
- No external dependencies for core functionality

## Testing

```bash
php run-tests.php
```

**Results:** 295/295 tests passing ✓

### Run Individual Test Phases

```bash
# Test derivatives
php demos/demo-phase13.php

# Test integration
php demos/demo-phase15.php

# Test optimization
php demos/demo-phase20.php
```

## Examples

### Derivatives

```php
$stream = new Stream();
$stream->registerGate(new TermParseGate());
$stream->registerGate(new DerivativeGate());

$stream->emit(new AlgebraicEvent('d/dx(x^2+3x+1)', 'derivative'));
$stream->process();
// Output: 2x+3
```

### Integration

```php
$stream->emit(new AlgebraicEvent('∫(3x^2+4x)dx', 'integration'));
$stream->process();
// Output: x^3+2x^2+C
```

### Optimization

```php
$stream->emit(new AlgebraicEvent('critical(x^2-4x+3)', 'critical'));
$stream->process();
// Output: x=2, value=0
```

## Architecture

```
Input Event (expression string)
  ↓
TermParseGate (parse to algebraic structure)
  ↓
Domain-Specific Gates (transform/simplify)
  ↓
ResultGate (capture final result)
  ↓
Output (simplified expression)
```

## File Structure

```
siso-math-cas/
├── code/                  # Source code
│   ├── Algebra/           # Algebraic data structures
│   │   ├── Expression.php
│   │   ├── Term.php
│   │   └── Variable.php
│   ├── Gates/             # 33 transformation gates
│   │   ├── DerivativeGate.php
│   │   ├── IntegrationGate.php
│   │   ├── FactoringGate.php
│   │   └── ... (30 more)
│   ├── Event.php          # Base event
│   ├── AlgebraicEvent.php
│   ├── Stream.php
│   └── ...
├── tests/                 # 23 test phases
├── demos/                 # 19 progressive demos
├── examples/              # Focused examples
├── run-tests.php          # Comprehensive test runner
├── autoload.php
└── composer.json
```

## Available Gates

### Calculus
- DerivativeGate (differentiation)
- ProductRuleGate (product rule)
- IntegrationGate (indefinite integration)
- DefiniteIntegralGate (definite integration)
- CriticalPointsGate (optimization)

### Algebraic
- AlgebraicAddGate (polynomial addition)
- DistributionGate (distribution property)
- FOILGate (binomial expansion)
- FactoringGate (polynomial factoring)
- SubstitutionGate (variable substitution)

### And 23 more...

## Test Coverage

- **Phase 0-4**: Arithmetic operations (15 tests)
- **Phase 5-8**: Algebraic operations (32 tests)
- **Phase 9-12**: Advanced algebra (45 tests)
- **Phase 13-16**: Calculus - Derivatives (58 tests)
- **Phase 17-19**: Calculus - Integration (67 tests)
- **Phase 20-23**: System integration (78 tests)

**Total: 295 tests across 23 phases**

## Related Implementations

- **[Logic Prover](https://github.com/siso-ai/siso-logic-prover)** (JavaScript) - Automated theorem proving
- **[Database](https://github.com/siso-ai/siso-database)** (PHP) - SQL database engine
- **[Framework](https://github.com/siso-ai/siso-framework)** - Core SISO framework specification

## Citation

```bibtex
@misc{bailey2025siso,
  title={SISO: A Pure Functional Framework for Rapid AI-Assisted Software Development},
  author={Bailey, Jonathan},
  year={2025},
  url={https://siso-framework.org}
}
```

## License

MIT License - See [LICENSE](LICENSE) file

Copyright (c) 2025 Jonathan Bailey

## Contact

- **Issues**: [GitHub Issues](https://github.com/siso-ai/siso-math-cas/issues)
- **Website**: [siso-framework.org](https://siso-framework.org)
