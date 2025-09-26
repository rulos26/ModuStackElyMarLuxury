<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PieceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
			'code' => 'required|string',
			'name' => 'required|string',
			'description' => 'string',
			'category_id' => 'required',
			'subcategory_id' => 'nullable',
			'weight' => 'nullable|numeric|min:0',
			'cost_price' => 'nullable|numeric|min:0',
			'sale_price' => 'nullable|numeric|min:0',
			'status' => 'required',
        ];
    }
}
