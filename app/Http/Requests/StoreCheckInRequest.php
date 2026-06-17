<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checked_in_date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:' . config('check_ins.notes_max_length'),
            ],
        ];
    }
}
