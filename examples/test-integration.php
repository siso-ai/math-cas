<?php
require_once __DIR__ . '/autoload.php';

use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\IntegrationGate;
use StreamGate\Gates\AlgebraicAddGate;
use StreamGate\Gates\ResultGate;

$stream = new Stream();
$stream->registerGate(new TermParseGate());
$stream->registerGate(new ConstantTermGate());
$stream->registerGate(new IntegrationGate());
$stream->registerGate(new AlgebraicAddGate());
$stream->registerGate(new ResultGate());

echo "Testing: ∫5 dx\n";
echo "Expected: 5x + C\n\n";

$stream->emit(new Event("∫5 dx", $stream->getId()));
$stream->process();

echo "Result: " . $stream->getResult() . "\n";
