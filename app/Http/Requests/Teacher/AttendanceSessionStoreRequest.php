<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceSessionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isTeacher();
    }

    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date', 'after:started_at'],
            'late_tolerance_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'qr_dynamic' => ['nullable', 'boolean'],
            'qr_rotate_seconds' => ['nullable', 'integer', 'min:15', 'max:120'],
            'location_validation' => ['nullable', 'boolean'],
            'center_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_meters' => ['nullable', 'integer', 'min:10', 'max:500'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $locationValidation = (bool) $this->boolean('location_validation');
            if (!$locationValidation) {
                return;
            }

            if ($this->input('center_lat') === null || $this->input('center_lng') === null || $this->input('radius_meters') === null) {
                $validator->errors()->add('location_validation', 'center_lat, center_lng, dan radius_meters wajib diisi jika validasi lokasi aktif.');
            }
        });
    }
}
