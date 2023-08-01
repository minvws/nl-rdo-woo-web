<?php

declare(strict_types=1);

namespace App\Service\Worker\Audio;

class Metadata
{
    protected int $duration;        // Duration of the audio file in seconds
    protected int $sampleRate;      // Sample rate used (44100 etc)
    protected int $channels;        // Number of channels (1 for mono, 2 for stereo)
    protected int $bitRate;         // Used encoding bit rate (128000 etc)
    protected string $format;       // Audio format (mp3, wav, ogg etc)

    public function __construct(int $duration, int $sampleRate, int $channels, int $bitRate, string $format)
    {
        $this->duration = $duration;
        $this->sampleRate = $sampleRate;
        $this->channels = $channels;
        $this->bitRate = $bitRate;
        $this->format = $format;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getSampleRate(): int
    {
        return $this->sampleRate;
    }

    public function getChannels(): int
    {
        return $this->channels;
    }

    public function getBitRate(): int
    {
        return $this->bitRate;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
