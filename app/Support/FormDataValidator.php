<?php

namespace App\Support;

use App\Models\FormTemplate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FormDataValidator
{
    /**
     * Build Laravel validation rules from a stored form template schema.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(FormTemplate $template, bool $enforceRequired = true): array
    {
        $rules = [];

        foreach ($template->fields() as $field) {
            $key = $field['key'] ?? null;
            $type = $field['type'] ?? 'text';

            if (! is_string($key) || $key === '' || $type === 'file') {
                continue;
            }

            $required = $enforceRequired && ($field['required'] ?? false);
            $fieldRules = [$required ? 'required' : 'nullable'];

            $fieldRules = array_merge($fieldRules, match ($type) {
                'textarea' => ['string', 'max:5000'],
                'number' => ['numeric'],
                'date' => ['date'],
                'dropdown', 'radio' => [
                    'string',
                    Rule::in($this->options($field)),
                ],
                'checkbox' => [
                    'array',
                    ...($required ? ['min:1'] : []),
                ],
                default => ['string', 'max:255'],
            });

            if ($type === 'checkbox') {
                $fieldRules[] = function (string $attribute, mixed $value, \Closure $fail) use ($field) {
                    $allowed = $this->options($field);

                    foreach ((array) $value as $item) {
                        if (! in_array($item, $allowed, true)) {
                            $fail('The selected value is not a valid option.');
                        }
                    }
                };
            }

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(FormTemplate $template, array $data, bool $enforceRequired = true): array
    {
        $rules = $this->rules($template, $enforceRequired);
        $attributes = [];

        foreach ($template->fields() as $field) {
            if (isset($field['key'], $field['label'])) {
                $attributes[$field['key']] = $field['label'];
            }
        }

        $validator = Validator::make($data, $rules, [], $attributes);

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, string>
     */
    private function options(array $field): array
    {
        return array_values(array_map(
            fn ($option) => is_array($option) ? (string) ($option['value'] ?? '') : (string) $option,
            $field['options'] ?? [],
        ));
    }
}
