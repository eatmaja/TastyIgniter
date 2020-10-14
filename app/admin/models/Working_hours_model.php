<?php

namespace Admin\Models;

use Igniter\Flame\Location\Models\AbstractWorkingHour;

/**
 * Working hours Model Class
 */
class Working_hours_model extends AbstractWorkingHour
{
    public $fillable = ['location_id', 'weekday', 'opening_time', 'closing_time', 'status', 'type'];

    public $casts = [
        'weekday' => 'integer',
        'opening_time' => 'time',
        'closing_time' => 'time',
        'status' => 'boolean',
    ];

    public function getHoursByLocation($id)
    {
        $collection = [];

        foreach (self::where('location_id', $id)->get() as $row) {
            $row = $this->parseRecord($row);
            $collection[$row['type']][$row['weekday']] = $row;
        }

        return $collection;
    }

    public function parseRecord($row)
    {
        $type = !empty($row['type']) ? $row['type'] : 'opening';
        $collection = array_merge($row, [
            'location_id' => $row['location_id'],
            'day' => $row['day'],
            'type' => $type,
            'open' => strtotime("{$row['day']} {$row['opening_time']}"),
            'close' => strtotime("{$row['day']} {$row['closing_time']}"),
            'is_24_hours' => $row['open_all_day'],
        ]);

        return $collection;
    }

    public function getWeekDaysOptions()
    {
        return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    }

    public function getTimesheetOptions($value, $data)
    {
        $result = new \stdClass();
        $result->daysOfWeek = [];
        $result->timesheet = [];

        $options = $value ?? [];
        foreach ($this->getWeekDaysOptions() as $key => $day) {
            $result->daysOfWeek[] = ['name' => $day];
            $result->timesheet[] = $options[$key] ?? ['day' => $key, 'open' => '00:00', 'close' => '23:59', 'status' => 1];
        }

        return $result;
    }
}
