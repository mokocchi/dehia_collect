<?php

namespace App\Document;

class Type {
    const SIMPLE = "simple";
    const TEXT_INPUT = "textInput";
    const NUMBER_INPUT = "numberInput";
    const CAMERA_INPUT = "cameraInput";
    const SELECT = "select";
    const MULTIPLE = "multiple";
    const COUNTERS = "counters";
    const COLLECT = "collect";
    const DEPOSIT = "deposit";
    const GPS_INPUT = "GPSInput";
    const AUDIO_INPUT = "audioInput";

    const TYPES = [
        self::SIMPLE, self::TEXT_INPUT, self::NUMBER_INPUT, self::CAMERA_INPUT, self::SELECT, self::MULTIPLE,
        self::COUNTERS, self::COLLECT, self::DEPOSIT, self::GPS_INPUT, self::AUDIO_INPUT
    ];
}