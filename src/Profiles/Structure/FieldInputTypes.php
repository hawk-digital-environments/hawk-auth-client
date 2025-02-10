<?php

namespace Hawk\AuthClient\Profiles\Structure;

enum FieldInputTypes: string
{
    case TEXT = 'text';
    case TEXTAREA = 'textarea';
    case SELECT = 'select';
    case RADIO = 'select-radiobuttons';
    case MULTISELECT = 'multiselect';
    case CHECKBOX = 'multiselect-checkboxes';
    case EMAIL = 'html5-email';
    case TELEPHONE = 'html5-tel';
    case URL = 'html5-url';
    case NUMBER = 'html5-number';
    case RANGE = 'html5-range';
    case DATE = 'html5-date';
    case MONTH = 'html5-month';
    case WEEK = 'html5-week';
    case TIME = 'html5-time';
}
