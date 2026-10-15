<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderPhotoRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->organization_id; }
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'category' => ['required', 'in:before,damage,process,after,other'],
            'caption' => ['nullable', 'string', 'max:255'],
            'visible_to_client' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $file = $this->file('photo');
            if ($validator->errors()->has('photo') || ! $file) {
                return;
            }

            $dimensions = @getimagesize($file->getRealPath());
            if (! $dimensions || ($dimensions[0] * $dimensions[1]) > 16_000_000) {
                $validator->errors()->add('photo', 'Файл изображения повреждён или превышает ограничение по разрешению.');
                return;
            }

            $decodedImage = @imagecreatefromstring($file->get());
            if (! $decodedImage) {
                $validator->errors()->add('photo', 'Не удалось проверить содержимое изображения.');
                return;
            }

            imagedestroy($decodedImage);
        }];
    }
}