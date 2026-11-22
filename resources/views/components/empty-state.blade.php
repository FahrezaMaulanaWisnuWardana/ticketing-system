@props(['action' => null, 'href' => null])
<div class="card text-center">
    <p class="font-medium text-slate-700">{{ __('common.empty_title') }}</p>
    <p class="mt-1 text-sm text-slate-500">{{ __('common.empty_hint') }}</p>
    @if($action && $href)
        <a href="{{ $href }}" class="btn btn-primary mt-4">{{ $action }}</a>
    @endif
</div>
