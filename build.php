<?php

use davekok\http\HttpParser;
use davekok\parser\ParserGenerator;
use davekok\parser\ParserReflection;

include __DIR__ . "/vendor/autoload.php";

foreach (new ParserGenerator(new ParserReflection(HttpParser::class)) as $phpFile) {
    file_put_contents($phpFile->name, (string)$phpFile);
}
