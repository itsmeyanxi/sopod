{{--
    GL Account Searchable Selector
    Props:
      $field       — HTML name attribute (e.g. 'gl_loan_account')
      $label       — Display label
      $value       — Current value (account code string)
      $glAccounts  — Array of ['code', 'name', 'search'] from controller
      $required    — bool (optional, default false)
      $uid         — Unique suffix for IDs when multiple selectors on same page
--}}
@php
    $uid      = $uid ?? $field;
    $required = $required ?? false;
    $value    = $value ?? '';
    // Find the display label for the current value
    $currentLabel = '';
    if ($value) {
        foreach ($glAccounts as $acct) {
            if ($acct['code'] === $value) {
                $currentLabel = $acct['code'] . ' — ' . $acct['name'];
                break;
            }
        }
        if (!$currentLabel) $currentLabel = $value;
    }
@endphp

<div>
    <label class="block text-sm font-semibold text-gray-300 mb-1">
        {{ $label }}@if($required) <span class="text-red-500">*</span>@endif
    </label>
    <div class="relative" style="position:relative;">
        <input
            type="text"
            id="gl_search_{{ $uid }}"
            placeholder="Type to search accounts..."
            autocomplete="off"
            value="{{ old($field, $currentLabel) }}"
            class="w-full bg-gray-800 border border-gray-600 rounded px-3 py-2 pr-8 text-sm focus:border-blue-500 focus:outline-none gl-search-input"
            data-uid="{{ $uid }}"
        >
        <svg class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <div
            id="gl_dropdown_{{ $uid }}"
            class="absolute z-50 w-full bg-gray-800 border border-gray-600 rounded mt-1 shadow-lg hidden max-h-56 overflow-y-auto"
            style="position:absolute; left:0; top:100%;"
        >
            <div class="sticky top-0 bg-gray-900 px-3 py-1 text-xs text-gray-300 font-semibold border-b border-gray-700">
                Select an account
            </div>
            @foreach($glAccounts as $acct)
            <div
                class="gl-option px-3 py-2 hover:bg-blue-600 hover:text-white cursor-pointer border-b border-gray-100 last:border-0 transition-colors"
                data-code="{{ $acct['code'] }}"
                data-name="{{ $acct['name'] }}"
                data-search="{{ $acct['search'] }}"
                data-uid="{{ $uid }}"
            >
                <div class="font-mono font-semibold text-sm">{{ $acct['code'] }}</div>
                <div class="text-xs opacity-60">{{ $acct['name'] }}</div>
            </div>
            @endforeach
            <div class="gl-no-results px-3 py-2 text-xs text-gray-400 hidden">No accounts found.</div>
        </div>
    </div>
    <input type="hidden" name="{{ $field }}" id="gl_hidden_{{ $uid }}" value="{{ old($field, $value) }}">
</div>
