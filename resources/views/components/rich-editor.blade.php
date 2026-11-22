@props([
    'name' => 'description',
    'id' => null,
    'value' => '',
    'wire' => null,
    'attachmentWire' => null,
    'label' => null,
    'placeholder' => '',
    'error' => null,
])

@php
    $id ??= $name;
    $initial = (string) old($name, $value);
    $config = [
        'value' => $initial,
        'placeholder' => $placeholder,
        'label' => (string) ($label ?? ''),
        'uploadUrl' => route('editor.uploads.store'),
        'linkPrompt' => __('common.editor_link_prompt'),
        'uploadFailed' => __('common.editor_upload_failed'),
        'editorLoading' => __('common.editor_loading'),
    ];
@endphp

<div class="space-y-1" x-data="richEditor(@js($config))">
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ $label }}</label>
    @endif

    <div class="rich-editor rounded-lg border border-slate-300 bg-white focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-800" wire:ignore>
        <div class="rich-toolbar" role="toolbar" aria-label="{{ __('common.editor_toolbar') }}">
            <button type="button" title="{{ __('common.editor_bold') }}" aria-label="{{ __('common.editor_bold') }}"
                    @click="toggle('Bold')" :class="{ 'is-active': isActive('bold') }"><b>B</b></button>
            <button type="button" title="{{ __('common.editor_italic') }}" aria-label="{{ __('common.editor_italic') }}"
                    @click="toggle('Italic')" :class="{ 'is-active': isActive('italic') }"><i>I</i></button>
            <button type="button" title="{{ __('common.editor_underline') }}" aria-label="{{ __('common.editor_underline') }}"
                    @click="toggle('Underline')" :class="{ 'is-active': isActive('underline') }"><u>U</u></button>
            <button type="button" title="{{ __('common.editor_strike') }}" aria-label="{{ __('common.editor_strike') }}"
                    @click="toggle('Strike')" :class="{ 'is-active': isActive('strike') }"><s>S</s></button>
            <span class="rich-divider"></span>
            <button type="button" title="{{ __('common.editor_bullet') }}" aria-label="{{ __('common.editor_bullet') }}"
                    @click="toggle('BulletList')" :class="{ 'is-active': isActive('bulletList') }">•</button>
            <button type="button" title="{{ __('common.editor_ordered') }}" aria-label="{{ __('common.editor_ordered') }}"
                    @click="toggle('OrderedList')" :class="{ 'is-active': isActive('orderedList') }">1.</button>
            <button type="button" title="{{ __('common.editor_quote') }}" aria-label="{{ __('common.editor_quote') }}"
                    @click="toggle('Blockquote')" :class="{ 'is-active': isActive('blockquote') }">❝</button>
            <button type="button" title="{{ __('common.editor_code') }}" aria-label="{{ __('common.editor_code') }}"
                    @click="toggle('Code')" :class="{ 'is-active': isActive('code') }">&lt;/&gt;</button>
            <span class="rich-divider"></span>
            <button type="button" title="{{ __('common.editor_link') }}" aria-label="{{ __('common.editor_link') }}"
                    @click="setLink()" :class="{ 'is-active': isActive('link') }">🔗</button>
            <button type="button" title="{{ __('common.attachments') }}" aria-label="{{ __('common.attachments') }}"
                    @click="$refs.fileInput.click()" :disabled="busy">📎</button>
        </div>

        <div x-ref="editor" id="{{ $id }}" class="rich-surface"></div>

        <div x-show="dragging" x-cloak class="rich-drop">
            {{ __('common.editor_drop_hint') }}
        </div>

        <div x-show="busy" x-cloak class="rich-status" role="status">{{ __('common.editor_uploading') }}</div>
    </div>

    <input type="hidden" x-ref="input" name="{{ $name }}" @if($wire) wire:model="{{ $wire }}" @endif value="{{ $initial }}">
    <input type="hidden" x-ref="attachments" name="attachment_ids" @if($attachmentWire) wire:model="{{ $attachmentWire }}" @endif>
    <input type="file" x-ref="fileInput" class="hidden" multiple @change="onPick($event)">

    <p x-show="error" x-cloak class="text-xs text-rose-600 dark:text-rose-400" role="alert" x-text="error"></p>
    @error($name)<p class="text-xs text-rose-600 dark:text-rose-400" role="alert">{{ $message }}</p>@enderror
    @if($error)<p class="text-xs text-rose-600 dark:text-rose-400" role="alert">{{ $error }}</p>@endif
</div>
