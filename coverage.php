<?php

if ($_SERVER['REQUEST_URI'] === "/") {
    $_SERVER['REQUEST_URI'] = "/index.html";
}
if (str_ends_with($_SERVER['REQUEST_URI'], '.html')) {
    header("Content-Type: text/html; charset=utf-8");
} else if (str_ends_with($_SERVER['REQUEST_URI'], '.js')) {
    header("Content-Type: text/javascript; charset=utf-8");
} else if (str_ends_with($_SERVER['REQUEST_URI'], '.css')) {
    header("Content-Type: text/css; charset=utf-8");
} else if (str_ends_with($_SERVER['REQUEST_URI'], '.svg')) {
    header("Content-Type: image/svg");
} else {
    http_response_code(404);
    exit();
}
readfile(__DIR__ . "/docs/coverage/" . $_SERVER['REQUEST_URI']);
exit();
