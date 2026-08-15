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
        <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col items-center justify-between gap-3 border-t border-border-subtle bg-white px-4 py-3 sm:flex-row sm:gap-0">
            <p class="text-sm text-ink-secondary">
                Menampilkan <span class="font-medium text-ink-primary">{{ $paginator->firstItem() ?? 0 }}</span>–<span class="font-medium text-ink-primary">{{ $paginator->lastItem() ?? 0 }}</span> dari <span class="font-medium text-ink-primary">{{ $paginator->total() }}</span>
            </p>

            <div class="flex items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span class="inline-flex h-8 items-center justify-center rounded-md border border-border bg-white px-2 text-ink-disabled opacity-40">
                        <x-icon name="chevron-left" size="16" />
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="inline-flex h-8 items-center justify-center rounded-md border border-border bg-white px-2 text-ink-secondary transition-colors hover:bg-surface-subtle">
                        <x-icon name="chevron-left" size="16" />
                    </button>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="flex h-8 w-8 items-center justify-center text-sm text-ink-muted">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-brand-primary text-sm font-medium text-white shadow-xs">
                                        {{ $page }}
                                    </span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-sm font-medium text-ink-secondary transition-colors hover:bg-surface-subtle" aria-label="{{ __('Ke halaman :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" class="inline-flex h-8 items-center justify-center rounded-md border border-border bg-white px-2 text-ink-secondary transition-colors hover:bg-surface-subtle">
                        <x-icon name="chevron-right" size="16" />
                    </button>
                @else
                    <span class="inline-flex h-8 items-center justify-center rounded-md border border-border bg-white px-2 text-ink-disabled opacity-40">
                        <x-icon name="chevron-right" size="16" />
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>
