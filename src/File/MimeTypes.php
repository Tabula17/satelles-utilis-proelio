<?php

namespace Tabula17\Satelles\Utilis\File;

use JsonSerializable;
use Tabula17\Satelles\Utilis\Interface\EnumMethodsInterface;

enum MimeTypes implements EnumMethodsInterface, JsonSerializable
{
    case HTML;
    case CSS;
    case JS;
    case JSON;
    case JSONLD;
    case XML;
    case TXT;
    case CSV;
    case TSV;
    case MD;
    case YAML;
    case RTF;
    case SQL;
    case ICS;
    case VCF;

    case MULTIPARTFORM;

    case JPG;
    case PNG;
    case GIF;
    case SVG;
    case ICO;
    case WEBP;
    case AVIF;
    case BMP;
    case TIFF;
    case HEIC;
    case HEIF;
    case PSD;
    case WMF;
    case EMF;

    case PDF;
    case EPUB;
    case ZIP;
    case RAR;
    case TAR;
    case GZ;
    case BZ2;
    case SEVEN_Z;
    case XZ;

    case DOC;
    case DOCX;
    case DOCM;
    case DOT;
    case DOTX;
    case DOTM;
    case XLS;
    case XLSX;
    case XLSM;
    case XLT;
    case XLTX;
    case XLTM;
    case XLSB;
    case PPT;
    case PPTX;
    case PPTM;
    case POT;
    case POTX;
    case POTM;
    case PPS;
    case PPSX;
    case PPSM;
    case VSD;
    case VSDX;

    case ODT;
    case OTT;
    case ODM;
    case OTH;
    case ODS;
    case OTS;
    case ODP;
    case OTP;
    case ODG;
    case OTG;
    case ODF;
    case ODB;
    case SXW;
    case STW;
    case SXC;
    case STC;
    case SXI;
    case STI;
    case SXD;
    case SXM;

    case MP3;
    case WAV;
    case OGG;
    case M4A;
    case AAC;
    case FLAC;
    case OPUS;

    case MP4;
    case WEBM;
    case MOV;
    case AVI;
    case MKV;
    case MPEG;
    case M4V;

    case WOFF;
    case WOFF2;
    case TTF;
    case OTF;
    case EOT;
    case SFNT;
    case TTC;

    case WASM;
    case BIN;

    public function mime(): string
    {
        return MimeTypeDefinitions::mime($this);
    }

    public function extension(): string
    {
        return MimeTypeDefinitions::extension($this);
    }

    public function extensions(): array
    {
        return MimeTypeDefinitions::extensions($this);
    }

    public function contentType(?string $charset = null): string
    {
        $mime = $this->mime();

        if ($charset !== null && $this->isTextBased()) {
            return $mime . '; charset=' . $charset;
        }

        return $mime;
    }

    public function isTextBased(): bool
    {
        return MimeTypeDefinitions::isTextBased($this);
    }

    public function isJson(): bool
    {
        return $this === self::JSON || $this === self::JSONLD;
    }

    public function isMultipartFormData(): bool
    {
        return $this === self::MULTIPARTFORM;
    }

    public function isOpenOffice(): bool
    {
        return MimeTypeDefinitions::isOpenOffice($this);
    }

    public function isOpenDocument(): bool
    {
        return MimeTypeDefinitions::isOpenDocument($this);
    }

    public function isOpenOfficeCompatible(): bool
    {
        return MimeTypeDefinitions::isOpenOfficeCompatible($this);
    }

    public function isLibreOfficeCompatible(): bool
    {
        return $this->isOpenOfficeCompatible();
    }

    public static function fromExtension(string $extension): self
    {
        return MimeTypeDefinitions::fromExtension($extension) ?? self::BIN;
    }

    public static function fromFile(string $file): self
    {
        return MimeTypeDefinitions::fromFile($file);
    }

    public static function fromValue(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (!is_string($value)) {
            return null;
        }

        return MimeTypeDefinitions::fromExtension($value)
            ?? MimeTypeDefinitions::fromMime($value);
    }

    public static function fromMime(mixed $value): self
    {
        if (!is_string($value)) {
            return self::BIN;
        }

        return MimeTypeDefinitions::fromMime($value) ?? self::BIN;
    }

    public static function tryFrom(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (!is_string($value)) {
            return self::BIN;
        }

        return MimeTypeDefinitions::fromExtension($value)
            ?? MimeTypeDefinitions::fromMime($value)
            ?? MimeTypeDefinitions::fromFile($value);
    }

    public static function isValid(mixed $value): bool
    {
        if ($value instanceof self) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return MimeTypeDefinitions::extensionExists($value) || MimeTypeDefinitions::fromMime($value) !== null;
    }

    public function jsonSerialize(): mixed
    {
        return [$this->extension() => $this->mime()];
    }
}