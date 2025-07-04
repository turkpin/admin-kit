<?php

declare(strict_types=1);

namespace Turkpin\AdminKit\Fields;

interface FieldTypeInterface
{
    /**
     * Get the field type name
     */
    public function getTypeName(): string;

    /**
     * Render the field for display (show/index views)
     */
    public function renderDisplay($value, array $options = []): string;

    /**
     * Render the field for form input (new/edit views)
     */
    public function renderFormInput(string $name, $value, array $options = []): string;

    /**
     * Process the submitted form value before saving
     */
    public function processFormValue($value, array $options = []);

    /**
     * Validate the field value
     */
    public function validate($value, array $options = []): array;

    /**
     * Get default configuration options for this field type
     */
    public function getDefaultOptions(): array;

    /**
     * Check if this field type supports the given option
     */
    public function supportsOption(string $option): bool;
}
