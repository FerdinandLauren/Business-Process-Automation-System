<?php

namespace Tests\Unit;

use App\Models\FormTemplate;
use App\Support\FormDataValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormDataValidatorTest extends TestCase
{
    private function template(): FormTemplate
    {
        return new FormTemplate([
            'schema' => [
                'fields' => [
                    ['key' => 'system_name', 'label' => 'System or application', 'type' => 'text', 'required' => true],
                    ['key' => 'amount', 'label' => 'Claim amount', 'type' => 'number', 'required' => true],
                    ['key' => 'needed_by', 'label' => 'Needed by', 'type' => 'date', 'required' => false],
                    ['key' => 'access_type', 'label' => 'Access type', 'type' => 'dropdown', 'required' => true, 'options' => ['New account', 'Additional permission']],
                    ['key' => 'acknowledgement', 'label' => 'Acceptable use', 'type' => 'checkbox', 'required' => true, 'options' => ['I accept the policy']],
                    ['key' => 'receipt', 'label' => 'Receipt', 'type' => 'file', 'required' => true],
                ],
            ],
        ]);
    }

    public function test_it_accepts_a_complete_payload(): void
    {
        $validated = (new FormDataValidator)->validate($this->template(), [
            'system_name' => 'Finance Reporting Workspace',
            'amount' => '2450000',
            'needed_by' => '2026-12-01',
            'access_type' => 'Additional permission',
            'acknowledgement' => ['I accept the policy'],
            'receipt' => 'ignored-for-file-fields',
        ]);

        $this->assertSame('Finance Reporting Workspace', $validated['system_name']);
        $this->assertSame('Additional permission', $validated['access_type']);
        $this->assertSame(['I accept the policy'], $validated['acknowledgement']);

        // File fields are handled by the attachment service, not the schema validator.
        $this->assertArrayNotHasKey('receipt', $validated);
    }

    public function test_it_rejects_missing_required_fields(): void
    {
        try {
            (new FormDataValidator)->validate($this->template(), []);
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('system_name', $exception->errors());
            $this->assertArrayHasKey('amount', $exception->errors());
            $this->assertArrayHasKey('access_type', $exception->errors());
            $this->assertArrayHasKey('acknowledgement', $exception->errors());
            $this->assertArrayNotHasKey('needed_by', $exception->errors());
        }
    }

    public function test_it_allows_missing_required_fields_when_drafting(): void
    {
        $validated = (new FormDataValidator)->validate($this->template(), [
            'system_name' => 'Draft system',
        ], enforceRequired: false);

        $this->assertSame('Draft system', $validated['system_name']);
    }

    public function test_it_rejects_a_dropdown_value_outside_the_schema(): void
    {
        $this->expectException(ValidationException::class);

        (new FormDataValidator)->validate($this->template(), [
            'system_name' => 'Finance',
            'amount' => '10',
            'access_type' => 'Root access',
            'acknowledgement' => ['I accept the policy'],
        ]);
    }

    public function test_it_rejects_a_checkbox_value_outside_the_schema(): void
    {
        try {
            (new FormDataValidator)->validate($this->template(), [
                'system_name' => 'Finance',
                'amount' => '10',
                'access_type' => 'New account',
                'acknowledgement' => ['Something else entirely'],
            ]);
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('acknowledgement', $exception->errors());
        }
    }

    public function test_it_rejects_non_numeric_amounts_and_invalid_dates(): void
    {
        try {
            (new FormDataValidator)->validate($this->template(), [
                'system_name' => 'Finance',
                'amount' => 'two million',
                'needed_by' => 'next Tuesday',
                'access_type' => 'New account',
                'acknowledgement' => ['I accept the policy'],
            ]);
            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
            $this->assertArrayHasKey('needed_by', $exception->errors());
        }
    }

    public function test_it_strips_unknown_keys_from_the_validated_payload(): void
    {
        $validated = (new FormDataValidator)->validate($this->template(), [
            'system_name' => 'Finance',
            'amount' => '10',
            'access_type' => 'New account',
            'acknowledgement' => ['I accept the policy'],
            'unexpected' => 'should not survive',
        ]);

        $this->assertArrayNotHasKey('unexpected', $validated);
    }
}
