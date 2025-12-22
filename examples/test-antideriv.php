<?php
require_once __DIR__ . '/autoload.php';

use StreamGate\Stream;
use StreamGate\Event;
use StreamGate\Gates\ResultGate;
use StreamGate\Gates\TermParseGate;
use StreamGate\Gates\ConstantTermGate;
use StreamGate\Gates\IntegrationGate;
use StreamGate\Gates\AlgebraicAddGate;

$stream = new Stream();
$stream->registerGate(new ResultGate());
$stream->registerGate(new TermParseGate());
$stream->registerGate(new ConstantTermGate());
$stream->registerGate(new IntegrationGate());
$stream->registerGate(new AlgebraicAddGate());

echo "Finding antiderivative of x^2-4x+3\n\n";

$stream->emit(new Event("∫x^2-4x+3 dx", $stream->getId()));
$stream->process();

$result = $stream->getResult();
echo "Antiderivative: $result\n";

$cleaned = str_replace(' + C', '', $result);
echo "Without + C: $cleaned\n";
