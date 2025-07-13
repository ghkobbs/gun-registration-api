<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CrimeEvidence extends Model
{
    use HasFactory;

    protected $fillable = [
        'crime_report_id',
        'evidence_type',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'metadata',
        'captured_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
        'captured_at' => 'datetime',
    ];

    // Relationships
    public function crimeReport(): BelongsTo
    {
        return $this->belongsTo(CrimeReport::class);
    }

    // Accessors
    public function getIsImageAttribute(): bool
    {
        return in_array($this->evidence_type, ['photo', 'image']) ||
               str_starts_with($this->file_type, 'image/');
    }

    public function getIsVideoAttribute(): bool
    {
        return $this->evidence_type === 'video' ||
               str_starts_with($this->file_type, 'video/');
    }

    public function getIsAudioAttribute(): bool
    {
        return $this->evidence_type === 'audio' ||
               str_starts_with($this->file_type, 'audio/');
    }

    public function getIsDocumentAttribute(): bool
    {
        return $this->evidence_type === 'document' ||
               in_array($this->file_type, [
                   'application/pdf',
                   'application/msword',
                   'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                   'text/plain',
               ]);
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getViewUrlAttribute(): string
    {
        return route('evidence.view', $this->id);
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('evidence.download', $this->id);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->is_image) {
            return null;
        }

        return route('evidence.thumbnail', $this->id);
    }

    public function getGpsCoordinatesAttribute(): ?array
    {
        if (!$this->metadata || !isset($this->metadata['gps'])) {
            return null;
        }

        $gps = $this->metadata['gps'];
        
        if (isset($gps['latitude']) && isset($gps['longitude'])) {
            return [
                'latitude' => $gps['latitude'],
                'longitude' => $gps['longitude'],
            ];
        }

        return null;
    }

    // Methods
    public function extractMetadata(): array
    {
        $metadata = [];

        if ($this->is_image) {
            $metadata = $this->extractImageMetadata();
        } elseif ($this->is_video) {
            $metadata = $this->extractVideoMetadata();
        } elseif ($this->is_audio) {
            $metadata = $this->extractAudioMetadata();
        }

        return $metadata;
    }

    private function extractImageMetadata(): array
    {
        $filePath = Storage::disk('evidence')->path($this->file_path);
        
        if (!file_exists($filePath)) {
            return [];
        }

        $exif = @exif_read_data($filePath);
        
        if (!$exif) {
            return [];
        }

        $metadata = [];

        // Basic image info
        if (isset($exif['COMPUTED']['Width'])) {
            $metadata['width'] = $exif['COMPUTED']['Width'];
        }
        if (isset($exif['COMPUTED']['Height'])) {
            $metadata['height'] = $exif['COMPUTED']['Height'];
        }

        // Camera info
        if (isset($exif['Make'])) {
            $metadata['camera_make'] = $exif['Make'];
        }
        if (isset($exif['Model'])) {
            $metadata['camera_model'] = $exif['Model'];
        }

        // Date taken
        if (isset($exif['DateTimeOriginal'])) {
            $metadata['date_taken'] = $exif['DateTimeOriginal'];
        }

        // GPS coordinates
        if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude'])) {
            $metadata['gps'] = [
                'latitude' => $this->getGpsCoordinate($exif['GPSLatitude'], $exif['GPSLatitudeRef']),
                'longitude' => $this->getGpsCoordinate($exif['GPSLongitude'], $exif['GPSLongitudeRef']),
            ];
        }

        return $metadata;
    }

    private function extractVideoMetadata(): array
    {
        // This would require a video processing library like FFmpeg
        // For now, return basic metadata
        return [
            'duration' => null,
            'resolution' => null,
            'format' => pathinfo($this->file_name, PATHINFO_EXTENSION),
        ];
    }

    private function extractAudioMetadata(): array
    {
        // This would require an audio processing library
        // For now, return basic metadata
        return [
            'duration' => null,
            'format' => pathinfo($this->file_name, PATHINFO_EXTENSION),
        ];
    }

    private function getGpsCoordinate($coordinate, $hemisphere): float
    {
        if (!is_array($coordinate) || count($coordinate) < 3) {
            return 0;
        }

        $degrees = $this->gpsToDecimal($coordinate[0]);
        $minutes = $this->gpsToDecimal($coordinate[1]);
        $seconds = $this->gpsToDecimal($coordinate[2]);

        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);

        return ($hemisphere === 'S' || $hemisphere === 'W') ? -$decimal : $decimal;
    }

    private function gpsToDecimal($coordinate): float
    {
        $parts = explode('/', $coordinate);
        
        if (count($parts) <= 1) {
            return (float) $coordinate;
        }

        return (float) $parts[0] / (float) $parts[1];
    }

    public function delete(): bool
    {
        // Delete the physical file
        if (Storage::disk('evidence')->exists($this->file_path)) {
            Storage::disk('evidence')->delete($this->file_path);
        }

        // Delete thumbnail if it exists
        $thumbnailPath = 'thumbnails/' . pathinfo($this->file_path, PATHINFO_FILENAME) . '.jpg';
        if (Storage::disk('evidence')->exists($thumbnailPath)) {
            Storage::disk('evidence')->delete($thumbnailPath);
        }

        return parent::delete();
    }
}