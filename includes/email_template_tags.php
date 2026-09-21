<?php

function replaceKnownEmailTemplateTags(string $template, array $variables): string
{
    $placeholders = [];
    foreach ($variables as $key => $value) {
        $placeholders['{{' . $key . '}}'] = (string) $value;
    }

    return replaceKnownEmailTemplatePlaceholders($template, $placeholders);
}

function replaceKnownEmailTemplatePlaceholders(
    string $template,
    array $placeholders
): string {
    return preg_replace_callback(
        '/\{\{[a-zA-Z_][a-zA-Z0-9_]*\}\}/',
        static fn(array $match): string => array_key_exists($match[0], $placeholders)
            ? (string) $placeholders[$match[0]]
            : $match[0],
        $template
    ) ?? $template;
}