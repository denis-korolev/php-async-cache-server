<?php

declare(strict_types=1);

$port = getenv('GATEWAY_PORT') ?: '81';
$swaggerTemplate = file_get_contents(__DIR__ . '/../swagger.yaml');
$swaggerContent = str_replace('${GATEWAY_PORT}', $port, $swaggerTemplate);

file_put_contents(__DIR__ . '/../swagger.generated.yaml', $swaggerContent); 