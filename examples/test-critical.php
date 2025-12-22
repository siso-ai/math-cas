<?php
require_once __DIR__ . '/autoload.php';

use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\CriticalPointsGate;
use StreamGate\Gates\ResultGate;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\DerivativeGate;
use StreamGate\Gates\EquationGate;
use StreamGate\Gates\SubstitutionGate;
use StreamGate\Gates\MultiplyGate;
use StreamGate\Gates\ExponentGate;
use StreamGate\Gates\AddGate;
use StreamGate\Gates\SubtractGate;
use StreamGate\Gates\DivideGate;

$stream = new Stream();
$stream->registerGate(new CriticalPointsGate());
$stream->registerGate(new ResultGate());
$stream->registerGate(new TermParseGate());
$stream->registerGate(new ConstantTermGate());
$stream->registerGate(new AlgebraicAddGate());
$stream->registerGate(new DerivativeGate());
$stream->registerGate(new EquationGate());
$stream->registerGate(new SubstitutionGate());
$stream->registerGate(new MultiplyGate());
$stream->registerGate(new ExponentGate());
$stream->registerGate(new AddGate());
$stream->registerGate(new SubtractGate());
$stream->registerGate(new DivideGate());

echo "Testing: critical(x^2-4x+3)\n";
echo "f(x) = x^2-4x+3\n";
echo "f'(x) = 2x-4 = 0 → x = 2\n";
echo "f(2) = 4-8+3 = -1\n";
echo "Expected: x=2, value=-1\n\n";

$stream->emit(new Event("critical(x^2-4x+3)", $stream->getId()));
$stream->process();

echo "Result: " . $stream->getResult() . "\n";
