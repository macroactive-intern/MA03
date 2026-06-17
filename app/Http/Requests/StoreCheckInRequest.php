<?php

namespace App\Http\Requests;

use App\Models\CheckIn;
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
                'max:500',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $date = $this->input('checked_in_date');

            if ($date && CheckIn::where('user_id', $this->user()->id)
                ->where('checked_in_date', $date)
                ->exists()
            ) {
                $validator->errors()->add(
                    'checked_in_date',
                    'You have already checked in for this date.'
                );
            }
        });
    }
}
