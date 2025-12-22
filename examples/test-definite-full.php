<?php
require_once __DIR__ . '/autoload.php';

use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\DefiniteIntegralGate;
use StreamGate\Gates\ResultGate;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\IntegrationGate;
use StreamGate\Gates\SubstitutionGate;
use StreamGate\Gates\MultiplyGate;
use StreamGate\Gates\ExponentGate;
use StreamGate\Gates\AddGate;
use StreamGate\Gates\SubtractGate;

$stream = new Stream();
$stream->registerGate(new DefiniteIntegralGate());
$stream->registerGate(new ResultGate());
$stream->registerGate(new TermParseGate());
$stream->registerGate(new ConstantTermGate());
$stream->registerGate(new AlgebraicAddGate());
$stream->registerGate(new IntegrationGate());
$stream->registerGate(new SubstitutionGate());
$stream->registerGate(new MultiplyGate());
$stream->registerGate(new ExponentGate());
$stream->registerGate(new AddGate());
$stream->registerGate(new SubtractGate());

echo "Testing DefiniteIntegralGate with expression similar to critical point case\n";
echo "∫[0,2] x^2-4x+3 dx\n\n";

$stream->emit(new Event("∫[0,2] x^2-4x+3 dx", $stream->getId()));
$stream->process();

echo "Result: " . $stream->getResult() . "\n";
