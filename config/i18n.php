<?php
class I18n {
    private static array $translations = [];
    private static string $locale = 'es';

    public static function load(string $locale = 'es'): void {
        self::$locale = $locale;
        $file = __DIR__ . "/../lang/$locale.php";
        if (file_exists($file)) {
            self::$translations = require $file;
        }
    }

    public static function trans(string $key, array $replace = []): string {
        $text = self::$translations[$key] ?? $key;
        foreach ($replace as $k => $v) {
            $text = str_replace(":$k", $v, $text);
        }
        return $text;
    }

    public static function getLocale(): string {
        return self::$locale;
    }
}
