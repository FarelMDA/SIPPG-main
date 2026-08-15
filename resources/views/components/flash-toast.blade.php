{{-- Menampilkan toast yang di-flash ke session sebelum redirect (mis. GantiPasswordForm).
     Dispatch browser event langsung sebelum navigasi penuh akan "kalah" lomba dengan
     perpindahan halaman — flash-session + baca ulang di halaman tujuan lebih andal. --}}
@if(session()->has('flash_toast'))
    <div x-data x-init="window.dispatchEvent(new CustomEvent('toast', { detail: @js(session('flash_toast')) }))"></div>
@endif
