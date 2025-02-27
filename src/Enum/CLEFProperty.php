<?php
declare(strict_types = 1);
namespace Pablo\MonologSeq\Enum;

enum CLEFProperty: string
{
    case TIMESTAMP = '@t';
    case MESSAGE = '@m';
    case MESSAGE_TEMPLATE = '@mt';
    case LEVEL = '@l';
    case EXCEPTION = '@x';
    case EVENT_ID = '@i';
    case RENDERINGS = '@r';
    case TRACE_ID = '@tr';
    case SPAN_ID = '@sp';
    case PARENT_ID = '@ps';
    case START = '@st';
    case INSTRUMENTATION_SCOPE = '@sc';
    case RESOURCE_ATTRIBUTES = '@ra';
    case SPAN_KIND = '@sk';
}