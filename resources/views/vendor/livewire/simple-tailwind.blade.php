@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between gap-1">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-8 items-center justify-center rounded-md border border-border bg-white px-2 text-ink-disabled opacity-40">
                    <x-icon name="chevron-left" size="16" />
                </span>
            @else
                @if(method_exists($paginator,'getCursorName'))
                    <button type="button" wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->previousCursor()->encode() }}" wire:click="setPage('{{$paginator->previousCursor()->encode()}}','{{ $paginator->getCursorName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="inline-flex h-8 items-center justify-center rounded-md border border-border bg-white px-2 text-ink-secondary transition-colors hover:bg-surface-subtle">
                        <x-icon name="chevron-left" size="16" />
                    </button>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="inline-flex h-8 items-center justify-center rounded-md border border-border bg-white px-2 text-ink-secondary transition-colors hover:bg-surface-subtle">
                        <x-icon name="chevron-left" size="16" />
                    </button>
                @endif
            @endif

            @if ($paginator->hasMorePages())
                @if(method_exists($paginator,'getCursorName'))
                    <button type="button" wire:key="cursor-{{ $paginator->getCursorName() }}-{{ $paginator->nextCursor()->encode() }}" wire:click="setPage('{{$paginator->nextCursor()->encode()}}','{{ $paginator->getCursorName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="inline-flex h-8 items-center justify-center rounded-md border border-border bg-white px-2 text-ink-secondary transition-colors hover:bg-surface-subtle">
                        <x-icon name="chevron-right" size="16" />
                    </button>
                @else
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="inline-flex h-8 items-center justify-center rounded-md border border-border bg-white px-2 text-ink-secondary transition-colors hover:bg-surface-subtle">
                        <x-icon name="chevron-right" size="16" />
                    </button>
                @endif
            @else
                <span class="inline-flex h-8 items-center justify-center rounded-md border border-border bg-white px-2 text-ink-disabled opacity-40">
                    <x-icon name="chevron-right" size="16" />
                </span>
            @endif
        </nav>
    @endif
</div>
