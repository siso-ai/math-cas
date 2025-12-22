<?php

namespace StreamGate;

/**
 * Logging levels for event transformation tracking
 */
class LoggingLevel {
    const OFF = 0;       // No tracking (zero overhead)
    const MINIMAL = 1;   // Gate names only
    const STANDARD = 2;  // Gate names + final result
    const DETAILED = 3;  // Before/after for each transformation
    const DEBUG = 4;     // Everything + timestamps + rejected gates
}
