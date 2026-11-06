<?php

namespace Tabula17\Satelles\Utilis\File;

final class MimeTypeDefinitions
{
    private static ?array $extensionLookupCache = null;

    public static function mime(MimeTypes $type): string
    {
        return self::definitions()[$type->name]['mime'] ?? 'application/octet-stream';
    }

    public static function extension(MimeTypes $type): string
    {
        return self::definitions()[$type->name]['extension'] ?? 'bin';
    }

    public static function extensions(MimeTypes $type): array
    {
        return self::definitions()[$type->name]['extensions'] ?? [];
    }

    public static function extensionExists(string $extension): bool
    {
        $extension = strtolower(ltrim($extension, '.'));

        return isset(self::extensionLookup()[$extension]);
    }

    public static function fromExtension(string $extension): ?MimeTypes
    {
        $extension = strtolower(ltrim($extension, '.'));

        return self::extensionLookup()[$extension] ?? null;
    }

    public static function fromFile(string $file): MimeTypes
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if ($extension === '') {
            return MimeTypes::BIN;
        }

        return self::fromExtension($extension) ?? MimeTypes::BIN;
    }

    public static function fromMime(string $value): ?MimeTypes
    {
        $cleanMime = strtolower(trim(explode(';', $value)[0]));

        foreach (self::definitions() as $name => $definition) {
            if ($definition['mime'] === $cleanMime) {
                return constant(MimeTypes::class . '::' . $name);
            }
            if (isset($definition['mimes']) && in_array($cleanMime, $definition['mimes'], true)) {
                return constant(MimeTypes::class . '::' . $name);
            }
        }
        return null;
    }

    public static function isTextBased(MimeTypes $type): bool
    {
        return isset(self::textBasedSet()[$type->name]);
    }

    public static function isOpenOffice(MimeTypes $type): bool
    {
        return self::isOpenDocument($type);
    }

    public static function isOpenDocument(MimeTypes $type): bool
    {
        return isset(self::openDocumentSet()[$type->name]);
    }

    public static function isOpenOfficeCompatible(MimeTypes $type): bool
    {
        return isset(self::openOfficeCompatibleSet()[$type->name]);
    }

    public static function isLibreOfficeCompatible(MimeTypes $type): bool
    {
        return self::isOpenOfficeCompatible($type);
    }

    private static function extensionLookup(): array
    {
        if (self::$extensionLookupCache !== null) {
            return self::$extensionLookupCache;
        }

        $lookup = [];

        foreach (self::definitions() as $name => $definition) {
            $case = constant(MimeTypes::class . '::' . $name);

            foreach ($definition['extensions'] as $extension) {
                $lookup[$extension] = $case;
            }
        }

        self::$extensionLookupCache = $lookup;

        return self::$extensionLookupCache;
    }

    private static function textBasedSet(): array
    {
        return [
            'HTML' => true,
            'CSS' => true,
            'JS' => true,
            'JSON' => true,
            'JSONLD' => true,
            'XML' => true,
            'TXT' => true,
            'CSV' => true,
            'TSV' => true,
            'MD' => true,
            'YAML' => true,
            'RTF' => true,
            'SQL' => true,
            'ICS' => true,
            'VCF' => true,
            'SVG' => true,
        ];
    }

    private static function openDocumentSet(): array
    {
        return [
            // OpenDocument Formats (ODF)
            'ODT' => true,
            'OTT' => true,
            'ODM' => true,
            'OTH' => true,
            'ODS' => true,
            'OTS' => true,
            'ODP' => true,
            'OTP' => true,
            'ODG' => true,
            'OTG' => true,
            'ODF' => true,
            'ODB' => true,
            // StarOffice / OpenOffice legacy formats
            'SXW' => true,
            'STW' => true,
            'SXC' => true,
            'STC' => true,
            'SXI' => true,
            'STI' => true,
            'SXD' => true,
            'SXM' => true,
        ];
    }

    private static function openOfficeCompatibleSet(): array
    {
        return [
            // OpenDocument / OpenOffice / StarOffice
            'ODT' => true,
            'OTT' => true,
            'ODM' => true,
            'OTH' => true,
            'ODS' => true,
            'OTS' => true,
            'ODP' => true,
            'OTP' => true,
            'ODG' => true,
            'OTG' => true,
            'ODF' => true,
            'ODB' => true,
            'SXW' => true,
            'STW' => true,
            'SXC' => true,
            'STC' => true,
            'SXI' => true,
            'STI' => true,
            'SXD' => true,
            'SXM' => true,

            // Microsoft Office
            'DOC' => true,
            'DOCX' => true,
            'DOCM' => true,
            'DOT' => true,
            'DOTX' => true,
            'DOTM' => true,
            'XLS' => true,
            'XLSX' => true,
            'XLSM' => true,
            'XLT' => true,
            'XLTX' => true,
            'XLTM' => true,
            'XLSB' => true,
            'PPT' => true,
            'PPTX' => true,
            'PPTM' => true,
            'POT' => true,
            'POTX' => true,
            'POTM' => true,
            'PPS' => true,
            'PPSX' => true,
            'PPSM' => true,
            'VSD' => true,
            'VSDX' => true,

            // Document & Interchange formats
            'RTF' => true,
            'TXT' => true,
            'CSV' => true,
            'TSV' => true,
            'HTML' => true,
            'XML' => true,
            'EPUB' => true,
            'PDF' => true,

            // Image formats supported by LibreOffice Draw / Writer / Impress for conversions
            'SVG' => true,
            'WMF' => true,
            'EMF' => true,
            'BMP' => true,
            'JPG' => true,
            'PNG' => true,
            'GIF' => true,
            'TIFF' => true,
            'WEBP' => true,
        ];
    }

    private static function definitions(): array
    {
        return [
            'HTML' => [
                'mime' => 'text/html',
                'extensions' => ['html', 'htm'],
                'extension' => 'html',
            ],
            'MULTIPARTFORM' => [
                'mime' => 'multipart/form-data',
                'extensions' => [],
                'extension' => 'mform',
            ],
            'CSS' => [
                'mime' => 'text/css',
                'extensions' => ['css'],
                'extension' => 'css',
            ],
            'JS' => [
                'mime' => 'application/javascript',
                'extensions' => ['js', 'mjs'],
                'extension' => 'js',
            ],
            'JSON' => [
                'mime' => 'application/json',
                'extensions' => ['json'],
                'extension' => 'json',
            ],
            'JSONLD' => [
                'mime' => 'application/ld+json',
                'extensions' => ['jsonld'],
                'extension' => 'jsonld',
            ],
            'XML' => [
                'mime' => 'application/xml',
                'extensions' => ['xml'],
                'extension' => 'xml',
            ],
            'TXT' => [
                'mime' => 'text/plain',
                'extensions' => ['txt'],
                'extension' => 'txt',
            ],
            'CSV' => [
                'mime' => 'text/csv',
                'extensions' => ['csv'],
                'extension' => 'csv',
            ],
            'TSV' => [
                'mime' => 'text/tab-separated-values',
                'extensions' => ['tsv'],
                'extension' => 'tsv',
            ],
            'MD' => [
                'mime' => 'text/markdown',
                'extensions' => ['md', 'markdown'],
                'extension' => 'md',
            ],
            'YAML' => [
                'mime' => 'application/yaml',
                'mimes' => ['text/yaml'],
                'extensions' => ['yaml', 'yml'],
                'extension' => 'yaml',
            ],
            'RTF' => [
                'mime' => 'application/rtf',
                'mimes' => ['text/rtf', 'application/x-rtf'],
                'extensions' => ['rtf'],
                'extension' => 'rtf',
            ],
            'SQL' => [
                'mime' => 'application/sql',
                'extensions' => ['sql'],
                'extension' => 'sql',
            ],
            'ICS' => [
                'mime' => 'text/calendar',
                'extensions' => ['ics', 'ical'],
                'extension' => 'ics',
            ],
            'VCF' => [
                'mime' => 'text/vcard',
                'extensions' => ['vcf', 'vcard'],
                'extension' => 'vcf',
            ],
            'JPG' => [
                'mime' => 'image/jpeg',
                'extensions' => ['jpg', 'jpeg'],
                'extension' => 'jpg',
            ],
            'PNG' => [
                'mime' => 'image/png',
                'extensions' => ['png'],
                'extension' => 'png',
            ],
            'GIF' => [
                'mime' => 'image/gif',
                'extensions' => ['gif'],
                'extension' => 'gif',
            ],
            'SVG' => [
                'mime' => 'image/svg+xml',
                'extensions' => ['svg'],
                'extension' => 'svg',
            ],
            'ICO' => [
                'mime' => 'image/x-icon',
                'extensions' => ['ico'],
                'extension' => 'ico',
            ],
            'WEBP' => [
                'mime' => 'image/webp',
                'extensions' => ['webp'],
                'extension' => 'webp',
            ],
            'AVIF' => [
                'mime' => 'image/avif',
                'extensions' => ['avif'],
                'extension' => 'avif',
            ],
            'BMP' => [
                'mime' => 'image/bmp',
                'extensions' => ['bmp'],
                'extension' => 'bmp',
            ],
            'TIFF' => [
                'mime' => 'image/tiff',
                'extensions' => ['tif', 'tiff'],
                'extension' => 'tif',
            ],
            'HEIC' => [
                'mime' => 'image/heic',
                'extensions' => ['heic'],
                'extension' => 'heic',
            ],
            'HEIF' => [
                'mime' => 'image/heif',
                'extensions' => ['heif'],
                'extension' => 'heif',
            ],
            'PSD' => [
                'mime' => 'image/vnd.adobe.photoshop',
                'extensions' => ['psd'],
                'extension' => 'psd',
            ],
            'WMF' => [
                'mime' => 'image/wmf',
                'extensions' => ['wmf'],
                'extension' => 'wmf',
            ],
            'EMF' => [
                'mime' => 'image/emf',
                'extensions' => ['emf'],
                'extension' => 'emf',
            ],
            'PDF' => [
                'mime' => 'application/pdf',
                'extensions' => ['pdf'],
                'extension' => 'pdf',
            ],
            'EPUB' => [
                'mime' => 'application/epub+zip',
                'extensions' => ['epub'],
                'extension' => 'epub',
            ],
            'ZIP' => [
                'mime' => 'application/zip',
                'extensions' => ['zip'],
                'extension' => 'zip',
            ],
            'RAR' => [
                'mime' => 'application/vnd.rar',
                'extensions' => ['rar'],
                'extension' => 'rar',
            ],
            'TAR' => [
                'mime' => 'application/x-tar',
                'extensions' => ['tar'],
                'extension' => 'tar',
            ],
            'GZ' => [
                'mime' => 'application/gzip',
                'extensions' => ['gz'],
                'extension' => 'gz',
            ],
            'BZ2' => [
                'mime' => 'application/x-bzip2',
                'extensions' => ['bz2'],
                'extension' => 'bz2',
            ],
            'SEVEN_Z' => [
                'mime' => 'application/x-7z-compressed',
                'extensions' => ['7z'],
                'extension' => '7z',
            ],
            'XZ' => [
                'mime' => 'application/x-xz',
                'extensions' => ['xz'],
                'extension' => 'xz',
            ],
            'DOC' => [
                'mime' => 'application/msword',
                'extensions' => ['doc'],
                'extension' => 'doc',
            ],
            'DOCX' => [
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'extensions' => ['docx'],
                'extension' => 'docx',
            ],
            'DOCM' => [
                'mime' => 'application/vnd.ms-word.document.macroenabled.12',
                'extensions' => ['docm'],
                'extension' => 'docm',
            ],
            'DOT' => [
                'mime' => 'application/msword',
                'extensions' => ['dot'],
                'extension' => 'dot',
            ],
            'DOTX' => [
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
                'extensions' => ['dotx'],
                'extension' => 'dotx',
            ],
            'DOTM' => [
                'mime' => 'application/vnd.ms-word.template.macroenabled.12',
                'extensions' => ['dotm'],
                'extension' => 'dotm',
            ],
            'XLS' => [
                'mime' => 'application/vnd.ms-excel',
                'extensions' => ['xls'],
                'extension' => 'xls',
            ],
            'XLSX' => [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'extensions' => ['xlsx'],
                'extension' => 'xlsx',
            ],
            'XLSM' => [
                'mime' => 'application/vnd.ms-excel.sheet.macroenabled.12',
                'extensions' => ['xlsm'],
                'extension' => 'xlsm',
            ],
            'XLT' => [
                'mime' => 'application/vnd.ms-excel',
                'extensions' => ['xlt'],
                'extension' => 'xlt',
            ],
            'XLTX' => [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
                'extensions' => ['xltx'],
                'extension' => 'xltx',
            ],
            'XLTM' => [
                'mime' => 'application/vnd.ms-excel.template.macroenabled.12',
                'extensions' => ['xltm'],
                'extension' => 'xltm',
            ],
            'XLSB' => [
                'mime' => 'application/vnd.ms-excel.sheet.binary.macroenabled.12',
                'extensions' => ['xlsb'],
                'extension' => 'xlsb',
            ],
            'PPT' => [
                'mime' => 'application/vnd.ms-powerpoint',
                'extensions' => ['ppt'],
                'extension' => 'ppt',
            ],
            'PPTX' => [
                'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'extensions' => ['pptx'],
                'extension' => 'pptx',
            ],
            'PPTM' => [
                'mime' => 'application/vnd.ms-powerpoint.presentation.macroenabled.12',
                'extensions' => ['pptm'],
                'extension' => 'pptm',
            ],
            'POT' => [
                'mime' => 'application/vnd.ms-powerpoint',
                'extensions' => ['pot'],
                'extension' => 'pot',
            ],
            'POTX' => [
                'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
                'extensions' => ['potx'],
                'extension' => 'potx',
            ],
            'POTM' => [
                'mime' => 'application/vnd.ms-powerpoint.template.macroenabled.12',
                'extensions' => ['potm'],
                'extension' => 'potm',
            ],
            'PPS' => [
                'mime' => 'application/vnd.ms-powerpoint',
                'extensions' => ['pps'],
                'extension' => 'pps',
            ],
            'PPSX' => [
                'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
                'extensions' => ['ppsx'],
                'extension' => 'ppsx',
            ],
            'PPSM' => [
                'mime' => 'application/vnd.ms-powerpoint.slideshow.macroenabled.12',
                'extensions' => ['ppsm'],
                'extension' => 'ppsm',
            ],
            'VSD' => [
                'mime' => 'application/vnd.visio',
                'extensions' => ['vsd'],
                'extension' => 'vsd',
            ],
            'VSDX' => [
                'mime' => 'application/vnd.ms-visio.drawing',
                'extensions' => ['vsdx'],
                'extension' => 'vsdx',
            ],
            'ODT' => [
                'mime' => 'application/vnd.oasis.opendocument.text',
                'extensions' => ['odt'],
                'extension' => 'odt',
            ],
            'OTT' => [
                'mime' => 'application/vnd.oasis.opendocument.text-template',
                'extensions' => ['ott'],
                'extension' => 'ott',
            ],
            'ODM' => [
                'mime' => 'application/vnd.oasis.opendocument.text-master',
                'extensions' => ['odm'],
                'extension' => 'odm',
            ],
            'OTH' => [
                'mime' => 'application/vnd.oasis.opendocument.text-web',
                'extensions' => ['oth'],
                'extension' => 'oth',
            ],
            'ODS' => [
                'mime' => 'application/vnd.oasis.opendocument.spreadsheet',
                'extensions' => ['ods'],
                'extension' => 'ods',
            ],
            'OTS' => [
                'mime' => 'application/vnd.oasis.opendocument.spreadsheet-template',
                'extensions' => ['ots'],
                'extension' => 'ots',
            ],
            'ODP' => [
                'mime' => 'application/vnd.oasis.opendocument.presentation',
                'extensions' => ['odp'],
                'extension' => 'odp',
            ],
            'OTP' => [
                'mime' => 'application/vnd.oasis.opendocument.presentation-template',
                'extensions' => ['otp'],
                'extension' => 'otp',
            ],
            'ODG' => [
                'mime' => 'application/vnd.oasis.opendocument.graphics',
                'extensions' => ['odg'],
                'extension' => 'odg',
            ],
            'OTG' => [
                'mime' => 'application/vnd.oasis.opendocument.graphics-template',
                'extensions' => ['otg'],
                'extension' => 'otg',
            ],
            'ODF' => [
                'mime' => 'application/vnd.oasis.opendocument.formula',
                'extensions' => ['odf'],
                'extension' => 'odf',
            ],
            'ODB' => [
                'mime' => 'application/vnd.oasis.opendocument.database',
                'extensions' => ['odb'],
                'extension' => 'odb',
            ],
            'SXW' => [
                'mime' => 'application/vnd.sun.xml.writer',
                'extensions' => ['sxw'],
                'extension' => 'sxw',
            ],
            'STW' => [
                'mime' => 'application/vnd.sun.xml.writer.template',
                'extensions' => ['stw'],
                'extension' => 'stw',
            ],
            'SXC' => [
                'mime' => 'application/vnd.sun.xml.calc',
                'extensions' => ['sxc'],
                'extension' => 'sxc',
            ],
            'STC' => [
                'mime' => 'application/vnd.sun.xml.calc.template',
                'extensions' => ['stc'],
                'extension' => 'stc',
            ],
            'SXI' => [
                'mime' => 'application/vnd.sun.xml.impress',
                'extensions' => ['sxi'],
                'extension' => 'sxi',
            ],
            'STI' => [
                'mime' => 'application/vnd.sun.xml.impress.template',
                'extensions' => ['sti'],
                'extension' => 'sti',
            ],
            'SXD' => [
                'mime' => 'application/vnd.sun.xml.draw',
                'extensions' => ['sxd'],
                'extension' => 'sxd',
            ],
            'SXM' => [
                'mime' => 'application/vnd.sun.xml.math',
                'extensions' => ['sxm'],
                'extension' => 'sxm',
            ],
            'MP3' => [
                'mime' => 'audio/mpeg',
                'extensions' => ['mp3'],
                'extension' => 'mp3',
            ],
            'WAV' => [
                'mime' => 'audio/wav',
                'extensions' => ['wav'],
                'extension' => 'wav',
            ],
            'OGG' => [
                'mime' => 'audio/ogg',
                'extensions' => ['ogg'],
                'extension' => 'ogg',
            ],
            'M4A' => [
                'mime' => 'audio/mp4',
                'extensions' => ['m4a'],
                'extension' => 'm4a',
            ],
            'AAC' => [
                'mime' => 'audio/aac',
                'extensions' => ['aac'],
                'extension' => 'aac',
            ],
            'FLAC' => [
                'mime' => 'audio/flac',
                'extensions' => ['flac'],
                'extension' => 'flac',
            ],
            'OPUS' => [
                'mime' => 'audio/opus',
                'extensions' => ['opus'],
                'extension' => 'opus',
            ],
            'MP4' => [
                'mime' => 'video/mp4',
                'extensions' => ['mp4'],
                'extension' => 'mp4',
            ],
            'WEBM' => [
                'mime' => 'video/webm',
                'extensions' => ['webm'],
                'extension' => 'webm',
            ],
            'MOV' => [
                'mime' => 'video/quicktime',
                'extensions' => ['mov'],
                'extension' => 'mov',
            ],
            'AVI' => [
                'mime' => 'video/x-msvideo',
                'extensions' => ['avi'],
                'extension' => 'avi',
            ],
            'MKV' => [
                'mime' => 'video/x-matroska',
                'extensions' => ['mkv'],
                'extension' => 'mkv',
            ],
            'MPEG' => [
                'mime' => 'video/mpeg',
                'extensions' => ['mpeg', 'mpg'],
                'extension' => 'mpg',
            ],
            'M4V' => [
                'mime' => 'video/x-m4v',
                'extensions' => ['m4v'],
                'extension' => 'm4v',
            ],
            'WOFF' => [
                'mime' => 'font/woff',
                'extensions' => ['woff'],
                'extension' => 'woff',
            ],
            'WOFF2' => [
                'mime' => 'font/woff2',
                'extensions' => ['woff2'],
                'extension' => 'woff2',
            ],
            'TTF' => [
                'mime' => 'font/ttf',
                'extensions' => ['ttf'],
                'extension' => 'ttf',
            ],
            'OTF' => [
                'mime' => 'font/otf',
                'extensions' => ['otf'],
                'extension' => 'otf',
            ],
            'EOT' => [
                'mime' => 'application/vnd.ms-fontobject',
                'extensions' => ['eot'],
                'extension' => 'eot',
            ],
            'SFNT' => [
                'mime' => 'font/sfnt',
                'extensions' => ['sfnt'],
                'extension' => 'sfnt',
            ],
            'TTC' => [
                'mime' => 'font/collection',
                'extensions' => ['ttc'],
                'extension' => 'ttc',
            ],
            'WASM' => [
                'mime' => 'application/wasm',
                'extensions' => ['wasm'],
                'extension' => 'wasm',
            ],
            'BIN' => [
                'mime' => 'application/octet-stream',
                'extensions' => [],
                'extension' => 'bin',
            ],
        ];
    }
}