<?php

function getMemory($chatId, $limit = 10)
{
    $file = __DIR__ . "/memory/" . md5($chatId) . ".json";
    if (!file_exists($file)) return [];

    $data = json_decode(file_get_contents($file), true);
    return array_slice($data, -$limit);
}

function saveMemory($chatId, $role, $content)
{
    $file = __DIR__ . "/memory/" . md5($chatId) . ".json";
    $data = file_exists($file)
        ? json_decode(file_get_contents($file), true)
        : [];

    $data[] = [
        "role" => $role,
        "content" => $content
    ];

    file_put_contents($file, json_encode($data));
}
