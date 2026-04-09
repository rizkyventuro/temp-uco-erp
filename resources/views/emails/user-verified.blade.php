# Halo, {{ $user->name }}!

Selamat! Akun Anda telah berhasil diverifikasi oleh admin. Sekarang Anda dapat mengakses semua fitur di platform kami.

Silakan klik tombol di bawah ini untuk masuk ke akun Anda.

<x-mail::button :url="url('/login')">
Login Sekarang
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
