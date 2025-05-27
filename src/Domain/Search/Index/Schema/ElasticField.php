<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Schema;

enum ElasticField: string
{
    case TYPE = 'type';
    case TOPLEVEL_TYPE = 'toplevel_type';
    case SUBLEVEL_TYPE = 'sublevel_type';
    case NAME = 'name';
    case PUBLICATION_DATE = 'publication_date';
    case DATE_PERIOD = 'date_period';
    case DATE_RANGE = 'date_range';
    case DATE_TO = 'date_to';
    case DATE_FROM = 'date_from';
    case DOCUMENT_PREFIX = 'document_prefix';
    case SUMMARY = 'summary';
    case STATUS = 'status';
    case TITLE = 'title';
    case PREFIXED_DOSSIER_NR = 'prefixed_dossier_nr';
    case DOSSIER_NR = 'dossier_nr';
    case ID = 'id';
    case PUBLICATION_REASON = 'publication_reason';
    case DECISION_DATE = 'decision_date';
    case DECISION = 'decision';
    case INQUIRY_IDS = 'inquiry_ids';
    case INQUIRY_CASE_NRS = 'inquiry_case_nrs';
    case MIME_TYPE = 'mime_type';
    case FILE_SIZE = 'file_size';
    case FILE_TYPE = 'file_type';
    case SOURCE_TYPE = 'source_type';
    case DATE = 'date';
    case FILENAME = 'filename';
    case GROUNDS = 'grounds';
    case METADATA = 'metadata';
    case DOCUMENT_NR = 'document_nr';
    case FAMILY_ID = 'family_id';
    case DOCUMENT_ID = 'document_id';
    case THREAD_ID = 'thread_id';
    case JUDGEMENT = 'judgement';
    case DOCUMENT_PAGES = 'document_pages';
    case CONTENT = 'content';
    case PAGE_NR = 'page_nr';
    case SUBJECT_NAMES = 'subject_names';
    case DEPARTMENT_NAMES = 'department_names';
    case DATE_FILTER = 'date_filter';
    case ORGANISATION_IDS = 'organisation_ids';
    case REFERRED_DOCUMENT_NRS = 'referred_document_nrs';
}
