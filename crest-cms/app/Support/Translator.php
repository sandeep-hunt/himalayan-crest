<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * English -> other language with the Google Cloud Translation API (v2).
 *
 * - The API key comes from .env (GOOGLE_TRANSLATE_API_KEY), see config('cms.translate').
 * - Every translation is remembered in the `translation_memory` table, so a text that did not
 *   change is never sent (or paid for) again.
 * - Text in {curly brackets} (e.g. {n}, {trek}) and web addresses are kept as they are.
 * - Throws RuntimeException with a readable message when the translation fails.
 */
class Translator
{
    private const MAX_ITEMS = 100;      // Google: up to 128 texts per request

    private const MAX_CHARS = 25000;    // and about 30k characters per request

    /** Google's code for one of our language codes. */
    public static function googleCode(string $code): string
    {
        $map = ['zh' => 'zh-CN', 'zh-tw' => 'zh-TW', 'zh-hk' => 'zh-TW', 'pt-br' => 'pt', 'pt-pt' => 'pt', 'fil' => 'tl', 'nb' => 'no', 'he' => 'iw'];
        if (isset($map[$code])) {
            return $map[$code];
        }
        if (str_contains($code, '-')) {
            [$lang, $region] = explode('-', $code, 2);

            return $lang.'-'.strtoupper($region);
        }

        return $code;
    }

    /**
     * Translate English texts into $target. Keys are kept; empty texts stay empty.
     *
     * @param  array<array-key, string>  $texts
     * @return array<array-key, string>
     */
    public static function translate(array $texts, string $target): array
    {
        $out = [];
        $todo = [];
        $hashes = [];
        foreach ($texts as $k => $text) {
            $text = (string) $text;
            if (trim($text) === '') {
                $out[$k] = '';

                continue;
            }
            $hashes[$k] = sha1($target."\0".$text);
        }
        // already translated before?
        $known = $hashes ? DB::table('translation_memory')->whereIn('hash', array_values(array_unique($hashes)))->pluck('translated', 'hash')->all() : [];
        foreach ($hashes as $k => $h) {
            if (isset($known[$h])) {
                $out[$k] = $known[$h];
            } else {
                $todo[$h] = (string) $texts[$k];
            }
        }

        if ($todo) {
            $done = self::callApi($todo, $target);
            $now = now();
            $rows = [];
            foreach ($done as $h => $translated) {
                $rows[] = ['hash' => $h, 'target' => $target, 'translated' => $translated, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('translation_memory')->upsert($chunk, ['hash'], ['translated', 'updated_at']);
            }
            foreach ($hashes as $k => $h) {
                $out[$k] ??= $done[$h];
            }
        }

        // keep the original order
        return array_replace(array_intersect_key($texts, $out), $out);
    }

    /** @param array<string, string> $todo hash => text   @return array<string, string> hash => translation */
    private static function callApi(array $todo, string $target): array
    {
        $cfg = config('cms.translate');
        if (empty($cfg['key'])) {
            throw new RuntimeException('The Google Translate API key is missing. Add GOOGLE_TRANSLATE_API_KEY to the .env file of the CMS.');
        }

        $result = [];
        // split into requests of at most MAX_ITEMS texts / MAX_CHARS characters
        $batches = [[]];
        $size = 0;
        foreach ($todo as $h => $text) {
            $html = self::toHtml($text);
            $len = mb_strlen($html);
            if ($batches[array_key_last($batches)] && (count($batches[array_key_last($batches)]) >= self::MAX_ITEMS || $size + $len > self::MAX_CHARS)) {
                $batches[] = [];
                $size = 0;
            }
            $batches[array_key_last($batches)][$h] = $html;
            $size += $len;
        }

        foreach ($batches as $batch) {
            try {
                $res = Http::timeout($cfg['timeout'] ?? 30)->acceptJson()->asJson()
                    ->post($cfg['endpoint'].'?key='.urlencode($cfg['key']), [
                        'q' => array_values($batch),
                        'source' => 'en',
                        'target' => self::googleCode($target),
                        'format' => 'html',
                    ]);
            } catch (Throwable $e) {
                throw new RuntimeException('Could not reach Google Translate. Check the internet connection and try again.');
            }
            if (! $res->successful()) {
                $msg = $res->json('error.message') ?: 'HTTP '.$res->status();
                throw new RuntimeException('Google Translate refused the request: '.$msg);
            }
            $translations = (array) $res->json('data.translations', []);
            if (count($translations) !== count($batch)) {
                throw new RuntimeException('Google Translate returned an incomplete answer. Please try again.');
            }
            foreach (array_keys($batch) as $i => $h) {
                $result[$h] = self::fromHtml((string) ($translations[$i]['translatedText'] ?? ''));
            }
        }

        return $result;
    }

    /**
     * Parts that stay exactly as in English (marked "do not translate" for Google):
     * {placeholders}, web addresses, emails, numbers (5,364 · 18 · 2026 · 3-4 · 7:30 · 10/04/2027),
     * month names (January / Jan / Sept) and weekday names (Monday).
     */
    private const KEEP = '~(\{[a-z_0-9]+\}'
        .'|https?://[^\s<>()]+'
        .'|[\w.+-]+@[\w-]+\.[\w.-]+'
        .'|\d+(?:[.,:/\x{2013}-]\d+)*'
        .'|\b(?:January|February|March|April|May|June|July|August|September|October|November|December|Jan|Feb|Mar|Apr|Jun|Jul|Aug|Sept?|Oct|Nov|Dec)\b\.?'
        .'|\b(?:Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday)\b)~u';

    /** Plain text -> HTML for Google, with the KEEP parts marked "do not translate". */
    private static function toHtml(string $text): string
    {
        $html = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = preg_replace(self::KEEP, '<span translate="no">$1</span>', $html);

        return str_replace("\n", '<br>', $html);
    }

    /** Native digits (e.g. Devanagari, Arabic, Telugu) -> 0-9, so numbers look the same as in English. */
    private static function asciiDigits(string $text): string
    {
        $zeros = [0x660, 0x6F0, 0x7C0, 0x966, 0x9E6, 0xA66, 0xAE6, 0xB66, 0xBE6, 0xC66, 0xCE6, 0xD66, 0xDE6, 0xE50, 0xED0, 0xF20, 0x1040, 0x17E0, 0x1810, 0xFF10];

        return preg_replace_callback('~\p{Nd}~u', function ($m) use ($zeros) {
            $cp = mb_ord($m[0]);
            foreach ($zeros as $z) {
                if ($cp >= $z && $cp <= $z + 9) {
                    return (string) ($cp - $z);
                }
            }

            return $m[0];
        }, $text);
    }

    private static function fromHtml(string $html): string
    {
        $text = preg_replace('~\s*<br\s*/?>\s*~i', "\n", $html);
        $text = preg_replace('~<span translate="no">(.*?)</span>~is', '$1', $text);
        $text = strip_tags($text);

        return self::asciiDigits(trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
