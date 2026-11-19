<?php

declare(strict_types=1);

namespace Shared\Domain\Upload\FileType;

enum FileExtension: string
{
    case AAC = 'aac';
    case ASF = 'asf';
    case AVI = 'avi';
    case CSV = 'csv';
    case DOC = 'doc';
    case DOCX = 'docx';
    case M4A = 'm4a';
    case MOV = 'mov';
    case MP3 = 'mp3';
    case MP4 = 'mp4';
    case MPG = 'mpg';
    case ODF = 'odf';
    case ODP = 'odp';
    case ODS = 'ods';
    case ODT = 'odt';
    case OPUS = 'opus';
    case PDF = 'pdf';
    case PPS = 'pps';
    case PPSX = 'ppsx';
    case PPT = 'ppt';
    case PPTX = 'pptx';
    case RTF = 'rtf';
    case SEVEN_ZIP = '7z';
    case SVG = 'svg';
    case TXT = 'txt';
    case WAV = 'wav';
    case WMV = 'wmv';
    case XLS = 'xls';
    case XLSX = 'xlsx';
    case ZIP = 'zip';
}
