<?php namespace HolidayPirates\RssFeed\Models;

use Model;

/**
 * Class Settings
 * @package HolidayPirates\RssFeed\Models
 */
class Settings extends Model
{
    public $implement = [
        'System.Behaviors.SettingsModel'
    ];

    public $settingsCode = 'holidaypirates_rss_settings';

    public $settingsFields = 'fields.yaml';
}
