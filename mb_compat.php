<?php
/**
 * Thư viện tương thích chuỗi Unicode đa byte (Polyfill nếu PHP chưa bật extension mbstring)
 * Đảm bảo hệ thống xử lý tiếng Hàn và tiếng Việt mượt mà trên mọi môi trường PHP Windows/Linux
 */

if (!function_exists('mb_strlen')) {
    function mb_strlen(?string $str, ?string $encoding = 'UTF-8'): int {
        if ($str === null || $str === '') return 0;
        preg_match_all('/./us', $str, $matches);
        return count($matches[0]);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(?string $str, int $start, ?int $length = null, ?string $encoding = 'UTF-8'): string {
        if ($str === null || $str === '') return '';
        preg_match_all('/./us', $str, $matches);
        $chars = $matches[0];
        if ($length === null) {
            $slice = array_slice($chars, $start);
        } else {
            $slice = array_slice($chars, $start, $length);
        }
        return implode('', $slice);
    }
}

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(?string $str, ?string $encoding = 'UTF-8'): string {
        if ($str === null) return '';
        return strtolower($str);
    }
}

if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper(?string $str, ?string $encoding = 'UTF-8'): string {
        if ($str === null) return '';
        return strtoupper($str);
    }
}

if (!function_exists('mb_check_encoding')) {
    function mb_check_encoding(?string $var = null, ?string $encoding = 'UTF-8'): bool {
        if ($var === null) return true;
        return (bool)preg_match('//u', $var);
    }
}

if (!function_exists('mb_detect_encoding')) {
    function mb_detect_encoding(?string $str, $encodings = null, bool $strict = false) {
        if ($str === null) return false;
        if (preg_match('//u', $str)) return 'UTF-8';
        return 'ISO-8859-1';
    }
}

if (!function_exists('mb_convert_encoding')) {
    function mb_convert_encoding(string|array|null $string, string $to_encoding, array|string|null $from_encoding = null): string|array|false {
        if ($string === null) return '';
        if (function_exists('iconv')) {
            $from = is_array($from_encoding) ? ($from_encoding[0] ?? 'UTF-8') : ($from_encoding ?: 'UTF-8');
            return @iconv($from, $to_encoding . '//IGNORE', $string);
        }
        return $string;
    }
}
